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
        DB::table('jira_sync')->updateOrInsert(
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

    public function saveUserStats(array $stats)
    {
        foreach ($stats as $stat) {
            DB::table('jira_user_stats')->updateOrInsert(
                [
                    'user_name' => $stat['user_name'],
                    'period' => $stat['period'],
                    'project_name' => $stat['project_name'],
                ],
                [
                    'bug_count' => $stat['bug_count'],
                    'bug_percent' => $stat['bug_percent'],
                    'subtask_count' => $stat['subtask_count'],
                    'ulnl_count' => $stat['ulnl_count'],
                    'slsx_count' => $stat['slsx_count'],
                    'slsx_vs_ulnl_ratio' => $stat['slsx_vs_ulnl_ratio'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
