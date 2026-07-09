<?php

namespace App\Jobs;

use App\Enums\SyncStatus;
use App\Events\IssuesSync;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Log;

class ProcessMilestonesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(): void
    {
        $requiredMilestones = [
            'Gửi Kế hoạch PYC', 'Gửi Tài liệu giải pháp để Review/Xác nhận', 'Gửi ULNL sơ bộ',
            'Chốt ULNL', 'Bàn giao', 'Giải pháp Done', 'Demo/FI nội bộ', 'Test Done', 'Dev Done'
        ];

        $rawMilestoneIssues = DB::table('jira_issues')
            ->where('issuetype', 'Milestone')
            ->where('updated_at', '>=', now()->subHours(2))
            ->get();

        if ($rawMilestoneIssues->isNotEmpty()) {
            $sortedMilestones = collect($requiredMilestones)->sortByDesc(fn($m) => strlen($m))->toArray();
            $milestonePattern = '/' . implode('|', array_map(fn($m) => preg_quote($m, '/'), $sortedMilestones)) . '/i';
            $collectedMilestonesRaw = [];

            foreach ($rawMilestoneIssues as $issue) {
                $summary = trim($issue->summary ?? '');
                $ticketCode = null; $milestoneName = null; $isException = false; $suffixText = '';

                $hasMilestone = preg_match($milestonePattern, $summary, $milestoneMatches);
                if ($hasMilestone) {
                    $matchedName = trim($milestoneMatches[0]);
                    $milestoneName = collect($requiredMilestones)->first(fn($m) => strcasecmp($m, $matchedName) === 0) ?? $matchedName;
                    $remainingSummary = str_ireplace($matchedName, '', $summary);
                } else {
                    $isException = true;
                    $milestoneName = preg_match('/^([^-–—]+)/ui', $summary, $exMatches) ? trim($exMatches[1]) : 'Mốc không rõ tên';
                    $remainingSummary = $summary;
                }

                $matchedTextForSuffix = null;
                if (preg_match('/([A-Z0-9]+-\d+)/ui', $remainingSummary, $ticketMatches)) {
                    $ticketCode = strtoupper(trim($ticketMatches[1]));
                    $matchedTextForSuffix = $ticketMatches[1];
                } elseif (preg_match('/(?<![A-Z0-9])(\d+)(?![A-Z0-9])/ui', $remainingSummary, $ticketMatches)) {
                    $ticketCode = trim($ticketMatches[1]);
                    $matchedTextForSuffix = $ticketMatches[1];
                }

                if ($ticketCode && $matchedTextForSuffix) {
                    $pos = mb_strpos($remainingSummary, $matchedTextForSuffix);
                    if ($pos !== false) {
                        $rawSuffix = mb_substr($remainingSummary, $pos + mb_strlen($matchedTextForSuffix));
                        $suffixText = trim(preg_replace('/^[\s\-_–—\/|:()]+/u', '', $rawSuffix));
                    }
                }

                if ($ticketCode) {
                    $period = !empty($issue->created_at_jira) ? Carbon::parse($issue->created_at_jira)->format('m-Y') : Carbon::now()->format('m-Y');
                    $collectedMilestonesRaw[] = [
                        'ticket_code'    => strtoupper($ticketCode),
                        'project_name'   => $issue->project_name ?? 'Dự án không tên',
                        'milestone_name' => $milestoneName,
                        'period'         => $period,
                        'is_exception'   => $isException,
                        'suffix_text'    => $suffixText
                    ];
                }
            }

            $this->storeMilestoneReports($collectedMilestonesRaw, $requiredMilestones);
        }

        $allSyncedIssues = DB::table('jira_issues')
            ->where('updated_at', '>=', now()->subHours(2))
            ->get()
            ->map(fn($issue) => [
                'key'         => $issue->key,
                'projectName' => $issue->project_name,
                'summary'     => $issue->summary,
                'issuetype'   => $issue->issuetype,
                'assignee'    => $issue->assignee,
                'causer'      => $issue->causer,
                'causer_category' => $issue->causer_category,
                'ulnl'        => $issue->ulnl,
                'slsx'        => $issue->slsx,
                'status'      => $issue->status,
                'created_at'  => $issue->created_at_jira,
                'enddate'     => $issue->end_date_jira,
            ]);

        if ($allSyncedIssues->isNotEmpty()) {
            $eventInstance = new IssuesSync($allSyncedIssues);
            $eventInstance->syncingUser = $this->user;

            event($eventInstance);
        }

        Cache::put("jira-sync-status:{$this->user->id}:full", SyncStatus::DONE->value, now()->addMinutes(30));
        Cache::put("jira-sync-status:{$this->user->id}:last", SyncStatus::DONE->value, now()->addMinutes(30));

        Log::info("Hệ thống đồng bộ + tính toán toán bộ chỉ số hoàn tất 100% cho User: " . $this->user->id);
    }

    protected function storeMilestoneReports(array $rawMilestones, array $requiredMilestones): void
    {
        $grouped = collect($rawMilestones)->groupBy(fn($item) => $item['period'] . '|||' . $item['ticket_code']);
        $bulkReportData = []; $processedTickets = [];

        foreach ($grouped as $groupKey => $issues) {
            $issuesCollection = collect($issues);
            $period = (string) $issuesCollection->first()['period'];
            $ticketCode = (string) $issuesCollection->first()['ticket_code'];
            $projectName = $issuesCollection->first()['project_name'] ?? 'Dự án không tên';
            $sharedSuffix = $issuesCollection->where('suffix_text', '!=', '')->pluck('suffix_text')->first() ?? '';

            $processedTickets[] = ['period' => $period, 'ticket_code' => $ticketCode];

            $currentStandardMilestones = $issuesCollection->where('is_exception', false)->pluck('milestone_name')->unique()->toArray();
            $currentExceptionMilestones = $issuesCollection->where('is_exception', true)->unique(fn($item) => $item['milestone_name'] . $item['suffix_text']);

            $missingMilestones = array_values(array_diff($requiredMilestones, $currentStandardMilestones));

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

        if (!empty($processedTickets)) {
            DB::transaction(function () use ($processedTickets, $bulkReportData) {
                foreach ($processedTickets as $ticket) {
                    DB::table('jira_milestones')
                        ->where('period', (string) $ticket['period'])
                        ->where('ticket_code', (string) $ticket['ticket_code'])
                        ->delete();
                }

                if (!empty($bulkReportData)) {
                    DB::table('jira_milestones')->upsert(
                        $bulkReportData,
                        ['period', 'ticket_code', 'report_type', 'milestone_name'],
                        ['project_name', 'updated_at']
                    );
                }
            });
        }
    }
}