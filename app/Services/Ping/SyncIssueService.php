<?php

namespace App\Services\Ping;

use App\DTO\Project\ProjectDTO;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleBugRatioService;
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
    protected $jiraRepo;
    protected $jiraHandleBug;
    protected $projectRepo;

    public function __construct(SyncIssueInterface $jiraRepo, HandleBugRatioService $jiraHandleBug, ProjectInterface $projectRepo)
    {
        parent::__construct();
        $this->jiraRepo = $jiraRepo;
        $this->jiraHandleBug = $jiraHandleBug;
        $this->projectRepo = $projectRepo;
    }

    /**
     * Bắn JQL kéo Issues bất đồng bộ bằng Guzzle Pool
     */
    protected function fetchIssuesByJql(string $jql): Collection
    {
        $user = Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = $this->maxResults;
        $allIssues = [];

        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_xxx,customfield_yyy,status,created";

        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error'])) {
            return collect([]);
        }

        $allIssues = array_merge($allIssues, $this->parseIssues($firstData['issues'] ?? []));
        $total = $firstData['total'] ?? 0;

        if ($total <= $maxResults) {
            return collect($allIssues);
        }

        $client = $this->initClient($user);
        $generator = function () use ($user, $jql, $total, $maxResults, $fieldsParam) {
            for ($startAt = $maxResults; $startAt < $total; $startAt += $maxResults) {
                $url = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt={$startAt}&maxResults={$maxResults}" . $fieldsParam;
                yield function () use ($user, $url) {
                    return $this->connectToJiraAsync($user, $url);
                };
            }
        };

        $pool = new Pool($client, $generator(), [
            'concurrency' => 7,

            'fulfilled' => function (Response $response) use (&$allIssues) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (isset($body['issues'])) {
                    $parsed = $this->parseIssues($body['issues']);
                    $allIssues = array_merge($allIssues, $parsed);
                }
            },

            'rejected' => function (RequestException $reason) {
                Log::error("Jira Sync Page Failed: " . $reason->getMessage());
            },
        ]);

        $pool->promise()->wait();

        return collect($allIssues);
    }

    /**
     * Parse mảng thô từ Jira sang mảng nội bộ sạch
     */
    protected function parseIssues(array $issues): array
    {
        return collect($issues)->map(function ($issue) {
            return [
                'key' => $issue['key'],
                'projectKey' => $issue['fields']['project']['key'] ?? null,
                'projectName' => $issue['fields']['project']['name'] ?? null,
                'summary' => $issue['fields']['summary'] ?? null,
                'issuetype' => $issue['fields']['issuetype']['name'] ?? null,
                'assignee' => $issue['fields']['assignee']['name'] ?? null,
                'causer' => $issue['fields']['customfield_11321']['name'] ?? null,
                'ulnl' => $issue['fields']['customfield_xxx'] ?? null,
                'slsx' => $issue['fields']['customfield_yyy'] ?? null,
                'status' => $issue['fields']['status']['name'] ?? null,
                'created' => isset($issue['fields']['created'])
                    ? Carbon::parse($issue['fields']['created'])->format('Y-m-d H:i:s')
                    : null,
            ];
        })->toArray();
    }

    public function syncFullIssues(): void
    {
        $jql = "issuetype in (Bug, Sub-task) ORDER BY summary DESC, updated DESC";
        $issues = $this->fetchIssuesByJql($jql);

        $this->saveAndProcess($issues);
    }

    public function syncMonthIssues(): void
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $jql = "(issuetype = Bug OR (issuetype = Sub-task AND status = Done)) 
        AND created >= '{$startDate}' AND created <= '{$endDate}' 
        ORDER BY created ASC";

        $issues = $this->fetchIssuesByJql($jql);
        $this->saveAndProcess($issues);
    }

    public function syncFromLastIssues(): void
    {
        $lastSyncTime = Carbon::parse($this->jiraRepo->getLastSyncTime());
        $startOfMonth = $lastSyncTime->copy()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $jql = "(issuetype = Bug OR (issuetype = Sub-task AND status = Done)) 
        AND created >= '{$startOfMonth}' AND created <= '{$endDate}' 
        ORDER BY created ASC";

        $issues = $this->fetchIssuesByJql($jql);

        $this->saveAndProcess($issues);
    }

    /**
     * Đồng bộ gốc từ API Jira - Quét trọn vẹn danh sách dự án (An toàn, đủ Avatar)
     */
    public function syncAndFetchProjects(): array
    {
        $user = Auth::user();

        // Tạo bản clone để xử lý password, tránh lỗi đúp decrypt như đã phân tích
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

        $projectsArray = array_map(function ($projectRaw) {
            return ProjectDTO::fromArray($projectRaw)->toArray();
        }, $jiraData);

        $this->projectRepo->updateProjectsJson($user->id, $projectsArray);

        return $projectsArray;
    }

    /**
     * Xử lý lưu DB và tính toán báo cáo thống kê
     */
    protected function saveAndProcess(Collection $issues): void
    {
        if ($issues->isEmpty()) {
            return;
        }

        $this->syncAndFetchProjects();

        $this->jiraRepo->saveIssues($issues->toArray());

        $lastSyncTime = $issues->max('created');
        $this->jiraRepo->updateSyncTime($lastSyncTime);

        $stats = [];

        $issuesByPeriod = $issues->groupBy(function ($issue) {
            return Carbon::parse($issue['created'])->startOfMonth()->format('Y-m-d');
        });

        foreach ($issuesByPeriod as $period => $periodIssues) {
            $bugPercent = $this->jiraHandleBug->countBugPercent($periodIssues);
            foreach ($bugPercent as $stat) {
                $userName = $stat['user_name'];
                $userIssues = $periodIssues->filter(fn($i) => $i['causer'] === $userName || $i['assignee'] === $userName);
                $ulnl = $userIssues->sum('ulnl');
                $slsx = $userIssues->sum('slsx');
                $ratio = $ulnl > 0 ? round(($slsx / $ulnl) * 100, 2) : 0;

                $stats[] = [
                    'project_name' => $userIssues->first()['projectName'] ?? null,
                    'user_name' => $userName,
                    'bug_count' => $stat['total_bugs'],
                    'bug_percent' => $stat['bug_percent'],
                    'subtask_count' => $stat['total_subtasks'],
                    'ulnl_count' => $ulnl,
                    'slsx_count' => $slsx,
                    'slsx_vs_ulnl_ratio' => $ratio,
                    'period' => $period,
                ];
            }
        }

        $this->jiraRepo->saveUserStats($stats);
    }
}