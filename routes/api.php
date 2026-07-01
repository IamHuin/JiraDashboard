<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\SyncIssueController;
use App\Http\Controllers\Dashboard\DashboardController;

Route::get('/test', function () {
    return view('welcome');
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'Login'])->name('login');
    Route::get('logout', [AuthController::class, 'Logout'])->name('logout');
    Route::get('refresh', [AuthController::class, 'Refresh'])->name('refresh');
});

Route::middleware('auth:api')->prefix('issues')->group(function () {

    Route::prefix('sync')->group(function () {
        Route::get('full_issues', [SyncIssueController::class, 'syncFullIssues'])->name('sync.full_issues');
        Route::get('month_issues', [SyncIssueController::class, 'syncMonthIssues'])->name('sync.month_issues');
        Route::post('from_last_issues', [SyncIssueController::class, 'syncFromLastIssues'])->name('sync.from_last_issues');
    });

    Route::prefix('cache')->group(function () {
        Route::get('get_tracked_cache_keys', [DashboardController::class, 'getTrackedCacheKeys'])->name('cache.get_tracked_cache_keys');
        Route::get('clear_cache', [DashboardController::class, 'clearCache'])->name('cache.clear_cache');
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [DashboardController::class, 'Overview'])->name('dashboard.overview');
        Route::get('projects', [DashboardController::class, 'getProjects'])->name('dashboard.projects');
        Route::get('overdue', [DashboardController::class, 'getOverdues'])->name('dashboard.overdues');
        Route::get('usbudget', [DashboardController::class, 'getUSBudget'])->name('dashboard.usbudget');

        Route::prefix('bug_ratio')->group(function () {
            Route::get('myself', [DashboardController::class, 'getBugRatioMyself'])->name('bug_ratio.myself');
            Route::get('leaderboard', [DashboardController::class, 'getBugRatioLeaderboard'])->name('bug_ratio.leaderboard');
        });

        Route::prefix('slsx_ulnl_ratio')->group(function () {
            Route::get('myself', [DashboardController::class, 'getSlsxUlnlRatioMyself'])->name('slsx_ulnl_ratio.myself');
            Route::get('leaderboard', [DashboardController::class, 'getSlsxUlnlRatioLeaderboard'])->name('slsx_ulnl_ratio.leaderboard');
        });
    });
});