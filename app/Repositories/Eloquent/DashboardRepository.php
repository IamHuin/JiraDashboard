<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\DashboardInterface;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardInterface
{
    public function getOverview(array $filters): array
    {
        $baseQuery = DB::table('jira_issues');

        if (!empty($filters['project_names'])) {
            $baseQuery->whereIn('project_name', $filters['project_names']);
        }

        if (!empty($filters['period_start'])) {
            $baseQuery->whereDate('created_at_jira', '>=', $filters['period_start']);
        }

        if (!empty($filters['period_end'])) {
            $baseQuery->whereDate('created_at_jira', '<=', $filters['period_end']);
        }

        $totalUsers = (clone $baseQuery)->distinct('assignee')->count('assignee');
        $totalSubtask = (clone $baseQuery)->where('issuetype', 'Sub-task')->count();
        $totalBug = (clone $baseQuery)->where('issuetype', 'Bug')->count();

        return [
            'total_users' => $totalUsers,
            'total_subtask' => $totalSubtask,
            'total_bug' => $totalBug,
        ];
    }

    public function getIssuesByPeriod(string $start, string $end, ?array $projectNames = [], ?string $userName = null): array
    {
        $query = DB::table('jira_user_stats')
            ->whereBetween('period', [$start, $end]);

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        if (!empty($userName)) {
            $query->where('user_name', $userName);
        }

        return $query->get()->toArray();
    }


}
