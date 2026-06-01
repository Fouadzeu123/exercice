<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    // Withdrawal Mobile Numbers configuration
    Route::get('settings/mobile-numbers', [\App\Http\Controllers\Settings\MobileNumbersController::class, 'edit'])->name('settings.mobile-numbers.edit');
    Route::post('settings/mobile-numbers', [\App\Http\Controllers\Settings\MobileNumbersController::class, 'store'])->name('settings.mobile-numbers.store');
    Route::patch('settings/mobile-numbers/{id}/default', [\App\Http\Controllers\Settings\MobileNumbersController::class, 'makeDefault'])->name('settings.mobile-numbers.default');
    Route::delete('settings/mobile-numbers/{id}', [\App\Http\Controllers\Settings\MobileNumbersController::class, 'destroy'])->name('settings.mobile-numbers.destroy');

    // Withdrawal Password configuration
    Route::get('settings/withdrawal-password', [\App\Http\Controllers\Settings\WithdrawalPasswordController::class, 'edit'])->name('settings.withdrawal-password.edit');
    Route::post('settings/withdrawal-password', [\App\Http\Controllers\Settings\WithdrawalPasswordController::class, 'update'])->name('settings.withdrawal-password.update');
});
