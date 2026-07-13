<?php

namespace App\Services\Sync;

use App\DTO\Project\ProjectDTO;
use App\Enums\SyncStatus;
use App\Jobs\ProcessJiraChunkJob;
use App\Jobs\ProcessMilestonesJob;
use App\Models\User;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Ping\ConnectJiraService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Log;
use Throwable;

class SyncIssueService extends ConnectJiraService
{
    protected $syncRepo;
    protected $projectRepo;

    public function __construct(
        SyncIssueInterface $syncRepo,
        ProjectInterface   $projectRepo
    ) {
        parent::__construct();
        $this->syncRepo = $syncRepo;
        $this->projectRepo = $projectRepo;
    }

    protected function fetchAndProcessIssuesByJql(string $jql, User $originalUser, string $mode): void
    {
        DB::disableQueryLog();
        set_time_limit(0);

        $this->syncAndFetchProjects($originalUser);

        $user = clone $originalUser;
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = 50;

        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_11306,status,created,customfield_10108,customfield_10115,subtasks&expand=changelog";

        // Tạo URL cho đợt check đầu tiên nhằm lấy "total" số lượng bản ghi
        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error']) || empty($firstData['issues'])) {
            Cache::put("jira-sync-status:{$originalUser->id}:{$mode}", SyncStatus::FAILED->value, now()->addMinutes(30));
            return;
        }

        $total = $firstData['total'] ?? 0;
        $jobs = [];

        $jobs[] = new ProcessJiraChunkJob(null, $firstUrl, $originalUser);
        unset($firstData);

        // Vòng lặp lấy các page tiếp theo (nếu có)
        if ($total > $maxResults) {
            for ($startAt = $maxResults; $startAt < $total; $startAt += $maxResults) {
                $url = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt={$startAt}&maxResults={$maxResults}" . $fieldsParam;
                $jobs[] = new ProcessJiraChunkJob(null, $url, $originalUser);
            }
        }

        // Chạy Batch Job song song trên Queue
        Bus::batch($jobs)
            ->then(function () use ($originalUser) {
                ProcessMilestonesJob::dispatch($originalUser)->onQueue('jira-sync');
            })
            ->catch(function ($batch, Throwable $e) use ($originalUser, $mode) {
                Cache::put("jira-sync-status:{$originalUser->id}:{$mode}", SyncStatus::FAILED->value, now()->addMinutes(30));
                Log::error("Lỗi Batch Sync Jira hệ thống (User {$originalUser->id}): " . $e->getMessage());
            })
            ->name("Jira Sync - {$mode} - User {$originalUser->id}")
            ->onQueue('jira-sync')
            ->dispatch();
    }

    public function syncFullIssues(User $user, array $projectNames = [], string $period_from = '', string $period_to = ''): void
    {
        $fromDate = Carbon::createFromFormat('m-Y', $period_from)->startOfMonth()->format('Y-m-d');
        $toDate = Carbon::createFromFormat('m-Y', $period_to)->endOfMonth()->format('Y-m-d');

        $jqlParts = [];

        if (!empty($projectNames)) {
            $projectStr = implode(",", $projectNames);
            $jqlParts[] = "project in ({$projectStr})";
        }

        $jqlParts[] = "issuetype IN (Bug, \"Sub-task\", Story, Milestone)";
        $jqlParts[] = "status != Cancelled";
        $jqlParts[] = "created >= '{$fromDate}'";
        $jqlParts[] = "created <= '{$toDate}'";

        $jql = implode(" AND ", $jqlParts) . " ORDER BY created ASC";

        $this->fetchAndProcessIssuesByJql($jql, $user, 'full');
    }

    public function syncFromLastIssues(User $user): void
    {
        $lastSyncString = $this->syncRepo->getLastSyncTime();

        $startSyncTime = !empty($lastSyncString)
            ? Carbon::parse($lastSyncString)->subMinutes(30)->format('Y-m-d H:i')
            : Carbon::now()->startOfMonth()->format('Y-m-d H:i');

        $jql = "issuetype IN (Bug, \"Sub-task\", Story, Milestone) 
        AND status != Cancelled 
        AND created >= '{$startSyncTime}' 
        ORDER BY created ASC";

        $this->fetchAndProcessIssuesByJql($jql, $user, 'last');
    }

    public function syncAndFetchProjects(User $user): array
    {
        $clonedUser = clone $user;
        if (base64_decode($clonedUser->jira_password, true) !== false) {
            try {
                $clonedUser->jira_password = Crypt::decryptString($clonedUser->jira_password);
            } catch (Exception $e) {}
        }

        $url = "/rest/api/2/project";
        $client = $this->initClient($clonedUser);

        try {
            $response = $client->getAsync($url)->wait();
            $jiraData = json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error("Jira Sync Projects Failed: " . $e->getMessage());
            $jiraData = ['error' => $e->getMessage()];
        }

        if (!empty($jiraData['error']) || !is_array($jiraData)) {
            return $this->projectRepo->getProjectsJson($user->id);
        }

        $projectsArray = array_map(fn($p) => ProjectDTO::fromArray($p)->toArray(), $jiraData);
        $this->projectRepo->upsertProjects($user->id, $projectsArray);

        return $projectsArray;
    }
}