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

    protected $latestCreatedTime = null;
    protected $allTransformedIssuesForEvent = [];

    // Biến tích lũy milestone thô từ tất cả các trang
    protected array $collectedMilestonesRaw = [];

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

    protected function fetchAndProcessIssuesByJql(string $jql): void
    {
        DB::disableQueryLog();
        set_time_limit(0);

        $user = clone Auth::user();
        $user->jira_password = Crypt::decryptString($user->jira_password);

        $maxResults = $this->maxResults ?? 100;
        $fieldsParam = "&fields=project,summary,issuetype,assignee,customfield_11321,customfield_11323,customfield_11306,status,created,customfield_10108,customfield_10115,subtasks";

        $this->syncAndFetchProjects();

        $this->latestCreatedTime = null;
        $this->allTransformedIssuesForEvent = [];
        $this->collectedMilestonesRaw = [];

        $firstUrl = "/rest/api/2/search?jql=" . urlencode($jql) . "&startAt=0&maxResults={$maxResults}&expand=changelog" . $fieldsParam;
        $firstData = $this->connectToJira($user, $firstUrl);

        if (!empty($firstData['error']) || empty($firstData['issues'])) {
            return;
        }

        $this->chunkProcess($firstData['issues']);
        $total = $firstData['total'] ?? 0;

        unset($firstData);
        gc_collect_cycles();

        if ($total <= $maxResults) {
            $this->finalizeSync();
            return;
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
            'concurrency' => 10,
            'fulfilled' => function (Response $response) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (!empty($body['issues'])) {
                    $this->chunkProcess($body['issues']);
                }
                unset($body, $response);
                gc_collect_cycles();
            },
            'rejected' => function (RequestException $reason) {
                Log::error("Jira Sync Page Failed: " . $reason->getMessage());
            },
        ]);

        $pool->promise()->wait();
        $this->finalizeSync();
    }

    protected function chunkProcess(array $rawIssuesChunk): void
    {
        $collectionChunk = collect($rawIssuesChunk);

        $transformedIssues = $this->transformer->transformMany($rawIssuesChunk);
        $this->syncRepo->saveIssues($transformedIssues);

        $this->allTransformedIssuesForEvent = array_merge($this->allTransformedIssuesForEvent, $transformedIssues);
        $this->processOverdueDirectly($collectionChunk);
        $this->collectMilestonesRawData($collectionChunk);

        $maxCreated = $collectionChunk->map(fn($issue) => $issue['fields']['created'] ?? null)->filter()->max();
        if ($maxCreated && (is_null($this->latestCreatedTime) || $maxCreated > $this->latestCreatedTime)) {
            $this->latestCreatedTime = $maxCreated;
        }

        unset($transformedIssues, $collectionChunk);
    }

    protected function collectMilestonesRawData(Collection $rawIssues): void
    {
        $requiredMilestones = [
            'Gửi Kế hoạch PYC', 'Gửi Tài liệu giải pháp để Review/Xác nhận', 'Gửi ULNL sơ bộ',
            'Chốt ULNL', 'Bàn giao', 'Giải pháp Done', 'Demo/FI nội bộ', 'Test Done', 'Dev Done'
        ];

        $sortedMilestones = collect($requiredMilestones)->sortByDesc(fn($m) => strlen($m))->toArray();
        $milestonePattern = '/' . implode('|', array_map(fn($m) => preg_quote($m, '/'), $sortedMilestones)) . '/i';

        foreach ($rawIssues as $issueData) {
            if (($issueData['fields']['issuetype']['name'] ?? null) !== 'Milestone') {
                continue;
            }

            try {
                $summary = trim($issueData['fields']['summary'] ?? '');
                $ticketCode = null;
                $milestoneName = null;
                $isException = false;
                $suffixText = '';

                $hasMilestone = preg_match($milestonePattern, $summary, $milestoneMatches);

                if ($hasMilestone) {
                    $matchedName = trim($milestoneMatches[0]);
                    $milestoneName = collect($requiredMilestones)->first(fn($m) => strcasecmp($m, $matchedName) === 0) ?? $matchedName;
                    $remainingSummary = str_ireplace($matchedName, '', $summary);
                } else {
                    $isException = true;
                    if (preg_match('/^([^-–—]+)/ui', $summary, $exMatches)) {
                        $milestoneName = trim($exMatches[1]);
                    } else {
                        $milestoneName = 'Mốc không rõ tên';
                    }
                    $remainingSummary = $summary;
                }

                $matchedTextForSuffix = null;

                // 1. ƯU TIÊN 1: Tìm dạng có cả chữ và số kết nối gạch ngang (Ví dụ: "VICPYC-3370", "PYC-3370")
                if (preg_match('/([A-Z0-9]+-\d+)/ui', $remainingSummary, $ticketMatches)) {
                    $ticketCode = strtoupper(trim($ticketMatches[1]));
                    $matchedTextForSuffix = $ticketMatches[1];
                }
                // ƯU TIÊN 2: Nếu không tồn tại định dạng chữ-số, mới bắt số độc lập (Ví dụ: "3370")
                elseif (preg_match('/(?<![A-Z0-9])(\d+)(?![A-Z0-9])/ui', $remainingSummary, $ticketMatches)) {
                    $ticketCode = trim($ticketMatches[1]);
                    $matchedTextForSuffix = $ticketMatches[1];
                }

                // 2. BÓC TÁCH PHẦN ĐUÔI ĐẰNG SAU MÃ PHIẾU VỪA TÌM ĐƯỢC
                if ($ticketCode && $matchedTextForSuffix) {
                    $pos = mb_strpos($remainingSummary, $matchedTextForSuffix);
                    if ($pos !== false) {
                        $rawSuffix = mb_substr($remainingSummary, $pos + mb_strlen($matchedTextForSuffix));
                        // ĐÃ SỬA: Loại bỏ \[\] để KHÔNG xóa dấu mở ngoặc vuông [ ở đầu chuỗi đuôi nữa
                        $suffixText = trim(preg_replace('/^[\s\-_–—\/|:()]+/u', '', $rawSuffix));
                    }
                }

                // 3. CHỈ LƯU NẾU TÌM THẤY SỐ PHIẾU HỢP LỆ (Bỏ qua mốc thiếu thông tin số hiệu)
                if ($ticketCode) {
                    $createdAtJira = $issueData['fields']['created'] ?? null;
                    $period = $createdAtJira ? Carbon::parse($createdAtJira)->format('m-Y') : Carbon::now()->format('m-Y');

                    $this->collectedMilestonesRaw[] = [
                        'ticket_code'    => strtoupper($ticketCode),
                        'project_name'   => $issueData['fields']['project']['name'] ?? 'Dự án không tên',
                        'milestone_name' => $milestoneName,
                        'period'         => $period,
                        'is_exception'   => $isException,
                        'suffix_text'    => $suffixText
                    ];
                }

            } catch (Exception $e) {
                Log::error("Lỗi trích xuất Milestone: " . $e->getMessage());
            }
        }
    }

    protected function processMilestones(array $rawMilestones): array
    {
        $requiredMilestones = [
            'Gửi Kế hoạch PYC',
            'Gửi Tài liệu giải pháp để Review/Xác nhận',
            'Gửi ULNL sơ bộ',
            'Chốt ULNL',
            'Bàn giao',
            'Giải pháp Done',
            'Demo/FI nội bộ',
            'Test Done',
            'Dev Done'
        ];

        $grouped = collect($rawMilestones)->groupBy(['period', 'ticket_code']);
        $bulkReportData = [];
        $processedTickets = [];

        foreach ($grouped as $period => $tickets) {
            foreach ($tickets as $ticketCode => $issues) {
                $issuesCollection = collect($issues);
                $projectName = $issuesCollection->first()['project_name'] ?? 'Dự án không tên';

                // Tìm kiếm một "tên đuôi" đại diện hợp lệ nhất của phiếu để dùng chung cho các mốc thiếu
                $sharedSuffix = $issuesCollection->where('suffix_text', '!=', '')->pluck('suffix_text')->first() ?? '';

                $processedTickets[] = [
                    'period'      => $period,
                    'ticket_code' => $ticketCode
                ];

                $currentStandardMilestones = $issuesCollection->where('is_exception', false)->pluck('milestone_name')->unique()->toArray();
                $currentExceptionMilestones = $issuesCollection->where('is_exception', true)->unique(fn($item) => $item['milestone_name'] . $item['suffix_text']);

                $missingMilestones = array_values(array_diff($requiredMilestones, $currentStandardMilestones));

                // Ghi nhận mốc thiếu (MISSING) đính kèm tên đuôi kế thừa
                foreach ($missingMilestones as $missingName) {
                    $fullName = !empty($sharedSuffix) ? $missingName . ' - ' . $sharedSuffix : $missingName;

                    $bulkReportData[] = [
                        'period'         => $period,
                        'project_name'   => $projectName,
                        'ticket_code'    => $ticketCode,
                        'report_type'    => 'MISSING',
                        'milestone_name' => mb_substr($fullName, 0, 255),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }

                // Ghi nhận mốc sai định dạng (EXCEPTION)
                foreach ($currentExceptionMilestones as $excItem) {
                    $excName = $excItem['milestone_name'];
                    $excSuffix = $excItem['suffix_text'] ?: $sharedSuffix;
                    $fullName = !empty($excSuffix) ? $excName . ' - ' . $excSuffix : $excName;

                    $bulkReportData[] = [
                        'period'         => $period,
                        'project_name'   => $projectName,
                        'ticket_code'    => $ticketCode,
                        'report_type'    => 'EXCEPTION',
                        'milestone_name' => mb_substr($fullName, 0, 255),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
            }
        }

        if (!empty($processedTickets) || !empty($bulkReportData)) {
            DB::transaction(function () use ($processedTickets, $bulkReportData) {

                foreach ($processedTickets as $ticket) {
                    DB::table('jira_milestones')
                        ->where('period', $ticket['period'])
                        ->where('ticket_code', $ticket['ticket_code'])
                        ->delete();
                }

                if (!empty($bulkReportData)) {
                    Log::info("Đang cập nhật báo cáo Milestone phẳng kèm Context vào DB: " . count($bulkReportData) . " dòng.");
                    DB::table('jira_milestones')->upsert(
                        $bulkReportData,
                        ['period', 'ticket_code', 'report_type', 'milestone_name'],
                        ['project_name', 'updated_at']
                    );
                }
            });
        }

        return [
            'status' => 'success',
            'processed_count' => count($bulkReportData)
        ];
    }

    protected function finalizeSync(): void
    {
        if ($this->latestCreatedTime) {
            $this->syncRepo->updateSyncTime(Carbon::parse($this->latestCreatedTime)->format('Y-m-d H:i:s'));
        }

        if (!empty($this->collectedMilestonesRaw)) {
            $this->processMilestones($this->collectedMilestonesRaw);
        }

        if (!empty($this->allTransformedIssuesForEvent)) {
            event(new IssuesSync(collect($this->allTransformedIssuesForEvent)));
        }

        $this->allTransformedIssuesForEvent = [];
        $this->collectedMilestonesRaw = [];
        gc_collect_cycles();
    }

    public function syncFullIssues(): void
    {
        $jql = 'issuetype IN (Bug, "Sub-task", Story, Milestone) AND status != Cancelled ORDER BY summary DESC, updated DESC';
        $this->fetchAndProcessIssuesByJql($jql);
    }

    public function syncFromLastIssues(): void
    {
        $startOfMonth = '2026-06-01';
        $endDate = '2026-06-30';
        $jql = "issuetype IN (Bug, \"Sub-task\", Story, Milestone) AND status != Cancelled AND created >= '{$startOfMonth}' AND created <= '{$endDate}' ORDER BY created ASC";

        $this->fetchAndProcessIssuesByJql($jql);
    }

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
                $createdAtJira = $issueData['fields']['customfield_10108'] ?? null;
                $period = $createdAtJira ? Carbon::parse($createdAtJira)->format('m-Y') : Carbon::now()->format('m-Y');

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
                Log::error("Lỗi Overdue: " . $e->getMessage());
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