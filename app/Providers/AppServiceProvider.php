<?php

namespace App\Providers;

use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\IssueOverdueRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\SyncIssueRepository;
use App\Repositories\Eloquent\USBudgetRepository;
use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\IssueOverdueInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Repositories\Interfaces\USBudgetInterface;
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
        $this->app->bind(IssueOverdueInterface::class, IssueOverdueRepository::class);
        $this->app->bind(USBudgetInterface::class, USBudgetRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
