<?php

use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/quotations/pdf', QuoteController::class)->middleware('throttle:60,1');
