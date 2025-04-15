<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use App\Models\Word;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Services\ParseWordsIntoTilesService;

class ApiTilesController extends Controller
{
    public function words(LanguagePack $languagePack, string $tileValue)
    {  
        $parseWordsIntoTilesService = new ParseWordsIntoTilesService($languagePack);      

        $tile = Tile::where('languagepackid', $languagePack->id)
            ->where('value', $tileValue)
            ->first();
        $tileHashMap[$tile->value] = $tile;
        $wordList = Word::where('languagepackid', $languagePack->id)->get();
        $scriptType = $languagePack->langInfo
            ->where('name', LangInfoEnum::SCRIPT_TYPE->value)
            ->first()->value ?? '';
        $placeholderCharacter = 'X'; 
    
        $wordsWithTile = [];
    
        // Process words and count tile occurrences
        foreach ($wordList as $word) {
            // First try the preliminary parsing to get all possible tiles
            $wordValue = str_replace('.', '', $word->value);
            $tilesInWord = $parseWordsIntoTilesService->parseWordIntoTiles($wordValue, $scriptType, $tileHashMap, $placeholderCharacter);
            if(count($tilesInWord) > 0) {
                $wordsWithTile[] = $word->value;
            }
        }

        return $wordsWithTile;
    }
}
