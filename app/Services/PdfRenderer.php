<?php

namespace App\Services;

interface PdfRenderer
{
    /** Returns raw PDF bytes or throws RenderFailed. */
    public function render(string $html): string;
}
