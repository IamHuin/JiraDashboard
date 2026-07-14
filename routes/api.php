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
    
    // ==========================================
    // NHÓM QUYỀN: MANAGE (Quản trị hệ thống)
    // ==========================================
    Route::middleware('permission:manage')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('manager', [ManagerController::class, 'getListUsers'])->name('admin.manager');
            Route::post('manager/user', [ManagerController::class, 'updateUser'])->name('admin.users.update');

            Route::get('roles', [RoleController::class, 'getListRoles']);
            Route::post('roles', [RoleController::class, 'createRole']);
            Route::get('roles/{id}', [RoleController::class, 'getDetailRole']);
            Route::put('roles/{id}', [RoleController::class, 'updateRole']);
            Route::delete('roles/{id}', [RoleController::class, 'deleteRole']);
        });

        Route::prefix('issues/cache')->group(function () {
            Route::get('get_tracked_cache_keys', [DashboardController::class, 'getTrackedCacheKeys'])->name('cache.get_tracked_cache_keys');
            Route::get('clear_cache', [DashboardController::class, 'clearCache'])->name('cache.clear_cache');
        });
    });

    // ==========================================
    // NHÓM QUYỀN: SYNC (Đồng bộ dữ liệu)
    // ==========================================
    Route::middleware('permission:sync')->prefix('issues/sync')->group(function () {
        Route::get('full_issues', [SyncIssueController::class, 'syncFullIssues'])->name('sync.full_issues');
        Route::get('from_last_issues', [SyncIssueController::class, 'syncFromLastIssues'])->name('sync.from_last_issues');
        Route::get('status/{mode}', [SyncIssueController::class, 'status']);
    });

    // ==========================================
    // NHÓM QUYỀN: DASHBOARD (Xem báo cáo)
    // ==========================================
    Route::middleware('permission:dashboard')->prefix('issues/dashboard')->group(function () {
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

    // ==========================================
    // NHÓM QUYỀN: IMPORT (Import File)
    // ==========================================
    Route::middleware('permission:import')->group(function () {
        Route::post('import', [DashboardController::class, 'importSlsx'])->name('leaderboard.import.slsx');
    });

});