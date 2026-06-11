<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return view('welcome');
});

Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::group(['namespace' => 'Auth'], function () {
        Route::post('login', [
            'uses' => 'AuthController@login',
            'as' => 'login'
        ]);
        
        Route::get('logout', [
            'uses' => 'AuthController@logout',
            'as' => 'logout'
        ]);
        
        Route::get('refresh', [
            'uses' => 'AuthController@refresh',
            'as' => 'refresh'
        ]);
    });
    
    Route::group(['namespace' => 'Issue'], function () {
        Route::get('issues', [
            'uses' => 'IssueController@issues',
            'as' => 'issues'
        ]);
    });
});