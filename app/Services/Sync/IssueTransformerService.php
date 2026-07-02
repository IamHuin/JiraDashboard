<?php

namespace App\Services\Sync;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IssueTransformerService
{
    /**
     * Biến đổi danh sách Issue thô sang cấu trúc lưu trữ bảng jira_issues (Flow 1)
     */
    public function transformMany(array $issues): array
    {
        return collect($issues)->map(function ($issue) {
            $issueType = $issue['fields']['issuetype']['name'] ?? null;
            $subtaskKeys = null;
            if ($issueType === 'Story' && !empty($issue['fields']['subtasks'])) {
                $keys = array_column($issue['fields']['subtasks'], 'key');
                $subtaskKeys = !empty($keys) ? json_encode($keys) : null;
            }
            return [
                'key'             => $issue['key'],
                'projectKey'      => $issue['fields']['project']['key'] ?? null,
                'projectName'     => $issue['fields']['project']['name'] ?? null,
                'summary'         => $issue['fields']['summary'] ?? null,
                'issuetype'       => $issue['fields']['issuetype']['name'] ?? null,
                'assignee'        => $issue['fields']['assignee']['displayName'] ?? $issue['fields']['assignee']['name'] ?? null,
                'causer'          => $issue['fields']['customfield_11321']['displayName'] ?? $issue['fields']['customfield_11321']['name'] ?? null,
                'causer_category' => $issue['fields']['customfield_10115']['value'] ?? null,
                'ulnl'            => $issue['fields']['customfield_11323'] ?? null,
                'slsx'            => $issue['fields']['customfield_11306'] ?? null,
                'status'          => $issue['fields']['status']['name'] ?? null,
                'subtask_keys'    => $subtaskKeys,
                'created'         => isset($issue['fields']['created'])
                    ? Carbon::parse($issue['fields']['created'])->format('Y-m-d H:i:s')
                    : null,
            ];
        })->toArray();
    }

