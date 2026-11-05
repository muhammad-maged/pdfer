<?php

use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DemoController::class, 'index'])->name('demo');
Route::post('/preview', [DemoController::class, 'preview'])
    ->middleware('throttle:30,1')->name('preview');
