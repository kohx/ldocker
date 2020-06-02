<?php

use Illuminate\Support\Facades\Route;

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', fn () => view('index'))->where('any', '.+');
