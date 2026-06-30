<?php

namespace App\Services\Sync;

use App\DTO\Project\ProjectDTO;
use App\Events\IssuesSync;
use App\Repositories\Interfaces\IssueOverdueInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Ping\ConnectJiraService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Log;

class SyncIssueService extends ConnectJiraService
{
    protected $syncRepo;
    protected $projectRepo;
    protected $issueOverdueRepo;
    protected $transformer;

    public function __construct(
        SyncIssueInterface    $syncRepo,
        ProjectInterface      $projectRepo,
        IssueOverdueInterface   $issueOverdueRepo,
        IssueTransformerService $transformer
    )
    {
        parent::__construct();
        $this->syncRepo = $syncRepo;
        $this->projectRepo = $projectRepo;
        $this->issueOverdueRepo = $issueOverdueRepo;
        $this->transformer = $transformer;
    }

    protected function fetchIssuesByJql(string $jql): Collection
    {
        $user = clone Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = $this->maxResults;
        $allIssues = [];

        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_11323,customfield_11306,status,created,customfield_10115,customfield_10108";

        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}&expand=changelog" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error'])) {
            return collect([]);
        }

        $allIssues = array_merge($allIssues, $firstData['issues'] ?? []);
        $total = $firstData['total'] ?? 0;

        if ($total <= $maxResults) {
            return collect($allIssues);
        }

        $client = $this->initClient($user);
        $generator = function () use ($user, $jql, $total, $maxResults, $fieldsParam) {
            for ($startAt = $maxResults; $startAt < $total; $startAt += $maxResults) {
                $url = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt={$startAt}&maxResults={$maxResults}&expand=changelog" . $fieldsParam;
                yield function () use ($user, $url) {
                    return $this->connectToJiraAsync($user, $url);
                };
            }
        };

        $pool = new Pool($client, $generator(), [
            'concurrency' => 50,
            'fulfilled' => function (Response $response) use (&$allIssues) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (isset($body['issues'])) {
                    $allIssues = array_merge($allIssues, $body['issues']);
                }
            },
            'rejected' => function (RequestException $reason) {
                Log::error("Jira Sync Page Failed: " . $reason->getMessage());
            },
        ]);

        $pool->promise()->wait();

        return collect($allIssues);
    }

    public function syncFullIssues(): void
    {
        $jql = 'issuetype IN (Bug, "Sub-task", Story, Milestone) AND status != Cancelled ORDER BY summary DESC, updated DESC';
        $this->saveAndProcess($this->fetchIssuesByJql($jql));
    }

    public function syncMonthIssues(): void
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $jql = "issuetype IN (Bug, \"Sub-task\", Story, Milestone) AND status != Cancelled AND created >= '{$startDate}' AND created <= '{$endDate}' ORDER BY created ASC";
        $this->saveAndProcess($this->fetchIssuesByJql($jql));
    }

    public function syncFromLastIssues(): void
    {
        $lastSyncTime = Carbon::parse($this->syncRepo->getLastSyncTime());
        $startOfMonth = $lastSyncTime->copy()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $jql = "issuetype IN (Bug, \"Sub-task\", Story, Milestone) AND status != Cancelled AND created >= '{$startOfMonth}' AND created <= '{$endDate}' ORDER BY created ASC";

        $this->saveAndProcess($this->fetchIssuesByJql($jql));
    }

    protected function saveAndProcess(Collection $rawIssues): void
    {
        if ($rawIssues->isEmpty()) {
            return;
        }

        $this->syncAndFetchProjects();

        $transformedIssues = $this->transformer->transformMany($rawIssues->toArray());
        $this->syncRepo->saveIssues($transformedIssues);

        $this->processOverdueDirectly($rawIssues);

        $lastSyncTime = $rawIssues->map(fn($issue) => $issue['fields']['created'] ?? null)->filter()->max();
        if ($lastSyncTime) {
            $this->syncRepo->updateSyncTime(Carbon::parse($lastSyncTime)->format('Y-m-d H:i:s'));
        }

        event(new IssuesSync(collect($transformedIssues)));
    }

    /**
     * Tách biệt logic xử lý lưu trữ Overdue sang bảng khác trực tiếp
     */
    protected function processOverdueDirectly(Collection $rawIssues): void
    {
        $targetTypes = ['Sub-task', 'Story', 'Milestone'];

        foreach ($rawIssues as $issueData) {
            $issueType = $issueData['fields']['issuetype']['name'] ?? null;

            if (!in_array($issueType, $targetTypes)) {
                continue;
            }

            try {
                $detailData = $this->transformer->transformSingle($issueData);

                $createdAtJira = $issueData['fields']['created'] ?? null;
                $detailData['period'] = $createdAtJira
                    ? Carbon::parse($createdAtJira)->format('m-Y')
                    : Carbon::now()->format('m-Y');

                $detailData['projectName'] = $issueData['fields']['project']['name'] ?? $detailData['project'] ?? null;

                $this->issueOverdueRepo->updateOrCreateIssue($detailData);

            } catch (Exception $e) {
                Log::error("Lỗi xử lý trực tiếp Overdue cho Key " . ($issueData['key'] ?? 'N/A') . ": " . $e->getMessage());
            }
        }
    }

    public function syncAndFetchProjects(): array
    {
        $user = Auth::user();
        $clonedUser = clone $user;
        if (base64_decode($clonedUser->jira_password, true) !== false) {
            try {
                $clonedUser->jira_password = Crypt::decryptString($clonedUser->jira_password);
            } catch (Exception $e) {
            }
        }

        $url = "/rest/api/2/project";
        $client = $this->initClient($clonedUser);

        try {
            $response = $client->getAsync($url)->wait();
            $jiraData = json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error("Jira Sync Projects Failed via Async-Wait: " . $e->getMessage());
            $jiraData = ['error' => $e->getMessage()];
        }

        if (!empty($jiraData['error']) || !is_array($jiraData)) {
            return $this->projectRepo->getProjectsJson($user->id);
        }

        $projectsArray = array_map(fn($p) => ProjectDTO::fromArray($p)->toArray(), $jiraData);
        $this->projectRepo->updateProjectsJson($user->id, $projectsArray);

        return $projectsArray;
    }
    
}