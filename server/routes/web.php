<?php

use Illuminate\Support\Facades\Route;

// verification callback
Route::get('/verification/{token}', 'Auth\VerificationController@register')
    ->name('verification');

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
