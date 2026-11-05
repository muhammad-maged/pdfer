<?php

namespace App\Services;

final class QuoteView
{
    public static function html(array $quote, bool $preview = false, ?string $logoBase64 = null): string
    {
        return view('quotation', [
            'quote' => Quotation::calculate($quote),
            'preview' => $preview,
            'logoBase64' => $logoBase64,
            'fontBase64' => base64_encode(file_get_contents(resource_path('fonts/NotoSansArabic-Regular.ttf'))),
        ])->render();
    }
}
