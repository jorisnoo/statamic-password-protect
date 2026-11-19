<?php

use Illuminate\Support\Facades\Route;
use Noo\PasswordProtect\Http\Controllers\PasswordProtectController;

// The form needs a session and CSRF protection even if the site's configured
// frontend middleware group is stateless.
Route::middleware('web')->group(function () {
    Route::get('password', [PasswordProtectController::class, 'show'])
        ->name('password-protect.show');

    Route::post('password', [PasswordProtectController::class, 'verify'])
        ->name('password-protect.verify');
});
