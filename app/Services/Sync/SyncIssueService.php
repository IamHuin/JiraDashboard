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
        $user = Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = $this->maxResults;
        $allIssues = [];
        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_xxx,customfield_11306,status,created,customfield_10115";

        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error'])) {
            return collect([]);
        }

        $allIssues = array_merge($allIssues, $this->transformer->transformMany($firstData['issues'] ?? []));
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
                    $parsed = $this->transformer->transformMany($body['issues']);
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

    public function syncFullIssues(): void
    {
        $jql = 'issuetype IN (Bug, "Sub-task") AND status != Cancelled ORDER BY summary DESC, updated DESC';
        $this->saveAndProcess($this->fetchIssuesByJql($jql));
    }

    public function syncMonthIssues(): void
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $jql = "issuetype IN (Bug, \"Sub-task\") AND status != Cancelled AND created >= '{$startDate}' AND created <= '{$endDate}' ORDER BY created ASC";

        $this->saveAndProcess($this->fetchIssuesByJql($jql));
    }

    public function syncFromLastIssues(): void
    {
        $lastSyncTime = Carbon::parse($this->syncRepo->getLastSyncTime());
        $startOfMonth = $lastSyncTime->copy()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $jql = "issuetype IN (Bug, \"Sub-task\") AND status != Cancelled AND created >= '{$startOfMonth}' AND created <= '{$endDate}' ORDER BY created ASC";

        $this->saveAndProcess($this->fetchIssuesByJql($jql));
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

    public function getIssueDetail(string $key): array
    {
        $user = Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $url = "/rest/api/2/issue/" . urlencode($key) . "?expand=changelog";
        $issueData = $this->connectToJira($user, $url);

        if (!empty($issueData['error']) || isset($issueData['errorMessages'])) {
            return ['error' => $issueData['errorMessages'] ?? 'Issue not found'];
        }

        return $this->transformer->transformSingle($issueData);
    }

    public function processOverdueFromSync(Collection $issues): void
    {
        $targetTypes = ['Sub-task', 'Story', 'Milestone'];
        $filteredIssues = $issues->filter(fn($i) => in_array($i['issuetype'] ?? null, $targetTypes));
        if ($filteredIssues->isEmpty()) {
            return;
        }

        foreach ($filteredIssues as $issue) {
            try {
                $key = $issue['key'] ?? null;
                if (!$key) continue;

                $period = isset($issue['created'])
                    ? Carbon::parse($issue['created'])->format('m-Y')
                    : Carbon::now()->format('m-Y');
                $detailData = $this->getIssueDetail($key);

                if (isset($detailData['error'])) {
                    Log::error("Call API Detail thất bại cho Key {$key}: " . $detailData['error']);
                    continue;
                }

                $detailData['period'] = $period;
                $detailData['projectName'] = $issue['projectName'] ?? $detailData['project'] ?? null;

                $this->issueOverdueRepo->updateOrCreateIssue($detailData);

            } catch (Exception $e) {
                Log::error("Lỗi xử lý lưu chi tiết quá hạn cho issue {$issue['key']}: " . $e->getMessage());
            }
        }
    }

    protected function saveAndProcess(Collection $issues): void
    {
        if ($issues->isEmpty()) {
            return;
        }

        $this->syncAndFetchProjects();
        $this->syncRepo->saveIssues($issues->toArray());

        $lastSyncTime = $issues->max('created');
        $this->syncRepo->updateSyncTime($lastSyncTime);

//        $this->processOverdueFromSync($issues);

        event(new IssuesSync($issues));
    }
}