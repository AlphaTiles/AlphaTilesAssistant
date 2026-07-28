<?php

namespace App\Services\Traits;

trait FormatSpaceTrait
{
    protected function formatSpace(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return str_ireplace($this->spacePlaceholder(), ' ', $value);
    }

    protected function normalizeSpacePlaceholderForStorage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value === ' ' || $this->isSpacePlaceholder($value)) {
            return $this->spacePlaceholder();
        }

        return $value;
    }

    protected function normalizeSpaceTokenForComparison(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value === ' ' || $this->isSpacePlaceholder($value)) {
            return ' ';
        }

        return $value;
    }

    protected function formatSpacePlaceholderForDisplay(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($this->normalizeSpaceTokenForComparison($value) === ' ') {
            return $this->spacePlaceholder();
        }

        return $value;
    }

    protected function addLiteralSpaceAlias(array $inventory): array
    {
        if (array_key_exists(' ', $inventory)) {
            return $inventory;
        }

        foreach ($inventory as $key => $value) {
            if (is_string($key) && $this->isSpacePlaceholder($key)) {
                $inventory[' '] = $value;
                break;
            }
        }

        return $inventory;
    }

    protected function isSpacePlaceholder(string $value): bool
    {
        return strcasecmp(trim($value), $this->spacePlaceholder()) === 0;
    }

    private function spacePlaceholder(): string
    {
        return '[space]';
    }
}
