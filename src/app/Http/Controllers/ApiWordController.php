<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use App\Models\Word;
use Illuminate\Support\Arr;
use App\Models\LanguagePack;
use App\Services\ParseWordsIntoTilesService;

class ApiWordController extends Controller
{
    public function tiles(LanguagePack $languagePack, int $wordId): string
    {  
        $parseWordsIntoTilesService = new ParseWordsIntoTilesService($languagePack);      
        $word = Word::find($wordId);
        $tileList = Tile::where('languagepackid', $languagePack->id)->get();
        
        // Convert tile list to a hash map for quick lookup
        $tileHashMap = [];
        foreach ($tileList as $tile) {
            $tileHashMap[$tile->value] = $tile;
        }

        $parsedWordArrayPreliminary = $parseWordsIntoTilesService->parseWordIntoTilesPreliminary($word->value, $tileHashMap, 'X', ParseWordsIntoTilesService::MULTITYPE_TILES);
        
        return implode(',', Arr::pluck($parsedWordArrayPreliminary, 'value'));        
    }
}
