<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\SyncIssueController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Role\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'Login'])->name('login');
    Route::get('logout', [AuthController::class, 'Logout'])->name('logout');
    Route::get('refresh', [AuthController::class, 'Refresh'])->name('refresh');
});

Route::middleware('auth:api')->group(function () {
    Route::middleware('isSuperAdmin')->prefix('admin')->group(function () {
        Route::get('manager', [ManagerController::class, 'getListUsers'])->name('admin.manager');
        Route::put('manager/user/{id}', [ManagerController::class, 'updateUser'])->name('admin.users.update');
        Route::prefix('role')->group(function () {
            Route::get('list', [RoleController::class, 'getListRoles'])->name('role.list');
            Route::post('create', [RoleController::class, 'createRole'])->name('role.create');
            Route::get('detail/{id}', [RoleController::class, 'getDetailRole'])->name('role.detail');
            Route::put('update/{id}', [RoleController::class, 'updateRole'])->name('role.update');
            Route::delete('delete/{id}', [RoleController::class, 'deleteRole'])->name('role.delete');
        });
    });
    Route::middleware('isUser')->prefix('issues')->group(function () {
        Route::prefix('sync')->group(function () {
            Route::get('full_issues', [SyncIssueController::class, 'syncFullIssues'])->name('sync.full_issues');
            Route::get('from_last_issues', [SyncIssueController::class, 'syncFromLastIssues'])->name('sync.from_last_issues');
            Route::get('status/{mode}', [SyncIssueController::class, 'status']);
        });

        Route::prefix('cache')->group(function () {
            Route::get('get_tracked_cache_keys', [DashboardController::class, 'getTrackedCacheKeys'])->name('cache.get_tracked_cache_keys');
            Route::get('clear_cache', [DashboardController::class, 'clearCache'])->name('cache.clear_cache');
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('overview', [DashboardController::class, 'Overview'])->name('dashboard.overview');
            Route::get('projects', [DashboardController::class, 'getProjects'])->name('dashboard.projects');
            Route::get('usbudget', [DashboardController::class, 'getUSBudgets'])->name('dashboard.usbudgets');
            Route::get('milestones', [DashboardController::class, 'getMilestones'])->name('dashboard.milestones');

            Route::prefix('leaderboard')->group(function () {
                Route::get('bug_ratio', [DashboardController::class, 'getBugRatioLeaderboard'])->name('leaderboard.bug_ratio');
                Route::get('slsx_ulnl_ratio', [DashboardController::class, 'getSlsxUlnlRatioLeaderboard'])->name('leaderboard.slsx_ulnl_ratio');
            });

            Route::prefix('overdue')->group(function () {
                Route::get('issue', [DashboardController::class, 'getOverdueIssues'])->name('overdues.issues');
                Route::get('logwork', [DashboardController::class, 'getOverdueLogWork'])->name('overdues.logwork');
            });
        });
    });
});