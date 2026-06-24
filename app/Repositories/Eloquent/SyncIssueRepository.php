<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\SyncIssueInterface;
use Illuminate\Support\Facades\DB;

class SyncIssueRepository implements SyncIssueInterface
{
    public function saveIssues(array $issues)
    {
        foreach ($issues as $issue) {
            DB::table('jira_issues')->updateOrInsert(
                ['key' => $issue['key']],
                [
                    'project_name' => $issue['projectName'] ?? null,
                    'summary' => $issue['summary'] ?? null,
                    'issuetype' => $issue['issuetype'] ?? null,
                    'assignee' => $issue['assignee'] ?? null,
                    'causer' => $issue['causer'] ?? null,
                    'causer_category' => $issue['causer_category'] ?? null,
                    'ulnl' => $issue['ulnl'] ?? null,
                    'slsx' => $issue['slsx'] ?? null,
                    'status' => $issue['status'] ?? null,
                    'created_at_jira' => $issue['created'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }


    public function updateSyncTime($lastSyncTime)
    {
        DB::table('jira_syncs')->updateOrInsert(
            ['id' => 1],
            [
                'last_sync_time' => $lastSyncTime,
                'updated_at' => now(),
            ]
        );
    }

    public function getLastSyncTime()
    {
        return DB::table('jira_sync')->where('id', 1)->value('last_sync_time');
    }

    public function saveBugRatios(array $bugRatios)
    {
        foreach ($bugRatios as $bugRatio) {
            DB::table('jira_bug_ratios')->updateOrInsert(
                [
                    'user_name' => $bugRatio['user_name'],
                    'period' => $bugRatio['period'],
                    'project_name' => $bugRatio['project_name'],
                ],
                [
                    'bug_count' => $bugRatio['bug_count'],
                    'bug_count_missing' => $bugRatio['bug_count_missing'],
                    'bug_percent' => $bugRatio['bug_percent'],
                    'subtask_count' => $bugRatio['subtask_count'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function saveSlsxUlnlRatios(array $slsxUlnlRatios)
    {
        foreach ($slsxUlnlRatios as $slsxUlnlRatio) {
            DB::table('jira_slsx_ulnl_ratios')->updateOrInsert(
                [
                    'user_name' => $slsxUlnlRatio['user_name'],
                    'period' => $slsxUlnlRatio['period'],
                    'project_name' => $slsxUlnlRatio['project_name'],
                ],
                [
                    'slsx_sum' => $slsxUlnlRatio['slsx_sum'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
