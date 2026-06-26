<?php

namespace App\Providers;

use App\Events\IssuesSync;
use App\Listeners\ClearCache;
use App\Listeners\HandelBugRatio;
use App\Listeners\HandelSlsxUlnlRatio;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        IssuesSync::class => [
            HandelBugRatio::class,
            HandelSlsxUlnlRatio::class,
            ClearCache::class
        ]
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
