<?php

namespace App\Services\Sync;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IssueTransformerService
{
    /**
     * Biến đổi danh sách Issue thô từ danh sách JQL
     */
    public function transformMany(array $issues): array
    {
        return collect($issues)->map(function ($issue) {
            return [
                'key'             => $issue['key'],
                'projectKey'      => $issue['fields']['project']['key'] ?? null,
                'projectName'     => $issue['fields']['project']['name'] ?? null,
                'summary'         => $issue['fields']['summary'] ?? null,
                'issuetype'       => $issue['fields']['issuetype']['name'] ?? null,
                'assignee'        => $issue['fields']['assignee']['name'] ?? null,
                'causer'          => $issue['fields']['customfield_11321']['name'] ?? null,
                'causer_category' => $issue['fields']['customfield_10115']['value'] ?? null,
                'ulnl'            => $issue['fields']['customfield_xxx'] ?? null,
                'slsx'            => $issue['fields']['customfield_11306'] ?? null,
                'status'          => $issue['fields']['status']['name'] ?? null,
                'created'         => isset($issue['fields']['created'])
                    ? Carbon::parse($issue['fields']['created'])->format('Y-m-d H:i:s')
                    : null,
            ];
        })->toArray();
    }

    /**
     * Biến đổi chi tiết single issue từ API Detail và tính toán Overdue
     */
    public function transformSingle(array $issue): array
    {
        $fields = $issue['fields'] ?? [];
        $key = $issue['key'] ?? null;
        $currentStatus = $fields['status']['name'] ?? null;
        $endDateRaw = $fields['customfield_10108'] ?? null;

        $endDate = $endDateRaw ? Carbon::parse($endDateRaw, 'Asia/Ho_Chi_Minh')->endOfDay() : null;
        $doneCreatedAt = $this->getLatestDoneDate($currentStatus, $issue['changelog']['histories'] ?? []);

        $finalStatus = $this->calculateFinalStatus($key, $endDate, $doneCreatedAt, $currentStatus);

        return [
            'key'       => $key,
            'summary'   => $fields['summary'] ?? null,
            'project'   => $fields['project']['name'] ?? null,
            'enddate'   => $endDateRaw,
            'status'    => $finalStatus,
            'issueType' => $fields['issuetype']['name'] ?? null,
            'assignee'  => $fields['assignee']['displayName'] ?? null,
        ];
    }

    /**
     * Tìm thời gian bấm nút DONE cuối cùng từ lịch sử thay đổi (Changelog)
     */
    private function getLatestDoneDate(?string $currentStatus, array $histories): ?Carbon
    {
        if (strtoupper($currentStatus ?? '') !== 'DONE') {
            return null;
        }

        $doneCreatedAt = null;

        foreach ($histories as $log) {
            $hasDoneTransition = false;

            if (isset($log['items'])) {
                foreach ($log['items'] as $item) {
                    if (isset($item['field']) && strtolower($item['field']) === 'status' && strtoupper($item['toString'] ?? '') === 'DONE') {
                        $hasDoneTransition = true;
                        break;
                    }
                }
            }

            if ($hasDoneTransition && isset($log['created'])) {
                $logCreated = Carbon::parse($log['created'], 'Asia/Ho_Chi_Minh');
                if ($doneCreatedAt === null || $logCreated->greaterThan($doneCreatedAt)) {
                    $doneCreatedAt = $logCreated;
                }
            }
        }

        return $doneCreatedAt;
    }

    /**
     * Logic lõi tính toán Overdue trạng thái
     */
    private function calculateFinalStatus(?string $key, ?Carbon $endDate, ?Carbon $doneCreatedAt, string $currentStatus): string
    {
        if (!$endDate) {
            return $currentStatus;
        }

        if ($doneCreatedAt) {
            if ($doneCreatedAt->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key'          => $key,
                    'enddate'      => $endDate->toDateTimeString(),
                    'enddate_last' => $doneCreatedAt->toDateTimeString()
                ]);
                return 'Overdue';
            }
        } else {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            if ($now->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key'          => $key,
                    'current_time' => $now->toDateTimeString()
                ]);
                return 'Overdue';
            }

            if ($now->isSameDay($endDate)) {
                $remindTime = Carbon::today('Asia/Ho_Chi_Minh')->setTime(17, 30, 0);
                if ($now->greaterThanOrEqualTo($remindTime)) {
                    Log::info('Thông báo: Issue sắp hết hạn vào hôm nay...', [
                        'key'          => $key,
                        'current_time' => $now->toDateTimeString()
                    ]);
                }
            }
        }

        return $currentStatus;
    }
}