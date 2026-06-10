<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::post('/reset', [AccountController::class, 'reset']);
Route::get('/balance', [AccountController::class, 'balance']);
Route::post('/event', [AccountController::class, 'event']);
