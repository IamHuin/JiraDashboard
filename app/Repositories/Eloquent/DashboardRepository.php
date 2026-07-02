<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\DashboardInterface;
use Carbon\Carbon;
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

        if (!empty($filters['period'])) {
            $date = Carbon::createFromFormat('m-Y', $filters['period']);
            $baseQuery->whereYear('created_at_jira', $date->year)
                ->whereMonth('created_at_jira', $date->month);
        }

        $totalUsers = (clone $baseQuery)->distinct('assignee')->count('assignee');
        $totalSubtask = (clone $baseQuery)->where([
            'status' => 'Done',
            'issuetype' => 'Sub-task',
        ])->count();
        $totalBug = (clone $baseQuery)->where('issuetype', 'Bug')->count();

        return [
            'total_users' => $totalUsers,
            'total_subtask' => $totalSubtask,
            'total_bug' => $totalBug,
        ];
    }

    public function getBugRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): LengthAwarePaginator
    {
        $query = DB::table('jira_bug_ratios')
            ->select('id', 'user_name', 'subtask_count', 'bug_count', 'bug_count_missing', 'bug_percent');

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($userName)) {
            $query->where('user_name', 'like', "%{$userName}%");
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $query->orderBy('bug_percent', 'desc');

        return $query->paginate($perPage);
    }

    public function getSlsxUlnlRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): LengthAwarePaginator
    {
        $query = DB::table('jira_slsx_ulnl_ratios')
            ->select('id', 'user_name', 'ulnl_sum', 'slsx_sum', 'slsx_vs_ulnl_ratio');

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($userName)) {
            $query->where('user_name', 'like', "%{$userName}%");
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $query->orderBy('slsx_vs_ulnl_ratio', 'desc');

        return $query->paginate($perPage);
    }

    public function getOverdue(string $period, ?array $projectNames = [], ?string $username = null, ?string $issueType = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = DB::table('jira_overdues')
            ->select('id', 'key', 'summary', 'issuetype', 'assignee', 'status', 'enddate', 'statusText');

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        if (!empty($issueType)) {
            $query->where('issuetype', $issueType);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($username)) {
            $query->where('assignee', 'like', "%{$username}%");
        }

        $query->orderBy('enddate', 'desc');
        return $query->paginate($perPage);
    }
}