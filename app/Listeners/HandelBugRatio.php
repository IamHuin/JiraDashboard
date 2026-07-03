<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Services\Dashboard\HandleBugRatioService;
use Illuminate\Support\Facades\Log;

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

            $userIssue = $event->issues->first(fn($i) =>
                ($i['causer'] === $userName || $i['assignee'] === $userName) && !empty($i['projectName'])
            );

            $bugRatios[] = [
                'project_name'       => $userIssue['projectName'] ?? null,
                'user_name'          => $userName,
                'bug_count'          => $bugRatio['total_bugs'],
                'bug_count_missing'  => $bugRatio['total_bugs_missing'],
                'bug_percent'        => $bugRatio['bug_percent'],
                'subtask_count'      => $bugRatio['total_subtasks'],
                'period'             => $bugRatio['period'],
            ];
        }

        if (!empty($bugRatios)) {
            $this->syncRepo->saveBugRatios($bugRatios);
        }
    }
}