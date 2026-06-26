<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleSLSXService;
use Carbon\Carbon;

class HandelSlsxUlnlRatio
{
    protected $handleSLSXService;
    protected $syncRepo;

    /**
     * Create the event listener.
     */
    public function __construct(HandleSLSXService $handleSLSXService, SyncIssueInterface $syncRepo)
    {
        $this->handleSLSXService = $handleSLSXService;
        $this->syncRepo = $syncRepo;
    }

    /**
     * Handle the event.
     */
    public function handle(IssuesSync $event): void
    {
        $slsxUlnlRatios = [];

        $issuesByPeriod = $event->issues->groupBy(function ($issue) {
            return Carbon::parse($issue['created'])->format('m-Y');
        });

        foreach ($issuesByPeriod as $period => $periodIssues) {
            $slsxSum = $this->handleSLSXService->slsxSum($periodIssues);

            foreach ($slsxSum as $slsx) {
                $userName = $slsx['username'];

                $userIssues = $periodIssues->filter(function ($issue) use ($userName) {
                    return ($issue['issuetype'] ?? '') === 'Sub-task'
                        && ($issue['assignee'] ?? null) === $userName;
                });

                $slsxUlnlRatios[] = [
                    'project_name' => $userIssues->first()['projectName'] ?? null,
                    'user_name' => $userName,
                    'slsx_sum' => $slsx['slsx_sum'],
                    'period' => $period,
                ];
            }
        }

        if (!empty($slsxUlnlRatios)) {
            $this->syncRepo->saveSlsxUlnlRatios($slsxUlnlRatios);
        }
    }
}