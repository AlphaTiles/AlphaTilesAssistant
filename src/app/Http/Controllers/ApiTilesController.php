<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use App\Models\Word;
use App\Enums\LangInfoEnum;
use Illuminate\Support\Arr;
use App\Models\LanguagePack;
use App\Services\ParseWordsIntoTilesService;

class ApiTilesController extends Controller
{
    public function words(LanguagePack $languagePack, int $tileId)
    {  
        $parseWordsIntoTilesService = new ParseWordsIntoTilesService($languagePack);      

        $tile = Tile::find($tileId);
        
        $normalizer = \Normalizer::FORM_C;
        if (preg_match('/\p{M}/u', $tile->value)) { 
            $normalizer = \Normalizer::FORM_D;
        }            
       $tileValue = \Normalizer::normalize($tile->value, $normalizer);

        $tileList = Tile::where('languagepackid', $languagePack->id)->get();
        $tileHashMap = [];
        foreach ($tileList as $tile) {
            $tileHashMap[$tile->value] = $tile;
        }

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
            $wordValue = \Normalizer::normalize($wordValue, $normalizer);

            $tilesInWord = $parseWordsIntoTilesService->parseWordIntoTiles($wordValue, $scriptType, $tileHashMap, $placeholderCharacter);            
                        
            if(!empty($tilesInWord) && in_array($tileValue, Arr::pluck($tilesInWord, 'value'))) {                
                $wordsWithTile[] = $word->value;
            }
        }

        if(count($wordsWithTile) == 0) {
            return ['No words found with the specified tile.'];
        }

        return $wordsWithTile;
    }
}
