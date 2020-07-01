<?php

use Illuminate\Support\Facades\Route;

// verification callback
Route::get('/verification/{token}', 'Auth\VerificationController@register')
    ->name('verification');

// reset password callback
Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@resetPassword')
    ->name('reset-password');

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
