<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// register
Route::post('/register', 'Auth\RegisterController@register')->name('register');
// login
Route::post('/login', 'Auth\LoginController@login')->name('login');
// logout
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

// user
Route::get('/user', function () {
    return Auth::user();
})->name('user');
