<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleSlsxUlnlRatioService;
use Carbon\Carbon;

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

                $projectName = $userIssues->first()['projectName'] ?? null;

                $slsxUser[] = [
                    'period' => $period,
                    'project_name' => $projectName,
                    'user_name' => $userName,
                    'display_name' => $item['display_name'],
                    'slsx_sum' => $item['slsx_sum'],
                ];
            }
        }

        if (!empty($slsxUser)) {
            $this->syncRepo->saveSlsxRatios($slsxUser);
            $exist = $this->handelSlsxUlnlService->nltcExist();
        }
    }
}