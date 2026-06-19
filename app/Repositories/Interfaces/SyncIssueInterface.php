<?php

namespace App\Repositories\Interfaces;

interface SyncIssueInterface
{
    public function saveIssues(array $issues);
    public function updateSyncTime($lastSyncTime);
    public function getLastSyncTime();
    public function saveUserStats(array $stats);
}