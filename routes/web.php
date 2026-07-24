<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('/friends', 'friends')->name('friends');
    Route::view('/campaign-builder', 'campaign-builder')->name('campaign-builder');
});

require __DIR__.'/settings.php';
