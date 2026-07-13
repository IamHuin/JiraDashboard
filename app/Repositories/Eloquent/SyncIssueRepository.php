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
                'display_name'    => $issue['displayName'] ?? null,
                'causer'          => $issue['causer'] ?? null,
                'causer_displayName' => $issue['causer_displayName'] ?? null,
                'causer_category' => $issue['causer_category'] ?? null,
                'slsx'            => $issue['slsx'] ?? null,
                'status'          => $issue['status'] ?? null,
                'subtask_keys'    => $issue['subtask_keys'] ?? null,
                'created_at_jira' => $issue['created'] ?? null,
                'end_date_jira'   => $issue['enddate'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('jira_issues')->upsert(
            $bulkData,
            ['key'],
            [        
                'project_name', 'summary', 'issuetype', 'assignee','display_name',
                'causer', 'causer_displayName', 'causer_category', 'slsx',
                'status', 'subtask_keys', 'created_at_jira', 'end_date_jira', 'updated_at'
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
                'period'            => $bugRatio['period'],
                'project_name'      => $bugRatio['project_name'] ?? '',
                'user_name'         => $bugRatio['user_name'],
                'display_name'      => $bugRatio['display_name'] ?? '',
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
            ['period', 'project_name', 'user_name', 'display_name'],
            ['bug_count', 'bug_count_missing', 'bug_percent', 'subtask_count', 'updated_at']
        );
    }

    public function saveSlsxRatios(array $slsxRatios)
    {
        if (empty($slsxRatios)) {
            return;
        }

        $now = now();
        $bulkData = [];

        foreach ($slsxRatios as $ratio) {
            $bulkData[] = [
                'period'             => $ratio['period'],
                'project_name'       => $ratio['project_name'] ?? '',
                'user_name'          => $ratio['user_name'],
                'display_name'       => $ratio['display_name'] ?? '',
                'slsx_sum'           => $ratio['slsx_sum'] ?? 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('jira_slsx_users')->upsert(
            $bulkData,
            ['period', 'project_name', 'user_name', 'display_name'],
            ['slsx_sum', 'updated_at']
        );
    }
}
