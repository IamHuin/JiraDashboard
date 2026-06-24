<?php

namespace App\Services\Dashboard;

class HandleBugRatioService
{
    public function countBug($issues)
    {
        return $issues->filter(fn($issue) => $issue['issuetype'] === 'Bug' && !empty($issue['causer']))
            ->groupBy(fn($issue) => $issue['causer'])
            ->map(fn($group, $causer) => [
                'causer' => $causer,
                'total_bugs' => $group->count()
            ])
            ->values();
    }

    public function countSubTask($issues)
    {
        return $issues->filter(fn($issue) => $issue['issuetype'] === 'Sub-task' && !empty($issue['assignee']))
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'assignee' => $assignee,
                'total_subtasks' => $group->count()
            ]);
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

        return $issues->filter(function ($issue) use ($validCategories) {
            return $issue['issuetype'] === 'Bug'
                && !empty($issue['causer'])
                && !empty($issue['causer_category'])
                && !in_array($issue['causer_category'], $validCategories);
        })
            ->groupBy(fn($issue) => $issue['causer'])
            ->map(fn($group, $causer) => [
                'causer' => $causer,
                'total_bugs_missing' => $group->count()
            ]);
    }

    public function countBugPercent($issues)
    {
        $bugStats = $this->countBug($issues);

        $subTaskStats = $this->countSubTask($issues)->keyBy('assignee');
        $bugMissingStats = $this->countBugMissing($issues)->keyBy('causer');

        return $bugStats->map(function ($bug) use ($subTaskStats, $bugMissingStats) {
            $userName = $bug['causer'];

            $subtaskCount = $subTaskStats->get($userName)['total_subtasks'] ?? 0;
            $missingCount = $bugMissingStats->get($userName)['total_bugs_missing'] ?? 0;

            $percent = $subtaskCount > 0 ? round(($bug['total_bugs'] / $subtaskCount) * 100, 2) : 0;

            return [
                'user_name'          => $userName,
                'total_bugs'         => $bug['total_bugs'],
                'total_subtasks'     => $subtaskCount,
                'total_bugs_missing' => $missingCount,
                'bug_percent'        => $percent
            ];
        })->values();
    }
}