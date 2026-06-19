<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    protected int $ttl;

    public function __construct()
    {
        $this->ttl = (int)config('cache.stores.file.dashboard_ttl', 600);
    }

    public function getTrackedKeys(int $userId): array
    {
        $registryKey = "user_{$userId}_cache_registry";
        return Cache::get($registryKey, []);
    }

    public function trackKey(int $userId, string $cacheKey): void
    {
        $registryKey = "user_{$userId}_cache_registry";
        $trackedKeys = $this->getTrackedKeys($userId);

        if (!in_array($cacheKey, $trackedKeys)) {
            $trackedKeys[] = $cacheKey;
            Cache::forever($registryKey, $trackedKeys);
        }
    }

    public function clearUserCache(int $userId): void
    {
        $trackedKeys = $this->getTrackedKeys($userId);

        foreach ($trackedKeys as $key) {
            Cache::forget($key);
        }

        Cache::forget("user_{$userId}_cache_registry");
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}