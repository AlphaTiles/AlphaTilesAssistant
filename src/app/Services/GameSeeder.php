<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\Log;

class GameSeeder
{
    /**
     * Seed games from CSV for the given language pack ID.
     * Only seeds if no games exist for that language pack.
     */
    public function seedIfEmpty(int $languagePackId): void
    {
        // Check if games table already has entries for this language pack
        if (Game::where('languagepackid', $languagePackId)->exists()) {
            return;
        }

        $csvPath = database_path('seeders/games.csv');

        if (!file_exists($csvPath)) {
            Log::warning("Games CSV file not found: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            Log::warning("Unable to open games CSV file: {$csvPath}");
            return;
        }

        // Skip the header row
        fgetcsv($handle, 0, ';');

        $games = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (empty(array_filter($row))) {
                continue; // Skip empty rows
            }

            $games[] = [
                'door' => (int) $row[0], // Door
                'country' => $row[1], // Country
                'level' => (int) $row[2], // ChallengeLevel
                'color' => (int) $row[3], // Color
                'audio_duration' => $row[5], // AudioDuration
                'syll_or_tile' => $row[6], // SyllOrTile
                'stages_included' => $row[7] === '-' ? null : (int) $row[7], // StagesIncluded
                'friendly_name' => $row[8], // Friendly Name
                'languagepackid' => $languagePackId,
                'include' => true,
                'file_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        if (!empty($games)) {
            Game::insert($games);
            Log::info("Seeded " . count($games) . " games for language pack id {$languagePackId}.");
        }
    }
}
