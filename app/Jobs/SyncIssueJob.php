<?php

namespace App\Jobs;

use App\Enums\SyncStatus;
use App\Models\User;
use App\Services\Sync\SyncIssueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncIssueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
    public $tries = 1;

    protected $userId;
    protected string $mode;

    public function __construct($userId, string $mode = 'full')
    {
        $this->userId = $userId;
        $this->mode = $mode;
        $this->onQueue('jira-sync');
    }

    protected function cacheKey(): string
    {
        return "jira-sync-status:{$this->userId}:{$this->mode}";
    }

    public function handle(SyncIssueService $service)
    {
        Cache::put($this->cacheKey(), SyncStatus::RUNNING->value, now()->addHours(2));

        try {
            $user = User::findOrFail($this->userId);

            match ($this->mode) {
                'full' => $service->syncFullIssues($user),
                'last' => $service->syncFromLastIssues($user),
                default => throw new \InvalidArgumentException("Unknown sync mode: {$this->mode}"),
            };


        } catch (\Throwable $e) {
            Cache::put($this->cacheKey(), SyncStatus::FAILED->value, now()->addMinutes(30));
            throw $e;
        }
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("{$this->userId}-{$this->mode}"))->expireAfter(3600)];
    }

    public function failed(\Throwable $exception)
    {
        Cache::put($this->cacheKey(), SyncStatus::FAILED->value, now()->addMinutes(30));
        Log::error("SyncIssueJob failed for user {$this->userId} (mode: {$this->mode}): " . $exception->getMessage());
    }
}