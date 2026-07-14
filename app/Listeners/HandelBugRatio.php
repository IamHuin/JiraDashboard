<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleBugRatioService;

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

        $bugPercentList = $this->handleBugRatioService->countBugPercent($event->issues);

        foreach ($bugPercentList as $bugRatio) {
            $userName = $bugRatio['user_name'];

            $userIssue = $event->issues->first(fn($i) => ($i['causer'] === $userName) && !empty($i['projectName']));
            
            if ($userIssue['causer_displayName']){
                $bugRatios[] = [
                    'period' => $bugRatio['period'],
                    'project_name' => $userIssue['projectName'] ?? null,
                    'user_name' => $userName,
                    'display_name' => $userIssue['causer_displayName'],
                    'bug_count' => $bugRatio['total_bugs'],
                    'bug_count_missing' => $bugRatio['total_bugs_missing'],
                    'bug_percent' => $bugRatio['bug_percent'],
                    'subtask_count' => $bugRatio['total_subtasks'],
                ];
            }
        }

        if (!empty($bugRatios)) {
            $this->syncRepo->saveBugRatios($bugRatios);
        }
    }
}