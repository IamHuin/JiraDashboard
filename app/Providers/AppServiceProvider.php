<?php

namespace App\Providers;

use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\IssueOverdueRepository;
use App\Repositories\Eloquent\ManagerRepository;
use App\Repositories\Eloquent\MilestoneRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\SyncIssueRepository;
use App\Repositories\Eloquent\USBudgetRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\IssueOverdueInterface;
use App\Repositories\Interfaces\ManagerInterface;
use App\Repositories\Interfaces\MilestoneInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\RoleInterface;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Repositories\Interfaces\USBudgetInterface;
use App\Repositories\Interfaces\UserInterface;
use App\Repositories\Interfaces\JiraNltcInterface;
use App\Repositories\Eloquent\JiraNltcRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(SyncIssueInterface::class, SyncIssueRepository::class);
        $this->app->bind(DashboardInterface::class, DashboardRepository::class);
        $this->app->bind(ProjectInterface::class, ProjectRepository::class);
        $this->app->bind(IssueOverdueInterface::class, IssueOverdueRepository::class);
        $this->app->bind(USBudgetInterface::class, USBudgetRepository::class);
        $this->app->bind(ManagerInterface::class, ManagerRepository::class);
        $this->app->bind(MilestoneInterface::class, MilestoneRepository::class);
        $this->app->bind(RoleInterface::class, RoleRepository::class);
        $this->app->bind(JiraNltcInterface::class, JiraNltcRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
