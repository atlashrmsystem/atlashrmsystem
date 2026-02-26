<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

    return redirect()->away($frontendUrl.'/login');
})->name('login');
