<?php

namespace App\Services;

class SeededGameCatalog
{
    public function getSeedSourceFiles(): array
    {
        return [
            [database_path('seeders/games.csv'), false],
            [database_path('seeders/abs_games.csv'), true],
        ];
    }

    public function getSeededGameSignatures(): array
    {
        $seededSignatures = [];

        foreach ($this->getSeedSourceFiles() as [$csvPath, $isAbs]) {
            if (!file_exists($csvPath)) {
                continue;
            }

            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                continue;
            }

            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $country = trim((string) ($row[1] ?? ''));
                $level = (int) ($row[2] ?? 0);
                $color = (int) ($row[3] ?? 0);
                $syllOrTile = trim((string) ($row[6] ?? ''));
                $friendlyName = trim((string) ($row[8] ?? ''));
                $friendlyName = $friendlyName === '' ? null : $friendlyName;

                $signature = $this->buildSeededSignature($country, $level, $color, $syllOrTile, $friendlyName, $isAbs);
                $seededSignatures[$signature] = true;
            }

            fclose($handle);
        }

        return $seededSignatures;
    }

    public function buildSeededSignature(
        string $country,
        int $level,
        int $color,
        string $syllOrTile,
        ?string $friendlyName,
        bool $isAbs
    ): string {
        return implode('|', [
            strtolower(trim($country)),
            $level,
            $color,
            strtolower(trim($syllOrTile)),
            strtolower(trim((string) ($friendlyName ?? ''))),
            $isAbs ? '1' : '0',
        ]);
    }
}
