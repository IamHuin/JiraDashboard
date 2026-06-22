<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\DashboardInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function getBugRatioByPeriod(string $start, string $end, ?array $projectNames = [], ?string $userName = null): array
    {
        $query = DB::table('jira_bug_ratios')
            ->whereBetween('period', [$start, $end]);

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        if (!empty($userName)) {
            $query->where('user_name', $userName);
        }

        return $query->get()->toArray();
    }

    public function getSlsxUlnlRatioByPeriod(string $start, string $end, ?array $projectNames = [], ?string $userName = null): array
    {
        $query = DB::table('jira_slsx_ulnl_ratios')
            ->whereBetween('period', [$start, $end]);

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        if (!empty($userName)) {
            $query->where('user_name', $userName);
        }

        return $query->get()->toArray();
    }

    public function getListDetail(
        ?string $periodStart,
        ?string $periodEnd,
        ?string $username = null,
        ?string $issueType = null,
        ?array  $projectNames = [],
        int     $perPage = 10
    ): LengthAwarePaginator
    {
        $query = DB::table('jira_issues');

        if ($periodStart && $periodEnd) {
            $query->whereBetween('created_at_jira', [$periodStart, $periodEnd]);
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        if (!empty($issueType)) {
            $query->where('issuetype', $issueType);
        }

        if (!empty($username)) {
            if ($issueType === 'Bug') {
                $query->where('causer', $username);
            } elseif ($issueType === 'Sub-task') {
                $query->where('assignee', $username);
            }
        }

        return $query->paginate($perPage);
    }
}