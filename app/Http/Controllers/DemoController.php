<?php

namespace App\Http\Controllers;

use App\Services\Quotation;
use App\Services\QuoteView;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

final class DemoController
{
    public function index()
    {
        return view('demo', [
            'sample' => json_encode(Quotation::sample(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function preview(Request $request)
    {
        $payload = $request->validate(['payload' => ['required', 'string', 'max:30000']])['payload'];

        try {
            $decoded = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['payload' => 'Enter a valid JSON quotation.']);
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw ValidationException::withMessages(['payload' => 'Enter a JSON object with quotation details.']);
        }

        return response(QuoteView::html(Quotation::validate($decoded), preview: true))
            ->header('Cache-Control', 'no-store');
    }
}
