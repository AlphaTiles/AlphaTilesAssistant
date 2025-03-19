<?php

namespace App\Services;

use App\Enums\LangInfoEnum;
use App\Models\Tile;
use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Support\Facades\Log;

class CountTilesService
{
    protected LanguagePack $languagePack;
protected ParseWordsIntoTilesService $parseWordsIntoTilesService;

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
        $this->parseWordsIntoTilesService = new ParseWordsIntoTilesService($languagePack);
    }

    /**
     * Returns the number of times each tile is used in the word list.
     *
     * @return array
     */
    public function handle(): array
    {
        $wordList = Word::where('languagepackid', $this->languagePack->id)->get();
        $tileList = Tile::where('languagepackid', $this->languagePack->id)->get();
        
        // Convert tile list to a hash map for quick lookup
        $tileHashMap = [];
        foreach ($tileList as $tile) {
            $tileHashMap[$tile->value] = $tile;
        }
        
        $scriptType = $this->languagePack->langInfo
            ->where('name', LangInfoEnum::SCRIPT_TYPE->value)
            ->first()->value ?? '';
        $placeholderCharacter = 'X'; 
        
        // Initialize tile usage counter
        $tileUsage = [];
        foreach ($tileList as $tile) {
            $tileUsage[$tile->value] = 0;
        }
        
        // Process words and count tile occurrences
        foreach ($wordList as $word) {
            $tilesInWord = $this->parseWordsIntoTilesService->parseWordIntoTiles($word->value, $scriptType, $tileHashMap, $placeholderCharacter);

            foreach ($tileList as $tile) {
                foreach ($tilesInWord as $tileInWord) {
                    if ($tileInWord === null) {
                        $preliminaryTilesInWord = $this->parseWordsIntoTilesService->parseWordIntoTilesPreliminary($word->value, $tileHashMap, $placeholderCharacter, self::MULTITYPE_TILES);
                        $preliminaryTileStringsInWord = array_filter(array_map(fn($t) => $t->value ?? null, $preliminaryTilesInWord));
        
                        Log::error("The word '{$word->value}' could not be parsed. The tiles parsed (simple parsing) are: " . implode(", ", $preliminaryTileStringsInWord));
                        break;
                    }
                    if (strtolower($tileInWord->value) === strtolower($tile->value)) {
                        $tileUsage[$tile->value]++;
                    }
                }
            }
        }
        
        return $tileUsage;
                  
    }
}