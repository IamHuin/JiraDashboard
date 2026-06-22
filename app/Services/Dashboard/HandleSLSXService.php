<?php

namespace App\Services\Dashboard;

class HandleSLSXService
{
    public function slsxSum($issues)
    {
        return $issues
            ->filter(fn($issue) => in_array($issue['issuetype'], ['Bug', 'Sub-task']) && !empty($issue['assignee']))
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'username' => $assignee,
                'slsx_sum' => $group->filter(fn($issue) => !empty($issue['slsx']))->sum('slsx')
            ])
            ->values();
    }
}