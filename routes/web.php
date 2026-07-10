<?php

use Illuminate\Support\Facades\Route;
use Noo\PasswordProtect\Http\Controllers\PasswordProtectController;

Route::get('password', [PasswordProtectController::class, 'show'])
    ->name('statamic.password-protect.show');

Route::post('password', [PasswordProtectController::class, 'verify'])
    ->middleware('throttle:password-protect')
    ->name('statamic.password-protect.verify');
