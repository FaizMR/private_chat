<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\PrivateMessageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('messages', MessageController::class);
    Route::resource('private', PrivateMessageController::class);
});

require __DIR__ . '/settings.php';
