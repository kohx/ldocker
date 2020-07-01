<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware(['language'])->group(function () {
    // register
    Route::post('/register', 'Auth\RegisterController@sendMail')->name('register');
    // login
    Route::post('/login', 'Auth\LoginController@login')->name('login');
    // logout
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
    // forgot
    Route::post('/forgot', 'Auth\ForgotPasswordController@forgot')->name('forgot');
    // reset
    Route::post('/reset', 'Auth\ResetPasswordController@reset')->name('reset');
    // user
    Route::get('/user', function () {
        return Auth::user();
    })->name('user');
    // refresh token
    Route::get('/refresh-token', function (Illuminate\Http\Request $request) {
        $request->session()->regenerateToken();
        return response()->json();
    })->name('refresh-token');
});

// set lang
Route::get('/set-lang/{lang}', function (Illuminate\Http\Request $request, $lang) {
    $request->session()->put('language', $lang);
    return response()->json();
})->name('set-lang');
