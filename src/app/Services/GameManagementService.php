<?php

namespace App\Services;

use App\Models\Game;
use App\Models\LanguagePack;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GameManagementService
{
    public function paginateForEdit(
        LanguagePack $languagePack,
        bool $showExcludedGames,
        string $requiredAssetsFilter
    ): LengthAwarePaginator {
        $gamesQuery = Game::where('languagepackid', $languagePack->id)
            ->orderBy('order');

        if (!$showExcludedGames) {
            $gamesQuery->where('include', true);
        }

        $validRequiredAssetsFilters = [
            'my_games',
            'TA',
            'SB/T',
            'SB/T+SA',
        ];

        if ($requiredAssetsFilter === 'my_games') {
            $gamesQuery->where('include', true);
        } elseif ($requiredAssetsFilter === 'abs') {
            $gamesQuery->where('abs', true);
        } elseif (
            $requiredAssetsFilter !== 'all'
            && in_array($requiredAssetsFilter, $validRequiredAssetsFilters, true)
        ) {
            $gamesQuery->where('required_assets', $requiredAssetsFilter);
        }

        //Log::info($gamesQuery->toRawSql());

        return $gamesQuery
            ->paginate(config('pagination.default'))
            ->withQueryString();
    }

    public function updateGames(LanguagePack $languagePack, array $items, string $fileRules): void
    {
        DB::transaction(function () use ($items, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);

            foreach ($items as $game) {
                $this->updateGame($game, $fileUploadService, $fileRules);
            }

            $this->resequenceDoors($languagePack->id);
        });
    }

    public function swapDoor(Game $game, int $languagePackId, string $direction): array
    {
        $games = Game::where('languagepackid', $languagePackId)
            ->orderBy('order')
            ->get();

        $currentIndex = $games->search(function ($existingGame) use ($game) {
            return $existingGame->id === $game->id;
        });
        if ($currentIndex === false) {
            return ['error' => 'Game not found', 'status' => 404];
        }

        $adjacentIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($adjacentIndex < 0 || $adjacentIndex >= $games->count()) {
            return ['error' => 'Cannot move beyond boundaries', 'status' => 400];
        }

        $adjacentGame = $games[$adjacentIndex];

        $tempDoor = $game->door;
        $tempOrder = $game->order;
        $game->update([
            'door' => $adjacentGame->door,
            'order' => $adjacentGame->order,
        ]);
        $adjacentGame->update([
            'door' => $tempDoor ?? $game->door,
            'order' => $tempOrder,
        ]);

        return ['success' => true];
    }

    private function updateGame(array $game, FileUploadService $fileUploadService, string $fileRules): void
    {
        $gameId = (int) ($game['id'] ?? 0);
        $include = isset($game['include']);

        $fileModel = $fileUploadService->handle($game, 'game', 1, $fileRules, 'mp3');
        $updateData = [
            'include' => $include ? 1 : 0,
            'door' => null,
            'color' => $game['color'] ?? 0,
            'stages_included' => $game['stages_included'] ?? null,
        ];

        if ($fileModel && isset($fileModel->id)) {
            $updateData['file_id'] = $fileModel->id;
        }

        Game::where(['id' => $gameId])->update($updateData);
    }

    private function resequenceDoors(int $languagePackId): void
    {
        // Re-sequence doors for included games based on order, skipping excluded games.
        Game::where('languagepackid', $languagePackId)
            ->where('include', false)
            ->update(['door' => null]);

        $door = 1;
        Game::where('languagepackid', $languagePackId)
            ->where('include', true)
            ->orderBy('order')
            ->select('id')
            ->each(function (Game $includedGame) use (&$door) {
                $includedGame->update(['door' => $door++]);
            });
    }
}