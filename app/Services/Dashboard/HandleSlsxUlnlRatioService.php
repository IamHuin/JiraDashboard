<?php

namespace App\Services\Dashboard;

class HandleSlsxUlnlRatioService
{
    public function slsxSum($issues)
    {
        return $issues
            ->filter(fn($issue) => ($issue['issuetype'] === 'Sub-task') && !empty($issue['assignee']) && $issue['status'] === 'Done')
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'username' => $assignee,
                'slsx_sum' => $group->filter(fn($issue) => !empty($issue['slsx']))->sum('slsx')
            ])
            ->values();
    }

    public function ulnlSum($issues)
    {
        return $issues
            ->filter(fn($issue) => ($issue['issuetype'] === 'Sub-task') && !empty($issue['assignee']) && $issue['status'] === 'Done')
            ->groupBy(fn($issue) => $issue['assignee'])
            ->map(fn($group, $assignee) => [
                'username' => $assignee,
                'ulnl_sum' => $group->sum(function ($issue) {
                    $ulnl = $issue['ulnl'] ?? null;
                    $slsx = $issue['slsx'] ?? null;

                    return !empty($ulnl) ? (float)$ulnl : (float)$slsx;
                })
            ])
            ->values();
    }

    public function slsxUlnlPercent($issues)
    {
        $totalSlsx = $this->slsxSum($issues);

        $totalUlnl = $this->ulnlSum($issues)->keyBy('username');

        return $totalSlsx->map(function ($issue) use ($totalUlnl) {
            $username = $issue['username'];

            $ulnl = $totalUlnl->get($username)['ulnl_sum'] ?? $issue['slsx_sum'];

            $slsxSum = (float)$issue['slsx_sum'];
            $ulnlSum = (float)$ulnl;

            $ratio = ($slsxSum == 0 || $ulnlSum == 0) ? 0 : round(($slsxSum / $ulnlSum) * 100);

            return [
                'username' => $username,
                'slsx_sum' => $slsxSum,
                'ulnl_sum' => $ulnlSum,
                'slsx_vs_ulnl_ratio' => $ratio,
            ];
        })->values();
    }
}