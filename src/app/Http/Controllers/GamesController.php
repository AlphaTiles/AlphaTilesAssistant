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
            ->orderBy('door')
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

    public function store(LanguagePack $languagePack, Request $request)
    {
        /*
        $this->validateAddItems($request, $languagePack, new Tile(), 'tiles');

        $data = $request->all();
        $tiles = explode("\r\n", $data['add_items']);

        $insert = [];
        foreach($tiles as $key => $tile) {
            if(!empty($tile)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $tile;
                $insert[$key]['upper'] = strtoupper($tile);
            }
        }

        Tile::insert($insert);
        */
        return redirect("languagepack/games/{$languagePack->id}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {        
        /*
        $items = $request->all()['items'];   
        $orderBy = Tile::getOrderByValue($languagePack, 'tile_orderby');         
               
        $fileRules = 'mimes:mp3|max:1024';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(), 
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.type2' => ['sometimes'],    
                'items.*.type3' => ['sometimes'],    
                'items.*.file' => $fileRules,
                'items.*.file2' => $fileRules,
                'items.*.file3' => $fileRules,
                'items.*.stage' => ['sometimes'],    
                'items.*.stage2' => ['sometimes'],    
                'items.*.stage3' => ['sometimes'],    
            ],
            [                
                'items.*.type' => '',
                'items.*.or_1' => '',
                'items.*.or_2' => '',
                'items.*.or_3' => '',
                'items.*.type2' => '',
                'items.*.type3' => '',
                'items.*.file' => $customErrorMessage,
                'items.*.file2' => $customErrorMessage,
                'items.*.file3' => $customErrorMessage,
                'items.*.stage' => '',    
                'items.*.stage2' => '',    
                'items.*.stage3' => '',    
            ]
        );

        DB::transaction(function() use($items, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);

            foreach($items as $key => $tile) {

                $fileModel1 = $fileUploadService->handle($tile, 'tile', 1, $fileRules, 'mp3');
                $fileModel2 = $fileUploadService->handle($tile, 'tile', 2, $fileRules, 'mp3');
                $fileModel3 = $fileUploadService->handle($tile, 'tile', 3, $fileRules, 'mp3');
                
                $updateData = [
                    'upper' => $tile['upper'],
                    'type' => $tile['type'],
                    'stage' => $tile['stage'] ?? null,
                    'or_1' => $tile['or_1'],
                    'or_2' => $tile['or_2'],
                    'or_3' => $tile['or_3'],
                    'file_id' => $tile['file_id'] ?? null,
                    'type2' => $tile['type2'],
                    'file2_id' => $tile['file2_id'] ?? null,
                    'stage2' => $tile['stage2'] ?? null,
                    'type3' => $tile['type3'],
                    'file3_id' => $tile['file3_id'] ?? null,
                    'stage3' => $tile['stage3'] ?? null,                    
                ];                
                
                if (isset($fileModel1->id)) {
                    $updateData['file_id'] = $fileModel1->id;
                }
                if (isset($fileModel2->id)) {
                    $updateData['file2_id'] = $fileModel2->id;
                }
                if (isset($fileModel3->id)) {
                    $updateData['file3_id'] = $fileModel3->id;
                }

                Tile::where(['id' => $tile['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        $items = $request->all()['items'];
        if(Arr::pluck($items, 'delete')) {
            $itemsCollection = Tile::orderByConfig($languagePack, 'tile_orderby')
                ->where('languagepackid', $languagePack->id)
                ->with(['file', 'file2', 'file3'])
                ->paginate(config('pagination.default'));


            return view('languagepack.tiles', [
                'completedSteps' => ['lang_info', 'tiles'],
                'languagePack' => $languagePack,
                'items' => $itemsCollection,
                'orderby' => $orderBy,
                'pagination' => $itemsCollection->links()
            ]);
        }
        
        session()->flash('success', 'Records updated successfully');
        */

        return redirect(url('/languagepack/games/' . $languagePack->id));
    }        

    public function swapDoor(Game $game, Request $request)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
            'languagePackId' => 'required|integer',
        ]);

        $languagePackId = $request->input('languagePackId');
        $direction = $request->input('direction');

        // Get all games for this language pack ordered by door
        $games = Game::where('languagepackid', $languagePackId)
            ->orderBy('door')
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

        // Swap door values
        $tempDoor = $game->door;
        $game->update(['door' => $adjacentGame->door]);
        $adjacentGame->update(['door' => $tempDoor]);

        return response()->json(['success' => true, 'message' => 'Game order updated', 'gameId' => $game->id]);
    }
}