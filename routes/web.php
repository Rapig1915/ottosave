<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// user authentication routes
Route::group(['prefix' => 'v1'], function(){
    Route::group(['prefix' => 'user'], function(){

        // PUT:/user/register
        Route::put('/register', 'Api\V1\Guest\User\RegisterController@register');

        // /user/login
        Route::group(['prefix' => 'login'], function(){

            // POST:/user/login
            Route::post('/', 'Api\V1\Guest\User\LoginController@login');
            // POST:/user/login/refresh
            Route::post('/refresh', 'Api\V1\Guest\User\LoginController@refresh');
        });

        // POST: /user/forgot-password
        Route::post('forgot-password', 'Api\V1\Guest\User\ForgotPasswordController@requestReset');
        // POST: /user/reset
        Route::post('reset', 'Api\V1\Guest\User\ResetPasswordController@resetPassword');
    });
});

Route::group(['prefix' => 'user'], function(){
    // Dummy routes to tell Laravel where certain front-end views live.
    Route::put('/login', 'Api\VersionController@endpointObsolete')->name('login');
    Route::post('reset-password', 'Api\VersionController@endpointObsolete')->name('password.reset');
});

// GET: /apple-app-site-association
Route::get('apple-app-site-association', 'Api\V1\External\IosController@getAppUniversalLinkJson');

// GET: /download-account-transactions
Route::get('download-account-transactions/{token}', 'Api\V1\Guest\User\DownloadController@downloadAccountTransactions');

// main route returned from the server
Route::get('/{vue_paths?}', 'ViewController@renderVueView')->where('vue_paths', '[\/\w\.-]*');
