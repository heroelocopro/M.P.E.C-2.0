<?php

use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/security');
    Route::redirect('settings/profile', '/settings/security')->name('profile.edit');
    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

});
