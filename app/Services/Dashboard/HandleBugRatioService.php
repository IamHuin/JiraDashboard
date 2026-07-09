<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;

class HandleBugRatioService
{
    public function countBug($issues)
    {
        return $issues->filter(fn($issue) => $issue['issuetype'] === 'Bug' && !empty($issue['causer']))
            ->groupBy(function ($issue) {
                $dateRaw = $issue['created'] ?? $issue['created_at'] ?? $issue['created_at_jira'] ?? now();
                $period = Carbon::parse($dateRaw)->format('m-Y');
                return $period . '|' . $issue['causer'];
            })
            ->map(function ($group, $key) {
                [$period, $causer] = explode('|', $key);
                return [
                    'period'     => $period,
                    'causer'     => $causer,
                    'total_bugs' => $group->count()
                ];
            })
            ->values();
    }

    public function countSubTask($issues)
    {
        return $issues->filter(function ($issue) {
            $hasEndDate = !empty($issue['enddate']) || !empty($issue['end_date_jira']);

            return $issue['issuetype'] === 'Sub-task'
                && $issue['status'] === 'Done'
                && !empty($issue['assignee'])
                && $hasEndDate;
        })
            ->groupBy(function ($issue) {
                $dateRaw = $issue['enddate'] ?? $issue['end_date_jira'] ?? now();
                $period = Carbon::parse($dateRaw)->format('m-Y');
                return $period . '|' . $issue['assignee'];
            })
            ->map(function ($group, $key) {
                [$period, $assignee] = explode('|', $key);
                return [
                    'period'         => $period,
                    'assignee'       => $assignee,
                    'total_subtasks' => $group->count()
                ];
            })
            ->values();
    }

    public function countBugMissing($issues)
    {
        $validCategories = [
            "COD_Code không tối ưu",
            "COD_Coding convention",
            "COD_Coding sai logic",
            "COD_Coding thiếu",
            "COD_Không tuân thủ quy trình",
            "COD_Sửa lỗi gây ra lỗi mới"
        ];

        $filtered = $issues->filter(function ($issue) use ($validCategories) {
            $isBug = $issue['issuetype'] === 'Bug';
            $hasCauser = !empty($issue['causer']);
            $hasCategory = !empty($issue['causer_category'] ?? $issue['causer_category_jira'] ?? null); // Đề phòng lệch tên key

            $categoryValue = $issue['causer_category'] ?? null;
            $isNotValid = !in_array($categoryValue, $validCategories);

            return $isBug && $hasCauser && $hasCategory && $isNotValid;
        });

        return $filtered
            ->groupBy(function ($issue) {
                $dateRaw = $issue['created'] ?? $issue['created_at'] ?? $issue['created_at_jira'] ?? now();
                return Carbon::parse($dateRaw)->format('m-Y') . '|' . $issue['causer'];
            })
            ->map(function ($group, $key) {
                [$period, $causer] = explode('|', $key);
                return [
                    'period'             => $period,
                    'causer'             => $causer,
                    'total_bugs_missing' => $group->count()
                ];
            })
            ->values();
    }

    public function countBugPercent($issues)
    {
        $bugStats = $this->countBug($issues);
        $subTaskStats = $this->countSubTask($issues);
        $bugMissingStats = $this->countBugMissing($issues);

        $keyedSubTasks = $subTaskStats->keyBy(fn($item) => $item['period'] . '|' . $item['assignee']);
        $keyedMissing = $bugMissingStats->keyBy(fn($item) => $item['period'] . '|' . $item['causer']);

        return $bugStats->map(function ($bug) use ($keyedSubTasks, $keyedMissing) {
            $period = $bug['period'];
            $userName = $bug['causer'];

            $key = $period . '|' . $userName;

            $subtaskData = $keyedSubTasks->get($key);
            $missingData = $keyedMissing->get($key);

            $bugCount = $bug['total_bugs'];
            $subtaskCount = $subtaskData['total_subtasks'] ?? 0;
            $missingCount = $missingData['total_bugs_missing'] ?? 0;

            $percent = $subtaskCount > 0 ? round(($bugCount / $subtaskCount) * 100) : 0;

            return [
                'period'             => $period,
                'user_name'          => $userName,
                'total_bugs'         => $bugCount,
                'total_subtasks'     => $subtaskCount,
                'total_bugs_missing' => $missingCount,
                'bug_percent'        => $percent
            ];
        })->values();
    }
}