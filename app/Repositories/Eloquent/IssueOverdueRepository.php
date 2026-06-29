<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\IssueOverdueInterface;
use Illuminate\Support\Facades\DB;

class IssueOverdueRepository implements IssueOverdueInterface
{
    public function updateOrCreateIssue(array $data): void
    {
        DB::table('jira_overdues')->updateOrCreate(
            ['key' => $data['key']],
            [
                'period'       => $data['period'] ?? null,
                'project_name' => $data['projectName'] ?? null,
                'summary'      => $data['summary'] ?? null,
                'project'      => $data['project'] ?? null,
                'issue_type'   => $data['issueType'] ?? null,
                'assignee'     => $data['assignee'] ?? null,
                'enddate'      => $data['enddate'] ?? null,
                'status'       => $data['status'] ?? null,
                'updated_at'   => now(),
            ]
        );
    }
    
    public function getOverdueIssues(): array
    {
        return DB::table('jira_overdues')
            ->where('status', 'Overdue')
            ->orderBy('enddate', 'asc')
            ->get()
            ->toArray();
    }
}