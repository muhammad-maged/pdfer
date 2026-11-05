<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => DB::table('renders')
    ->whereNotNull('pdf_base64')
    ->where('expires_at', '<=', now())
    ->update(['pdf_base64' => null]))
    ->hourly()
    ->name('expire-pilot-pdfs')
    ->withoutOverlapping();
