<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\SyncIssueInterface;
use Illuminate\Support\Facades\DB;

class SyncIssueRepository implements SyncIssueInterface
{
    public function saveIssues(array $issues)
    {
        if (empty($issues)) {
            return;
        }

        $now = now();
        $bulkData = [];

        foreach ($issues as $issue) {
            $bulkData[] = [
                'key'             => $issue['key'],
                'project_name'    => $issue['projectName'] ?? '',
                'summary'         => $issue['summary'] ?? null,
                'issuetype'       => $issue['issuetype'] ?? null,
                'assignee'        => $issue['assignee'] ?? null,
                'causer'          => $issue['causer'] ?? null,
                'causer_category' => $issue['causer_category'] ?? null,
                'ulnl'            => $issue['ulnl'] ?? null,
                'slsx'            => $issue['slsx'] ?? null,
                'status'          => $issue['status'] ?? null,
                'subtask_keys'    => $issue['subtask_keys'] ?? null,
                'created_at_jira' => $issue['created'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('jira_issues')->upsert(
            $bulkData,
            ['key'],
            [        
                'project_name', 'summary', 'issuetype', 'assignee',
                'causer', 'causer_category', 'ulnl', 'slsx',
                'status', 'subtask_keys', 'created_at_jira', 'updated_at'
            ]
        );
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
        return DB::table('jira_syncs')->where('id', 1)->value('last_sync_time');
    }

    public function saveBugRatios(array $bugRatios)
    {
        if (empty($bugRatios)) {
            return;
        }

        $now = now();
        $bulkData = [];

        foreach ($bugRatios as $bugRatio) {
            $bulkData[] = [
                'user_name'         => $bugRatio['user_name'],
                'period'            => $bugRatio['period'],
                'project_name'      => $bugRatio['project_name'] ?? '',
                'bug_count'         => $bugRatio['bug_count'] ?? 0,
                'bug_count_missing' => $bugRatio['bug_count_missing'] ?? 0,
                'bug_percent'       => $bugRatio['bug_percent'] ?? 0,
                'subtask_count'     => $bugRatio['subtask_count'] ?? 0,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        DB::table('jira_bug_ratios')->upsert(
            $bulkData,
            ['user_name', 'period', 'project_name'],
            ['bug_count', 'bug_count_missing', 'bug_percent', 'subtask_count', 'updated_at']
        );
    }

    public function saveSlsxUlnlRatios(array $slsxUlnlRatios)
    {
        if (empty($slsxUlnlRatios)) {
            return;
        }

        $now = now();
        $bulkData = [];

        foreach ($slsxUlnlRatios as $ratio) {
            $bulkData[] = [
                'user_name'          => $ratio['user_name'],
                'period'             => $ratio['period'],
                'project_name'       => $ratio['project_name'] ?? '',
                'slsx_sum'           => $ratio['slsx_sum'] ?? 0,
                'ulnl_sum'           => $ratio['ulnl_sum'] ?? 0,
                'slsx_vs_ulnl_ratio' => $ratio['slsx_vs_ulnl_ratio'] ?? 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('jira_slsx_ulnl_ratios')->upsert(
            $bulkData,
            ['user_name', 'period', 'project_name'],
            ['slsx_sum', 'ulnl_sum', 'slsx_vs_ulnl_ratio', 'updated_at']
        );
    }
}
