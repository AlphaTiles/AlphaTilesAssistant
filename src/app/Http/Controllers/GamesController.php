<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use Carbon\Language;
use App\Enums\TabEnum;
use App\Models\Game;
use Illuminate\Support\Arr;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Models\LanguagepackConfig;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Services\ParseWordsIntoTilesService;

class GamesController extends BaseItemController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->route = 'games';
        $this->model = new Game();
        $this->fileKeyname = 'game';

        parent::__construct($request);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack, string $tile = null)
    {           
        $items = Game::where('languagepackid', $languagePack->id)
            ->orderBy('order')
            ->paginate(config('pagination.default'));

        $validationErrors = null;
        if(empty($tile)) {
            $validationService = (new ValidationService($languagePack));
            $validationErrors = $validationService->handle(TabEnum::GAME);    
        }

        return view('languagepack.games', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'games'],
            'languagePack' => $languagePack,
            'items' => $items,
            'validationErrors' => $validationErrors,
            'pagination' => $items->links(),
        ]);
    }

    public function update(LanguagePack $languagePack, Request $request)
    {
        $items = $request->all()['items'];
        $fileRules = 'mimes:mp3|max:1024';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(),
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.color' => ['sometimes', 'integer'],
                'items.*.file' => $fileRules,
                'items.*.stages_included' => ['sometimes', 'nullable', 'integer'],
            ],
            [
                'items.*.file' => $customErrorMessage,
            ]
        );

        DB::transaction(function() use($items, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);
            foreach($items as $key => $game) {
                $door = !empty($game['door']) ? $game['door'] : null;
                if(empty($game['include'])) {
                    $door = null;
                }

                $fileModel = $fileUploadService->handle($game, 'game', 1, $fileRules, 'mp3');
                $updateData = [
                    'include' => isset($game['include']) ? 1 : 0,
                    'door' => $door,
                    'color' => $game['color'] ?? 0,
                    'stages_included' => $game['stages_included'] ?? null,
                ];
                if ($fileModel && isset($fileModel->id)) {
                    $updateData['file_id'] = $fileModel->id;
                }

                Game::where(['id' => $game['id']])->update($updateData);
            }
        });

        // reset door numbers to be sequential
        $games = Game::where('languagepackid', $languagePack->id)
            ->whereNotNull('door')
            ->orderBy('door')
            ->get();    
        $doorNumber = 1;
        foreach($games as $game) {
            $game->update(['door' => $doorNumber++]);
        }

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }

        session()->flash('success', 'Records updated successfully');
        return redirect(url('/languagepack/games/' . $languagePack->id) . '?' . http_build_query(request()->query()));
    }

    public function swapDoor(Game $game, Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
            'languagePackId' => 'required|integer',
        ]);

        $languagePackId = $request->input('languagePackId');
        $direction = $request->input('direction');

        // Get all games for this language pack ordered by order
        $games = Game::where('languagepackid', $languagePackId)
            ->orderBy('order')
            ->get();

        // Find current game and adjacent game
        $currentIndex = $games->search(fn($g) => $g->id === $game->id);
        if ($currentIndex === false) {
            return response()->json(['error' => 'Game not found'], 404);
        }

        $adjacentIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        
        // Check bounds
        if ($adjacentIndex < 0 || $adjacentIndex >= $games->count()) {
            return response()->json(['error' => 'Cannot move beyond boundaries'], 400);
        }

        $adjacentGame = $games[$adjacentIndex];

        // Swap order values
        $tempDoor = $game->door;
        $tempOrder = $game->order;
        $game->update([
            'door' => $adjacentGame->door,
            'order' => $adjacentGame->order
        ]);
        $adjacentGame->update([
            'door' => $tempDoor ?? $game->door,
            'order' => $tempOrder
        ]);

        return response()->json(['success' => true, 'message' => 'Game order updated', 'gameId' => $game->id]);
    }
}