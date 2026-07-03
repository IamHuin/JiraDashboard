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
use Illuminate\Support\Facades\DB;
use Log;

class SyncIssueService extends ConnectJiraService
{
    protected $syncRepo;
    protected $projectRepo;
    protected $issueOverdueRepo;
    protected $transformer;

    // Biến tạm để lưu thời gian sync lớn nhất trong vòng lặp nhằm cập nhật sau cùng
    protected $latestCreatedTime = null;
    // Biến thu thập dữ liệu đã transform để bắn Event sau khi hoàn thành (nếu thực sự cần)
    protected $allTransformedIssuesForEvent = [];

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

    /**
     * Thay đổi cốt lõi: Không trả về Collection nữa, xử lý trực tiếp trong Pool để tiết kiệm RAM
     */
    protected function fetchAndProcessIssuesByJql(string $jql): void
    {
        // 1. Tắt Query Log để tránh Laravel tích lũy RAM qua các câu lệnh SQL
        DB::disableQueryLog();
        set_time_limit(0);

        $user = clone Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = $this->maxResults ?? 100; // Đảm bảo maxResults tầm 50-100
        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_11323,customfield_11306,status,created,customfield_10108,customfield_10115,customfield_10108,subtasks";
        // Đồng bộ sẵn Project trước để tránh trong Loop gọi đi gọi lại
        $this->syncAndFetchProjects();

        // Reset các biến tracking
        $this->latestCreatedTime = null;
        $this->allTransformedIssuesForEvent = [];

        // Lấy trang đầu tiên để biết tổng số lượng (Total)
        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}&expand=changelog" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error']) || empty($firstData['issues'])) {
            return;
        }

        // Xử lý ngay trang đầu tiên
        $this->chunkProcess($firstData['issues']);
        $total = $firstData['total'] ?? 0;

        // Giải phóng biến trang đầu
        unset($firstData);
        gc_collect_cycles();

        if ($total <= $maxResults) {
            $this->finalizeSync();
            return;
        }

        // Khởi tạo Pool cho các trang còn lại
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
            'concurrency' => 10, // Hạ concurrency xuống một chút (khoảng 10-20) để tránh nghẽn luồng xử lý DB
            'fulfilled' => function (Response $response) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (!empty($body['issues'])) {
                    // XỬ LÝ CUỐN CHIẾU: Cứ có data của page này là lưu vào DB luôn
                    $this->chunkProcess($body['issues']);
                }

                // GIẢI PHÓNG BỘ NHỚ NGAY LẬP TỨC
                unset($body, $response);
                gc_collect_cycles();
            },
            'rejected' => function (RequestException $reason) {
                Log::error("Jira Sync Page Failed: " . $reason->getMessage());
            },
        ]);

        $pool->promise()->wait();

        // Hoàn tất cập nhật thời gian đồng bộ và bắn Event
        $this->finalizeSync();
    }

    /**
     * Hàm xử lý cục bộ cho từng Chunk dữ liệu (Ví dụ: 100 dòng một lúc)
     */
    protected function chunkProcess(array $rawIssuesChunk): void
    {
        $collectionChunk = collect($rawIssuesChunk);
        // 1. Transform và Save Issues
        $transformedIssues = $this->transformer->transformMany($rawIssuesChunk);
        $this->syncRepo->saveIssues($transformedIssues);

        // Gom một phần dữ liệu lại để bắn Event (nếu Event thực sự cần truyền data, 
        // tuy nhiên nếu 30k issues nhét vào Event vẫn có thể gây tốn RAM, cân nhắc chỉ truyền số lượng)
        $this->allTransformedIssuesForEvent = array_merge($this->allTransformedIssuesForEvent, $transformedIssues);

        // 2. Xử lý Overdue trực tiếp
        $this->processOverdueDirectly($collectionChunk);

        // 3. Tìm thời gian max của hàng đợi này
        $maxCreated = $collectionChunk->map(fn($issue) => $issue['fields']['created'] ?? null)->filter()->max();
        if ($maxCreated && (is_null($this->latestCreatedTime) || $maxCreated > $this->latestCreatedTime)) {
            $this->latestCreatedTime = $maxCreated;
        }

        // Xóa biến vùng nhớ cục bộ
        unset($transformedIssues, $collectionChunk);
    }

    /**
     * Hàm kết thúc, cập nhật thời gian và Event
     */
    protected function finalizeSync(): void
    {
        if ($this->latestCreatedTime) {
            $this->syncRepo->updateSyncTime(Carbon::parse($this->latestCreatedTime)->format('Y-m-d H:i:s'));
        }

        if (!empty($this->allTransformedIssuesForEvent)) {
            event(new IssuesSync(collect($this->allTransformedIssuesForEvent)));
        }

        // Clear sạch sẽ bộ nhớ sau cùng
        $this->allTransformedIssuesForEvent = [];
        gc_collect_cycles();
    }

    public function syncFullIssues(): void
    {
        $jql = 'issuetype IN (Bug, "Sub-task", Story, Milestone) AND status != Cancelled ORDER BY summary DESC, updated DESC';
        $this->fetchAndProcessIssuesByJql($jql);
    }

    public function syncFromLastIssues(): void
    {
        $lastSyncTime = Carbon::parse($this->syncRepo->getLastSyncTime());
        $startOfMonth = $lastSyncTime->copy()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $jql = "issuetype IN (Bug, \"Sub-task\", Story, Milestone) AND status != Cancelled AND End date >= '{$startOfMonth}' AND End date <= '{$endDate}' ORDER BY created ASC";

        $this->fetchAndProcessIssuesByJql($jql);
    }

    /**
     * Tách biệt logic xử lý lưu trữ Overdue sang bảng khác trực tiếp
     */
    protected function processOverdueDirectly(Collection $rawIssues): void
    {
        $targetTypes = ['Sub-task', 'Story', 'Milestone'];
        $bulkOverdueData = [];

        foreach ($rawIssues as $issueData) {
            $issueType = $issueData['fields']['issuetype']['name'] ?? null;

            if (!in_array($issueType, $targetTypes)) {
                continue;
            }

            try {
                $detailData = $this->transformer->transformSingle($issueData);

                $createdAtJira = $issueData['fields']['created'] ?? null;
                $period = $createdAtJira
                    ? Carbon::parse($createdAtJira)->format('m-Y')
                    : Carbon::now()->format('m-Y');

                $bulkOverdueData[] = [
                    'key'          => $issueData['key'] ?? null,
                    'period'       => $period,
                    'project_name' => $issueData['fields']['project']['name'] ?? $detailData['project'] ?? null,
                    'summary'      => $issueData['fields']['summary'] ?? null,
                    'issuetype'    => $issueType,
                    'assignee'     => $detailData['assignee'] ?? null,
                    'enddate'      => $detailData['enddate'] ?? null,
                    'status'       => $detailData['status'] ?? null,
                    'statusText' => $detailData['statusText'] ?? null,
                    'statusLogWork' => $detailData['statusLogWork'] ?? null,
                    'statusTextLogWork' => $detailData['statusTextLogWork'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

            } catch (Exception $e) {
                Log::error("Lỗi chuẩn bị data Overdue cho Key " . ($issueData['key'] ?? 'N/A') . ": " . $e->getMessage());
            }
        }

        if (!empty($bulkOverdueData)) {
            $this->issueOverdueRepo->upsertIssues($bulkOverdueData);
        }

        unset($bulkOverdueData);
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