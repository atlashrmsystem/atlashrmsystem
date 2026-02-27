<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name', 'atlas-api'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/login', function () {
    $frontendUrl = trim((string) config('app.frontend_url', 'http://localhost:5173'));
    if ($frontendUrl === '' || filter_var($frontendUrl, FILTER_VALIDATE_URL) === false) {
        $frontendUrl = 'http://localhost:5173';
    }

    return redirect()->away(rtrim($frontendUrl, '/').'/login');
})->name('login');
