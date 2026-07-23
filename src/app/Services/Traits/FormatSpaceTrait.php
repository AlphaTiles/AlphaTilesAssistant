<?php

namespace App\Services\Traits;

trait FormatSpaceTrait
{
    protected function formatSpace(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return strtolower($value) === '[space]' ? ' ' : $value;
    }
}
