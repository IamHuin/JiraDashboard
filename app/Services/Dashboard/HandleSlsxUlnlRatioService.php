<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class HandleSlsxUlnlRatioService
{
    public function slsxSum($issues)
    {
        return $issues
            ->filter(fn($issue) => ($issue['issuetype'] === 'Sub-task')
                && !empty($issue['assignee'])
                && $issue['status'] === 'Done'
            )
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'username' => $assignee,
                'display_name' => $group->first()['displayName'] ?? $assignee,
                'slsx_sum' => $group->filter(fn($issue) => !empty($issue['slsx']))->sum('slsx')
            ])
            ->values();
    }

    /**
     * Tính toán tỷ lệ SLSX / NLTC dựa trên dữ liệu giao nhau giữa jira_slsx_users và jira_nltc
     *
     * @param array $periods Danh sách các kỳ cần cập nhật (vd: ['07-2024'])
     */
    public function calculateAndSaveRatios(array $periods)
    {
        if (empty($periods)) {
            return;
        }

        // Lấy danh sách giao nhau giữa sản lượng và năng lực tiêu chuẩn
        $ratios = DB::table('jira_slsx_users as S')
            ->join('jira_nltc as N', function ($join) {
                $join->on('S.period', '=', 'N.period')
                     ->on('S.project_name', '=', 'N.project_name')
                     ->on('S.user_name', '=', 'N.user_name');
            })
            ->whereIn('S.period', $periods)
            ->select('S.period', 'S.project_name', 'S.user_name', 'S.display_name', 'S.slsx_sum', 'N.standard')
            ->get();

        $upsertData = [];
        foreach ($ratios as $row) {
            $standard = (float)$row->standard;
            $slsxSum = (float)$row->slsx_sum;
            
            $ratio = 0;
            if ($standard > 0) {
                $ratio = (int)round(($slsxSum / $standard) * 100);
            }

            $upsertData[] = [
                'period' => $row->period,
                'project_name' => $row->project_name,
                'user_name' => $row->user_name,
                'display_name' => $row->display_name,
                'slsx_sum' => $slsxSum,
                'standard' => $row->standard,
                'slsx_nltc_ratio' => $ratio,
            ];
        }

        if (!empty($upsertData)) {
            DB::table('jira_slsx_ratios')->upsert(
                $upsertData, 
                ['period', 'project_name', 'user_name'], 
                ['display_name', 'slsx_sum', 'standard', 'slsx_nltc_ratio']
            );
        }
    }
}