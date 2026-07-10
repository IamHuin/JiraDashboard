<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\MilestoneInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MilestoneRepository implements MilestoneInterface
{
    /**
     * Lấy danh sách mốc lỗi phân trang theo style query động tường minh
     */
    public function getMilestones(string $period, ?string $reportType = null, ?string $ticket_code = null, ?array $projectNames = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = DB::table('jira_milestones')
            ->select('id', 'ticket_code', 'report_type', 'milestone_name');

        if (!empty($period)) {
            $query->where('period', $period);
        }

        if (!empty($reportType)) {
            $query->where('report_type', $reportType);
        }

        if (!empty($ticket_code)) {
            $query->where('ticket_code', 'like', '%' . $ticket_code . '%')
                ->orWhere('milestone_name', 'like', '%' . $ticket_code . '%');
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $query->orderBy('ticket_code', 'asc')
            ->orderBy('milestone_name', 'asc');

        return $query->paginate($perPage);
    }
}