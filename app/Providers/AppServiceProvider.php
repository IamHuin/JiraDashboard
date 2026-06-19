<?php

namespace App\Providers;

use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\SyncIssueRepository;
use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SyncIssueInterface::class, SyncIssueRepository::class);
        $this->app->bind(DashboardInterface::class, DashboardRepository::class);
        $this->app->bind(ProjectInterface::class, ProjectRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
