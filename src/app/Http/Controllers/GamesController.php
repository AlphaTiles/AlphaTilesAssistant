<?php

namespace App\Http\Controllers;

use App\Enums\TabEnum;
use App\Models\Game;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Services\GameManagementService;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class GamesController extends BaseItemController
{
    protected $gameManagementService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, GameManagementService $gameManagementService)
    {
        $this->route = 'games';
        $this->model = new Game();
        $this->fileKeyname = 'game';
        $this->gameManagementService = $gameManagementService;

        parent::__construct($request);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack, string $tile = null)
    {
        $request = request();
        $showExcludedGames = $request->boolean('show_excluded');
        $requiredAssetsFilter = $this->resolveRequiredAssetsFilter($request, $showExcludedGames);

        $items = $this->gameManagementService->paginateForEdit(
            $languagePack,
            $showExcludedGames,
            $requiredAssetsFilter
        );

        $validationErrors = $this->resolveValidationErrors($languagePack, $tile);

        return view('languagepack.games', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'games'],
            'languagePack' => $languagePack,
            'items' => $items,
            'showExcludedGames' => $showExcludedGames,
            'requiredAssetsFilter' => $requiredAssetsFilter,
            'validationErrors' => $validationErrors,
            'pagination' => $items->links(),
        ]);
    }

    public function update(LanguagePack $languagePack, Request $request)
    {
        $validator = $this->makeUpdateValidator($request);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $items = $request->input('items', []);
        $this->gameManagementService->updateGames($languagePack, $items, $this->gameFileRules());

        session()->flash('success', 'Records updated successfully');
        return redirect(url('/languagepack/games/' . $languagePack->id) . '?' . http_build_query(request()->query()));
    }

    public function swapDoor(Game $game, Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
            'languagePackId' => 'required|integer',
        ]);

        $result = $this->gameManagementService->swapDoor(
            $game,
            (int) $request->input('languagePackId'),
            $request->input('direction')
        );

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        return response()->json(['success' => true, 'message' => 'Game order updated', 'gameId' => $game->id]);
    }

    private function resolveRequiredAssetsFilter(Request $request, bool $showExcludedGames): string
    {
        $requestedFilter = $request->query('required_assets_filter');

        if (!$showExcludedGames) {
            return $requestedFilter ?? 'my_games';
        }

        return $requestedFilter ?? ($request->boolean('show_abs_exclusive') ? 'abs' : 'all');
    }


    private function resolveValidationErrors(LanguagePack $languagePack, ?string $tile)
    {
        if (!empty($tile)) {
            return null;
        }

        $validationService = new ValidationService($languagePack);

        return $validationService->handle(TabEnum::GAME);
    }

    private function makeUpdateValidator(Request $request)
    {
        $fileRules = $this->gameFileRules();
        $customErrorMessage = 'The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.';

        return Validator::make(
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
    }

    private function gameFileRules(): string
    {
        return 'mimes:mp3|max:1024';
    }

}