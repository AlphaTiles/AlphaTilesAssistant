<?php

namespace App\Services;

use App\Enums\RequiredAssetsEnum;
use App\Models\Game;
use Illuminate\Support\Facades\Log;

class GameSeeder
{
    /**
        * Seed games from CSV files for the given language pack ID.
        * Seeds normal games when empty and backfills ABS games when missing.
     */
    public function seedIfEmpty(int $languagePackId): void
    {
        $hasAnyGames = Game::where('languagepackid', $languagePackId)->exists();
        $hasAbsGames = Game::where('languagepackid', $languagePackId)
            ->where('abs', true)
            ->exists();

        if ($hasAnyGames && $hasAbsGames) {
            return;
        }

        $games = [];
        $order = ((int) Game::where('languagepackid', $languagePackId)->max('order')) + 1;
        if ($order <= 0) {
            $order = 1;
        }

        $maxDoor = (int) (Game::where('languagepackid', $languagePackId)->max('door') ?? 0);
        if ($maxDoor < 0) {
            $maxDoor = 0;
        }

        $defaultGames = [];
        if (!$hasAnyGames) {
            $defaultGames = $this->loadGamesFromCsv(
                database_path('seeders/games.csv'),
                $languagePackId,
                false,
                $order,
                0
            );

            if (!empty($defaultGames)) {
                $maxDoor = max($maxDoor, (int) max(array_column($defaultGames, 'door')));
            }
        }

        $absGames = [];
        if (!$hasAbsGames) {
            $absGames = $this->loadGamesFromCsv(
                database_path('seeders/abs_games.csv'),
                $languagePackId,
                true,
                $order,
                $maxDoor
            );
        }

        $games = array_merge($defaultGames, $absGames);

        if (!empty($games)) {
            Game::insert($games);
            Log::info("Seeded " . count($games) . " games for language pack id {$languagePackId}.");
        }
    }

    private function loadGamesFromCsv(string $csvPath, int $languagePackId, bool $isAbs, int &$order, int $doorOffset = 0): array
    {
        if (!file_exists($csvPath)) {
            Log::warning("Games CSV file not found: {$csvPath}");
            return [];
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            Log::warning("Unable to open games CSV file: {$csvPath}");
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            Log::warning("Games CSV file has no header: {$csvPath}");
            return [];
        }

        $headerMap = $this->buildHeaderMap($headers);
        $games = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue; // Skip empty rows
            }

            $requiredAssetsRaw = $this->getCsvValue($row, $headerMap, 'Required Assets');
            $requiredAssets = $requiredAssetsRaw ? RequiredAssetsEnum::tryFrom($requiredAssetsRaw) : null;
            if ($requiredAssetsRaw !== null && !$requiredAssets) {
                Log::warning("Unknown required assets value '{$requiredAssetsRaw}' in {$csvPath}");
            }
            $requiredAssetsValue = $requiredAssets ? $requiredAssets->value : null;

            $stagesIncluded = $this->getCsvValue($row, $headerMap, 'StagesIncluded');
            $basic = $this->getCsvValue($row, $headerMap, 'basic') === '1';
            $include = $basic;
            $csvDoor = (int) ($this->getCsvValue($row, $headerMap, 'Door') ?? 0);
            $door = $isAbs ? $doorOffset + $csvDoor : $csvDoor;
            $door = $include ? $door : null;

            $games[] = [
                'door' => $door,
                'order' => $order++,
                'country' => $this->getCsvValue($row, $headerMap, 'Country') ?? '',
                'level' => (int) ($this->getCsvValue($row, $headerMap, 'ChallengeLevel') ?? 0),
                'color' => (int) ($this->getCsvValue($row, $headerMap, 'Color') ?? 0),
                'audio_duration' => $this->getCsvValue($row, $headerMap, 'AudioDuration'),
                'syll_or_tile' => $this->getCsvValue($row, $headerMap, 'SyllOrTile') ?? '',
                'stages_included' => ($stagesIncluded === null || $stagesIncluded === '-') ? null : (int) $stagesIncluded,
                'friendly_name' => $this->getCsvValue($row, $headerMap, 'Friendly Name'),
                'required_assets' => $requiredAssetsValue,
                'basic' => $basic,
                'abs' => $isAbs,
                'languagepackid' => $languagePackId,
                'include' => $include,
                'file_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        return $games;
    }

    private function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = trim((string) $header);
            if ($normalized === '') {
                continue;
            }
            $map[$normalized] = $index;
        }

        return $map;
    }

    private function getCsvValue(array $row, array $headerMap, string $header): ?string
    {
        if (!array_key_exists($header, $headerMap)) {
            return null;
        }

        $value = $row[$headerMap[$header]] ?? null;
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
