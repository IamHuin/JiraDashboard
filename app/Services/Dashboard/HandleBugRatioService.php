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
            ])
            ->values();
    }

    public function countBugPercent($issues)
    {
        $bugStats = $this->countBug($issues);
        $subTaskStats = $this->countSubTask($issues);

        return $bugStats->map(function ($bug) use ($subTaskStats) {
            $userName = $bug['causer'];
            $subtaskCount = $subTaskStats->firstWhere('assignee', $userName)['total_subtasks'] ?? 0;

            $percent = $subtaskCount > 0 ? round(($bug['total_bugs'] / $subtaskCount) * 100, 2) : 0;

            return [
                'user_name' => $userName,
                'total_bugs' => $bug['total_bugs'],
                'total_subtasks' => $subtaskCount,
                'bug_percent' => $percent
            ];
        });
    }
}