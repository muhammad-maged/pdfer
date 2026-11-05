<?php

namespace App\Providers;

use App\Services\PdfRenderer;
use App\Services\PdfShiftRenderer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PdfRenderer::class, PdfShiftRenderer::class);
    }
}
