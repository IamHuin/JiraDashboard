<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class HandleSlsxUlnlRatioService
{
    public function slsxSum($issues)
    {
        return $issues
            ->filter(fn($issue) => ($issue['issuetype'] === 'Sub-task')
                && !empty($issue['assignee'])
                && $issue['status'] === 'Done'
            )
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'username' => $assignee,
                'slsx_sum' => $group->filter(fn($issue) => !empty($issue['slsx']))->sum('slsx')
            ])
            ->values();
    }

    public function nltcExist()
    {
        $slsxUser = DB::table('jira_slsx_users')
            ->get()->toArray();
        $nltcUser = DB::table('jira_nltc')
            ->get()->toArray();
    }
}