    /**
     * Biến đổi chi tiết single issue từ mảng raw có changelog để tính Overdue (Flow 2)
     */
    public function transformSingle(array $issue): array
    {
        $fields = $issue['fields'] ?? [];
        $key = $issue['key'] ?? null;
        $currentStatus = $fields['status']['name'] ?? null;
        $endDateRaw = $fields['customfield_10108'] ?? null;

        $endDate = $endDateRaw ? Carbon::parse($endDateRaw, 'Asia/Ho_Chi_Minh')->endOfDay() : null;
        $doneCreatedAt = $this->getLatestDoneDate($currentStatus, $issue['changelog']['histories'] ?? []);
        if (($issue['fields']['issuetype']['name'] === 'Sub-task')) {
            $logWorkDateDone = $this->getLogWorkDone($issue['changelog']['histories'] ?? []);
            $finalLogWork = $this->calculateFinalLogWork($key, $endDate, $logWorkDateDone, $currentStatus);
        }
        $finalStatus = $this->calculateFinalStatus($key, $endDate, $doneCreatedAt, $currentStatus);

        return [
            'key'        => $key,
            'summary'    => $fields['summary'] ?? null,
            'project'    => $fields['project']['name'] ?? null,
            'enddate'    => $endDate ? $endDate->format('Y-m-d') : null,
            'status'     => $finalStatus['current_status'] ?? null,
            'statusText' => $finalStatus['status_text'] ?? null,
            'statusLogWork' => isset($finalLogWork['current_status']) ? $finalLogWork['current_status'] : null,
            'statusTextLogWork' => isset($finalLogWork['status_text']) ? $finalLogWork['status_text'] : null,
            'issueType'  => $fields['issuetype']['name'] ?? null,
            'assignee'   => $fields['assignee']['displayName'] ?? $fields['assignee']['name'] ?? null,
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

    private function getLogWorkDone(array $histories): ?Carbon
    {
        $latestLogWorkDate = null;

        foreach ($histories as $log) {
            if (isset($log['items'])) {
                foreach ($log['items'] as $item) {
                    if (isset($item['field']) && strtolower($item['field']) === 'WORKLOGID') {
                        if (isset($log['created'])) {
                            $logCreated = Carbon::parse($log['created'], 'Asia/Ho_Chi_Minh');
                            if ($latestLogWorkDate === null || $logCreated->greaterThan($latestLogWorkDate)) {
                                $latestLogWorkDate = $logCreated;
                            }
                        }
                    }
                }
            }
        }

        return $latestLogWorkDate;
    }

    private function calculateFinalLogWork(?string $key, ?Carbon $endDate, ?Carbon $logWorkDateDone, ?string $currentStatus): array
    {
        $statusText = null;
        if (!$endDate) {
            return [
                'current_status' => $currentStatus,
                'status_text' => 'Chưa có thời hạn',
            ];
        }

        if ($logWorkDateDone) {
            if ($logWorkDateDone->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key' => $key,
                    'enddate' => $endDate->toDateTimeString(),
                    'enddate_last' => $logWorkDateDone->toDateTimeString()
                ]);

                $currentStatus = 'Overdue';
                $statusText = "Quá hạn " . $this->formatDetailedDuration($endDate, $logWorkDateDone);
            } else {
                $days = $logWorkDateDone->startOfDay()->diffInDays($endDate->startOfDay());
                $statusText = $days === 0 ? "Đúng thời hạn" : "Còn " . $this->formatDetailedDuration($logWorkDateDone, $endDate);
            }
        } else {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            if ($now->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key' => $key,
                    'current_time' => $now->toDateTimeString()
                ]);

                $currentStatus = 'Missing';
                $statusText = "Thiếu log work (Quá hạn " . $this->formatDetailedDuration($endDate, $now) . ")";
            } elseif ($now->isSameDay($endDate)) {
                $remindTime = Carbon::today('Asia/Ho_Chi_Minh')->setTime(17, 30, 0);
                if ($now->greaterThanOrEqualTo($remindTime)) {
                    Log::info('Thông báo: Issue sắp hết hạn vào hôm nay...', [
                        'key' => $key,
                        'current_time' => $now->toDateTimeString()
                    ]);

                    $currentStatus = 'Warning';
                }
                $statusText = "Hạn cuối ngày";
            }
        }

        return [
            'current_status' => $currentStatus,
            'status_text' => $statusText,
        ];
    }

    /**
     * Logic lõi tính toán Overdue trạng thái và tạo chuỗi hiển thị quy đổi chi tiết
     */
    private function calculateFinalStatus(?string $key, ?Carbon $endDate, ?Carbon $doneCreatedAt, ?string $currentStatus): array
    {
        $statusText = null;
        if (!$endDate) {
            return [
                'current_status' => $currentStatus,
                'status_text'    => 'Chưa có thời hạn',
            ];
        }

        if ($doneCreatedAt) {
            if ($doneCreatedAt->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key'          => $key,
                    'enddate'      => $endDate->toDateTimeString(),
                    'enddate_last' => $doneCreatedAt->toDateTimeString()
                ]);

                $currentStatus = 'Overdue';
                $statusText = "Quá hạn " . $this->formatDetailedDuration($endDate, $doneCreatedAt);
            } else {
                $days = $doneCreatedAt->startOfDay()->diffInDays($endDate->startOfDay());
                $statusText = $days === 0 ? "Hạn cuối ngày" : (($currentStatus === 'Done') ? "Đúng thời hạn" : "Còn " . $this->formatDetailedDuration($doneCreatedAt, $endDate));
            }
        } else {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            if ($now->greaterThan($endDate)) {
                Log::info('Thông báo: Issue này đã quá hạn', [
                    'key'          => $key,
                    'current_time' => $now->toDateTimeString()
                ]);

                $currentStatus = 'Overdue';
                $statusText = "Quá hạn " . $this->formatDetailedDuration($endDate, $now);
            } elseif ($now->isSameDay($endDate)) {
                $remindTime = Carbon::today('Asia/Ho_Chi_Minh')->setTime(17, 30, 0);
                if ($now->greaterThanOrEqualTo($remindTime)) {
                    Log::info('Thông báo: Issue sắp hết hạn vào hôm nay...', [
                        'key'          => $key,
                        'current_time' => $now->toDateTimeString()
                    ]);

                    $currentStatus = 'Warning';
                }
                $statusText = "Hạn cuối ngày";
            } else {
                $statusText = "Còn " . $this->formatDetailedDuration($now, $endDate);
            }
        }

        return [
            'current_status' => $currentStatus,
            'status_text'    => $statusText,
        ];
    }

    /**
     * Hàm Helper quy đổi khoảng cách ngày lớn ra dạng chuỗi "X năm Y tháng Z ngày"
     */
    private function formatDetailedDuration(Carbon $startDate, Carbon $endDate): string
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        $totalDays = $start->diffInDays($end);

        if ($totalDays <= 30) {
            return "{$totalDays} ngày";
        }

        $diff = $start->diff($end);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = "{$diff->y} năm";
        }
        if ($diff->m > 0) {
            $parts[] = "{$diff->m} tháng";
        }
        if ($diff->d > 0) {
            $parts[] = "{$diff->d} ngày";
        }

        return implode(' ', $parts);
    }
}