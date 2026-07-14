<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\IssueOverdueInterface;
use Illuminate\Support\Facades\DB;

class IssueOverdueRepository implements IssueOverdueInterface
{
    public function upsertIssues(array $multipleData): void
    {
        if (empty($multipleData)) {
            return;
        }

        DB::table('jira_overdues')->upsert(
            $multipleData,
            ['key'],
            ['period', 'project_name', 'summary', 'issuetype', 'assignee', 'display_name', 'enddate', 'status', 'statusText', 'statusLogWork', 'statusTextLogWork', 'note', 'updated_at']
        );
    }
}