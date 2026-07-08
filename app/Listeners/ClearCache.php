<?php

namespace App\Listeners;

use App\Events\IssuesSync;
use App\Services\Cache\DashboardCacheService;
use Illuminate\Support\Facades\Log;

class ClearCache
{
    protected $cacheService;

    public function __construct(DashboardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function handle(IssuesSync $event): void
    {
        $user = $event->syncingUser ?? null;

        if ($user) {
            $this->cacheService->clearUserCache($user->id);
            Log::info("User ID {$user->id} đã đồng bộ Jira thành công: Đã dọn dẹp Cache từ Queue Worker.");
        }
    }
}