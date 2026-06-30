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
        $baseQuery = DB::table('jira_issues')
            ->where('status', 'Done');

        if (!empty($filters['project_names'])) {
            $baseQuery->whereIn('project_name', $filters['project_names']);
        }

        if (!empty($filters['period'])) {
            $date = Carbon::createFromFormat('m-Y', $filters['period']);
            $baseQuery->whereYear('created_at_jira', $date->year)
                ->whereMonth('created_at_jira', $date->month);
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

    public function getBugRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): array|LengthAwarePaginator
    {
        $query = DB::table('jira_bug_ratios');

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($userName)) {
            $query->where('user_name', $userName);
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $query->orderBy('bug_percent', 'desc');

        if (!empty($perPage)) {
            return $query->paginate($perPage);
        }

        return $query->get()->toArray();
    }

    public function getSlsxUlnlRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): array|LengthAwarePaginator
    {
        $query = DB::table('jira_slsx_ulnl_ratios');

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($userName)) {
            $query->where('user_name', $userName);
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $query->orderBy('slsx_vs_ulnl_ratio', 'desc');

        if (!empty($perPage)) {
            return $query->paginate($perPage);
        }

        return $query->get()->toArray();
    }

    public function getListDetail(
        ?string $period,
        ?string $username = null,
        ?string $issueType = null,
        ?array  $projectNames = [],
        int     $perPage = 10
    ): LengthAwarePaginator
    {
        $query = DB::table('jira_issues');

        if ($period) {
            $date = Carbon::createFromFormat('m-Y', $period);
            $query->whereYear('created_at_jira', $date->year)
                ->whereMonth('created_at_jira', $date->month);
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

        $query->orderBy('created_at_jira', 'asc')
            ->orderBy('id', 'asc');

        return $query->paginate($perPage);
    }

    public function getOverdue(?string $period, ?array $projectNames = [], ?string $username = null, ?string $issueType = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = DB::table('jira_overdues');

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
            $query->where('assignee', $username);
        }

        $query->orderBy('enddate', 'desc');

        return $query->paginate($perPage);
    }
}