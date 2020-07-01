<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function (Illuminate\Http\Request $request) {
    $request->session()->get('language');
    return $request->session()->get('language');
});


Route::middleware(['language'])->group(function () {
    // verification callback
    Route::get('/verification/{token}', 'Auth\VerificationController@register')
        ->name('verification');

    // reset password callback
    Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@resetPassword')
        ->name('reset-password');

    // socialite 各プロバイダにリダイレクトするルート
    Route::get('/login/{provider}', 'Auth\LoginController@redirectToProvider');

    // socialite 各プロバイダからのコールバックを受けるルート
    Route::get('/login/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

    // API以外はindexを返すようにして、VueRouterで制御
    Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
});
