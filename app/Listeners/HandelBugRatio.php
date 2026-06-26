<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleBugRatioService;
use Carbon\Carbon;

class HandelBugRatio
{
    protected $handleBugRatioService;
    protected $syncRepo;

    /**
     * Create the event listener.
     */
    public function __construct(HandleBugRatioService $handleBugRatioService, SyncIssueInterface $syncRepo)
    {
        $this->handleBugRatioService = $handleBugRatioService;
        $this->syncRepo = $syncRepo;
    }

    /**
     * Handle the event.
     */
    public function handle(IssuesSync $event): void
    {
        $bugRatios = [];
        $issuesByPeriod = $event->issues->groupBy(function ($issue) {
            return Carbon::parse($issue['created'])->format('m-Y');
        });

        foreach ($issuesByPeriod as $period => $periodIssues) {
            $bugPercent = $this->handleBugRatioService->countBugPercent($periodIssues);
            
            foreach ($bugPercent as $bugRatio) {
                $userName = $bugRatio['user_name'];
                $userIssues = $periodIssues->filter(fn($i) => $i['causer'] === $userName || $i['assignee'] === $userName);

                $bugRatios[] = [
                    'project_name' => $userIssues->first()['projectName'] ?? null,
                    'user_name' => $userName,
                    'bug_count' => $bugRatio['total_bugs'],
                    'bug_count_missing' => $bugRatio['total_bugs_missing'],
                    'bug_percent' => $bugRatio['bug_percent'],
                    'subtask_count' => $bugRatio['total_subtasks'],
                    'period' => $period,
                ];
            }
        }

        if (!empty($bugRatios)) {
            $this->syncRepo->saveBugRatios($bugRatios);
        }
    }
}
