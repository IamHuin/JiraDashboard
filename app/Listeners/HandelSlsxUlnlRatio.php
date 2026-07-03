<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleSlsxUlnlRatioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandelSlsxUlnlRatio
{
    protected $handelSlsxUlnlService;
    protected $syncRepo;

    /**
     * Create the event listener.
     */
    public function __construct(HandleSlsxUlnlRatioService $handelSlsxUlnlService, SyncIssueInterface $syncRepo)
    {
        $this->handelSlsxUlnlService = $handelSlsxUlnlService;
        $this->syncRepo = $syncRepo;
    }

    /**
     * Handle the event.
     */
    public function handle(IssuesSync $event): void
    {
        $slsxUlnlRatios = [];

        $issuesByPeriod = $event->issues->groupBy(function ($issue) {
            return Carbon::parse($issue['enddate'])->format('m-Y');
        });

        foreach ($issuesByPeriod as $period => $periodIssues) {
            $slsxUlnlPercent = $this->handelSlsxUlnlService->slsxUlnlPercent($periodIssues);

            foreach ($slsxUlnlPercent as $item) {
                $userName = $item['username'];

                $userIssues = $periodIssues->filter(function ($issue) use ($userName) {
                    return ($issue['issuetype'] ?? '') === 'Sub-task'
                        && ($issue['assignee'] ?? null) === $userName;
                });

                $slsxUlnlRatios[] = [
                    'project_name' => $userIssues->first()['projectName'] ?? null,
                    'user_name' => $userName,
                    'slsx_sum' => $item['slsx_sum'],
                    'ulnl_sum' => $item['ulnl_sum'],
                    'slsx_vs_ulnl_ratio' => $item['slsx_vs_ulnl_ratio'],
                    'period' => $period,
                ];
            }
        }

        if (!empty($slsxUlnlRatios)) {
            $this->syncRepo->saveSlsxUlnlRatios($slsxUlnlRatios);
        }
    }
}