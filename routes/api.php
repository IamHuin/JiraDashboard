<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return view('welcome');
});

Route::group(['namespace' => 'App\Http\Controllers'], function () {

    // Auth Routes
    Route::group(['namespace' => 'Auth'], function () {
        Route::post('login', [
            'uses' => 'AuthController@Login',
            'as' => 'login'
        ]);

        Route::get('logout', [
            'uses' => 'AuthController@Logout',
            'as' => 'logout'
        ]);

        Route::get('refresh', [
            'uses' => 'AuthController@Refresh',
            'as' => 'refresh'
        ]);
    });

    Route::group(['namespace' => 'Dashboard', 'prefix' => 'issues', 'middleware' => 'auth:api'], function () {

        Route::group(['prefix' => 'sync'], function () {
            Route::get('full_issues', [
                'uses' => 'SyncIssueController@syncFullIssues',
                'as' => 'sync.full_issues'
            ]);

            Route::get('month_issues', [
                'uses' => 'SyncIssueController@syncMonthIssues',
                'as' => 'sync.month_issues'
            ]);

            Route::post('from_last_issues', [
                'uses' => 'SyncIssueController@syncFromLastIssues',
                'as' => 'sync.from_last_issues'
            ]);
        });

        Route::group(['prefix' => 'cache'], function () {
            Route::get('get_tracked_cache_keys', [
                'uses' => 'DashboardController@getTrackedCacheKeys',
                'as' => 'cache.get_tracked_cache_keys'
            ]);
            
            Route::get('clear_cache', [
                'uses' => 'DashboardController@clearCache',
                'as' => 'cache.clear_cache'
            ]);
        });

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('overview', [
                'uses' => 'DashboardController@Overview',
                'as' => 'dashboard.overview'
            ]);

            Route::get('get_bug_ratio', [
                'uses' => 'DashboardController@getBugRatio',
                'as' => 'dashboard.get_bug_ratio'
            ]);

            Route::get('projects', [
                'uses' => 'DashboardController@getProjects',
                'as' => 'dashboard.projects'
            ]);
        });
    });
});