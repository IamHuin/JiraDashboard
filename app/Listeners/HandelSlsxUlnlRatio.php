<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Services\Dashboard\HandleSlsxUlnlRatioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HandelSlsxUlnlRatio
{
    protected $handelSlsxUlnlService;

    /**
     * Create the event listener.
     */
    public function __construct(HandleSlsxUlnlRatioService $handelSlsxUlnlService)
    {
        $this->handelSlsxUlnlService = $handelSlsxUlnlService;
    }

    /**
     * Handle the event.
     */
    public function handle(IssuesSync $event): void
    {
        $slsxUser = [];

        $issuesByPeriod = $event->issues->groupBy(function ($issue) {
            return Carbon::parse($issue['enddate'])->format('m-Y');
        });

        foreach ($issuesByPeriod as $period => $periodIssues) {
            $slsxSum = $this->handelSlsxUlnlService->slsxSum($periodIssues);

            foreach ($slsxSum as $item) {
                $userName = $item['username'];

                $userIssues = $periodIssues->filter(function ($issue) use ($userName) {
                    return ($issue['issuetype'] ?? '') === 'Sub-task'
                        && ($issue['assignee'] ?? null) === $userName;
                });

                $firstIssue = $userIssues->first();
                $projectName = $firstIssue ? ($firstIssue['projectName'] ?? null) : null;

                $slsxUser[] = [
                    'period' => $period,
                    'project_name' => $projectName,
                    'user_name' => $userName,
                    'display_name' => $item['display_name'] ?? $userName,
                    'slsx_sum' => $item['slsx_sum'],
                ];
            }
        }

        if (!empty($slsxUser)) {
            // Lưu dữ liệu sản lượng vào jira_slsx_users
            DB::table('jira_slsx_users')->upsert(
                $slsxUser, 
                ['period', 'project_name', 'user_name'], 
                ['display_name', 'slsx_sum']
            );
            
            // Tính toán tỷ lệ và lưu vào jira_slsx_ratios
            $periods = array_keys($issuesByPeriod->toArray());
            $this->handelSlsxUlnlService->calculateAndSaveRatios($periods);
        }
    }
}