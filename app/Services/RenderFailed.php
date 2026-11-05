<?php

namespace App\Services;

final class RenderFailed extends \RuntimeException
{
    public function __construct(public readonly bool $uncertain = false)
    {
        parent::__construct('The PDF renderer is unavailable.');
    }
}
