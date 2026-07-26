<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('/friends', 'friends')->name('friends');
    Route::view('/campaign-builder', 'campaign-builder')->name('campaign-builder');
    Route::view('/character-builder', 'character-builder')->name('character-builder');

    Route::middleware(['dm'])->group(function () {

        Route::view('/characters', 'characters-page')->name('characters-page');
        Route::view('/scenes', 'scenes')->name('scenes');
    });

    Route::middleware(['player'])->group(function () {
        Route::view('/character', 'character-page')->name('character-page');
        Route::view('/inventory', 'inventory')->name('inventory');
    });

    Route::view('/shops', 'shops')->name('shops');
    Route::view('/shops/{shop}', 'shop-detail')->name('shop-detail');
    Route::view('/journal', 'journal')->name('journal');
});

require __DIR__.'/settings.php';
