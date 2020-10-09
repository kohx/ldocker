<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/refresh-token', function (Request $request) {
        $request->session()->regenerateToken();
        return response()->json();
    })->name('refresh-token');

    // 写真
    Route::post('/photos', 'PhotoController@store')->name('photo.store');
    Route::get('/photos', 'PhotoController@index')->name('photo.list');
    Route::get('/photos/{id}', 'PhotoController@show')->name('photo.show');
    Route::put('/photos/like', 'PhotoController@like')->name('photo.like');
    Route::put('/photos/{id}', 'PhotoController@edit')->name('photo.edit');
    Route::delete('/photos/{id}', 'PhotoController@destroy')->name('photo.destroy');
    // モデルバインディングで取得するので明示的に「{photo}」にしておく
    Route::post('/photos/{photo}/comments', 'PhotoController@storeComment')->name('photo.store_comment');
    Route::put('/photos/comments/{comment}', 'PhotoController@editComment')->name('photo.edit_comment');

});

// set lang
Route::get('/set-lang/{lang}', function (Request $request, $lang) {
    $request->session()->put('language', $lang);
    return response()->json();
})->name('set-lang');
