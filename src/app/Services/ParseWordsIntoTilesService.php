<?php

namespace App\Services;

use App\Enums\LangInfoEnum;
use App\Models\Tile;
use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Support\Facades\Log;

class ParseWordsIntoTilesService
{
    protected LanguagePack $languagePack;
    // Define valid multi-type tiles (this probably should be dynamic)
    const MULTITYPE_TILES = ["C", "PC", "V", "X", "T", "-", "SAD", "LV", "AV", "BV", "FV", "D", "AD"]; 

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
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
        
        $parseErrors = [];
        
        // Process words and count tile occurrences
        foreach ($wordList as $word) {
            // First try the preliminary parsing to get all possible tiles
            $wordValue = str_replace('.', '', $word->value);
            $preliminaryTilesInWord = $this->parseWordIntoTilesPreliminary($wordValue, $tileHashMap, $placeholderCharacter, self::MULTITYPE_TILES);
            $preliminaryLength = mb_strlen($wordValue);
            $coveredLength = 0;
            
            foreach ($preliminaryTilesInWord as $tile) {
                if ($tile !== null) {
                    $coveredLength += mb_strlen($tile->value);
                }
            }
            
            // Try full parsing only if preliminary parsing covered the entire word
            if ($coveredLength === $preliminaryLength) {
                $tilesInWord = $this->parseWordIntoTiles($word->value, $scriptType, $tileHashMap, $placeholderCharacter);
                
                // Check if full parsing succeeded
                $hasNullTile = false;
                foreach ($tilesInWord as $tileInWord) {
                    if ($tileInWord === null) {
                        $hasNullTile = true;
                        break;
                    }
                }
                
                if (!$hasNullTile && !empty($tilesInWord)) {
                    // Parsing succeeded, continue to next word
                    continue;
                }
            }
            
            // If we get here, either preliminary or full parsing failed
            $preliminaryTileStringsInWord = array_filter(array_map(fn($t) => $t->value ?? null, $preliminaryTilesInWord));
            $parseErrors[$word->value] = $preliminaryTileStringsInWord;
        }        
        
        return $parseErrors;
                  
    }

    public function parseWordIntoTiles($wordListWord, $scriptType, $tileHashMap, $placeholderCharacter)
    {
        $parsedWordArrayPreliminary = $this->parseWordIntoTilesPreliminary($wordListWord, $tileHashMap, $placeholderCharacter, self::MULTITYPE_TILES);
    
        if (!preg_match("/(Thai|Lao|Khmer|Arabic)/", $scriptType)) {
            return $parsedWordArrayPreliminary;
        }
    
        $parsedWordTileArray = [];
    
        $consonantScanIndex = 0;
        $currentConsonantIndex = 0;
        $previousConsonantIndex = -1;
        $nextConsonantIndex = count($parsedWordArrayPreliminary);
        $foundNextConsonant = false;
    
        while ($consonantScanIndex < count($parsedWordArrayPreliminary)) {
            $currentConsonant = null;
            $foundNextConsonant = false;
    
            // Scan for the next unchecked consonant tile
            while (!$foundNextConsonant && $consonantScanIndex < count($parsedWordArrayPreliminary)) {
                $currentTile = $parsedWordArrayPreliminary[$consonantScanIndex];
                $currentTileType = $currentTile->typeOfThisTileInstance;
    
                if (preg_match("/(C|PC)/", $currentTileType)) {
                    $currentConsonant = $currentTile;
                    $currentConsonantIndex = $consonantScanIndex;
                    $foundNextConsonant = true;
                }
                $consonantScanIndex++;
            }
    
            if (!$foundNextConsonant) {
                $currentConsonantIndex = count($parsedWordArrayPreliminary);
            }
    
            $foundNextConsonant = false;
    
            // Scan for the next unchecked consonant tile
            while (!$foundNextConsonant && $consonantScanIndex < count($parsedWordArrayPreliminary)) {
                $currentTile = $parsedWordArrayPreliminary[$consonantScanIndex];
                $currentTileType = $currentTile->typeOfThisTileInstance;
    
                if (preg_match("/(C|PC)/", $currentTileType)) {
                    $nextConsonantIndex = $consonantScanIndex;
                    $foundNextConsonant = true;
                }
                $consonantScanIndex++;
            }
    
            if (!$foundNextConsonant) {
                $nextConsonantIndex = count($parsedWordArrayPreliminary);
            }
    
            // Process vowel symbols, diacritics, spaces, and dashes
            $vowelTile = null;
            $SADTiles = [];
            $diacriticStringSoFar = "";
            $vowelStringSoFar = "";
            $vowelTypeSoFar = "";
            $nonCombiningVowelFromPreviousSyllable = null;
    
            for ($b = $previousConsonantIndex + 1; $b < $currentConsonantIndex; $b++) {
                $currentTile = $parsedWordArrayPreliminary[$b];
                $currentTileString = $currentTile->text;
                $currentTileType = $currentTile->typeOfThisTileInstance;
    
                if ($currentTileType === "LV") {
                    $vowelStringSoFar .= $currentTileString;
                    if ($vowelStringSoFar === $currentTileString) {
                        $vowelTypeSoFar = $currentTileType;
                    } elseif (isset($tileHashMap[$vowelStringSoFar])) {
                        $vowelTypeSoFar = $tileHashMap[$vowelStringSoFar]->tileType;
                    }
                } elseif ($currentTileType === "V") {
                    $nonCombiningVowelFromPreviousSyllable = $currentTile;
                }
            }
    
            $nonComplexV = null;
    
            for ($a = $currentConsonantIndex + 1; $a < $nextConsonantIndex; $a++) {
                $currentTile = $parsedWordArrayPreliminary[$a];
                $currentTileString = $currentTile->text;
                $currentTileType = $currentTile->typeOfThisTileInstance;
    
                if (preg_match("/(AV|BV|FV)/", $currentTileType)) {
                    if (isset($tileHashMap[$vowelStringSoFar])) {
                        if ($vowelTypeSoFar === "LV") {
                            if (!str_ends_with($vowelStringSoFar, $placeholderCharacter)) {
                                $vowelStringSoFar .= $placeholderCharacter;
                            }
                        } elseif (preg_match("/(AV|BV|FV)/", $vowelTypeSoFar) && !str_starts_with($vowelStringSoFar, $placeholderCharacter)) {
                            $vowelStringSoFar = $placeholderCharacter . $vowelStringSoFar;
                        }
                    }
    
                    if (str_contains($vowelStringSoFar, $placeholderCharacter) && str_contains($currentTileString, $placeholderCharacter)) {
                        $currentTileString = str_replace($placeholderCharacter, "", $currentTileString);
                    }
    
                    $vowelStringSoFar .= $currentTileString;
    
                    if ($vowelStringSoFar === $currentTileString) {
                        $vowelTypeSoFar = $currentTileType;
                    } elseif (isset($tileHashMap[$vowelStringSoFar])) {
                        $vowelTypeSoFar = $tileHashMap[$vowelStringSoFar]->tileType;
                    }
                } elseif (preg_match("/(AD|D)/", $currentTileType)) {
                    if (!empty($diacriticStringSoFar) && !str_contains($diacriticStringSoFar, $placeholderCharacter)) {
                        $diacriticStringSoFar = $placeholderCharacter . $diacriticStringSoFar;
                    }
    
                    if (str_contains($diacriticStringSoFar, $placeholderCharacter) && str_contains($currentTileString, $placeholderCharacter)) {
                        $currentTileString = str_replace($placeholderCharacter, "", $currentTileString);
                    }
    
                    $diacriticStringSoFar .= $currentTileString;
                } elseif ($currentTileType === "SAD") {
                    $SADTiles[] = $currentTile;
                } elseif (!$foundNextConsonant && $currentTileType === "V") {
                    $nonComplexV = $currentTile;
                }
            }
    
            if ($nonCombiningVowelFromPreviousSyllable) {
                $parsedWordTileArray[] = $nonCombiningVowelFromPreviousSyllable;
            }
    
            if ($currentConsonant) {
                if (!empty($diacriticStringSoFar) && isset($tileHashMap[$currentConsonant->text . str_replace($placeholderCharacter, "", $diacriticStringSoFar)])) {
                    $currentConsonant = $tileHashMap[$currentConsonant->text . str_replace($placeholderCharacter, "", $diacriticStringSoFar)];
                    $diacriticStringSoFar = "";
                }
    
                if (!empty($vowelStringSoFar)) {
                    switch ($vowelTypeSoFar) {
                        case "LV":
                            $vowelTile = $tileHashMap[$vowelStringSoFar] ?? null;
                            if ($vowelTile) $parsedWordTileArray[] = $vowelTile;
                            $parsedWordTileArray[] = $currentConsonant;
                            if (!empty($diacriticStringSoFar) && isset($tileHashMap[$diacriticStringSoFar])) {
                                $parsedWordTileArray[] = $tileHashMap[$diacriticStringSoFar];
                            }
                            break;
    
                        case "AV":
                        case "BV":
                        case "V":
                            $vowelTile = $tileHashMap[$vowelStringSoFar] ?? null;
                            $parsedWordTileArray[] = $currentConsonant;
                            if ($vowelTile) $parsedWordTileArray[] = $vowelTile;
                            if (!empty($diacriticStringSoFar) && isset($tileHashMap[$diacriticStringSoFar])) {
                                $parsedWordTileArray[] = $tileHashMap[$diacriticStringSoFar];
                            }
                            break;
    
                        case "FV":
                            $parsedWordTileArray[] = $currentConsonant;
                            if (!empty($diacriticStringSoFar) && isset($tileHashMap[$diacriticStringSoFar])) {
                                $parsedWordTileArray[] = $tileHashMap[$diacriticStringSoFar];
                            }
                            if ($vowelTile) $parsedWordTileArray[] = $vowelTile;
                            break;
                    }
                } else {
                    $parsedWordTileArray[] = $currentConsonant;
                }
    
                if ($nonComplexV) {
                    $parsedWordTileArray[] = $nonComplexV;
                }
    
                if (!empty($SADTiles)) {
                    array_push($parsedWordTileArray, ...$SADTiles);
                }
    
                $previousConsonantIndex = $currentConsonantIndex;
            }
    
            $consonantScanIndex = $nextConsonantIndex;
        }
    
        return $parsedWordTileArray;
    }
    
    public function parseWordIntoTilesPreliminary(string $wordListWord, $tileHashMap, $placeholderCharacter, $MULTITYPE_TILES)
    {
        $wordPreliminaryTileArray = [];
        $wordPreliminaryTileArrayFinal = [];
    
        $LOPwordString = strtolower($wordListWord);
        $tileIndex = 0;
    
        for ($i = 0; $i < mb_strlen($LOPwordString); $i++) {
            // Create character blocks for matching
            $next1Chars = mb_substr($LOPwordString, $i, 1);
            $next2Chars = ($i < mb_strlen($LOPwordString) - 1) ? mb_substr($LOPwordString, $i, 2) : "XYZXYZ";
            $next3Chars = ($i < mb_strlen($LOPwordString) - 2) ? mb_substr($LOPwordString, $i, 3) : "XYZXYZ";
            $next4Chars = ($i < mb_strlen($LOPwordString) - 3) ? mb_substr($LOPwordString, $i, 4) : "XYZXYZ";
    
            // Determine the longest matching tile
            $charBlockLength = 0;
            if (isset($tileHashMap[$next1Chars]) || isset($tileHashMap[$placeholderCharacter . $next1Chars]) ||
                isset($tileHashMap[$next1Chars . $placeholderCharacter]) || isset($tileHashMap[$placeholderCharacter . $next1Chars . $placeholderCharacter])) {
                $charBlockLength = 1;
            }
            if (isset($tileHashMap[$next2Chars]) || isset($tileHashMap[$placeholderCharacter . $next2Chars]) ||
                isset($tileHashMap[$next2Chars . $placeholderCharacter]) || isset($tileHashMap[$placeholderCharacter . $next2Chars . $placeholderCharacter])) {
                $charBlockLength = 2;
            }
            if (isset($tileHashMap[$next3Chars]) || isset($tileHashMap[$placeholderCharacter . $next3Chars]) ||
                isset($tileHashMap[$next3Chars . $placeholderCharacter]) || isset($tileHashMap[$placeholderCharacter . $next3Chars . $placeholderCharacter])) {
                $charBlockLength = 3;
            }
            if (isset($tileHashMap[$next4Chars]) || isset($tileHashMap[$placeholderCharacter . $next4Chars]) ||
                isset($tileHashMap[$next4Chars . $placeholderCharacter]) || isset($tileHashMap[$placeholderCharacter . $next4Chars . $placeholderCharacter])) {
                $charBlockLength = 4;
            }
    
            // Select the best-matching tile
            $tileString = "";
            switch ($charBlockLength) {
                case 1:
                    $tileString = $tileHashMap[$next1Chars] ?? $tileHashMap[$placeholderCharacter . $next1Chars] ?? 
                                  $tileHashMap[$next1Chars . $placeholderCharacter] ?? $tileHashMap[$placeholderCharacter . $next1Chars . $placeholderCharacter] ?? "";
                    break;
                case 2:
                    $tileString = $tileHashMap[$next2Chars] ?? $tileHashMap[$placeholderCharacter . $next2Chars] ?? 
                                  $tileHashMap[$next2Chars . $placeholderCharacter] ?? $tileHashMap[$placeholderCharacter . $next2Chars . $placeholderCharacter] ?? "";
                    $i++;
                    break;
                case 3:
                    $tileString = $tileHashMap[$next3Chars] ?? $tileHashMap[$placeholderCharacter . $next3Chars] ?? 
                                  $tileHashMap[$next3Chars . $placeholderCharacter] ?? $tileHashMap[$placeholderCharacter . $next3Chars . $placeholderCharacter] ?? "";
                    $i += 2;
                    break;
                case 4:
                    $tileString = $tileHashMap[$next4Chars] ?? $tileHashMap[$placeholderCharacter . $next4Chars] ?? 
                                  $tileHashMap[$next4Chars . $placeholderCharacter] ?? $tileHashMap[$placeholderCharacter . $next4Chars . $placeholderCharacter] ?? "";
                    $i += 3;
                    break;
            }
    
            if (!empty($tileString)) {
                $nextTile = $tileHashMap[$tileString->value] ?? null;
                if ($nextTile) {
                    $wordPreliminaryTileArray[] = $nextTile;
                }
            } 
            // elseif (!in_array($next1Chars, [".", "#"])) {
            //     return null;
            // }
        }
    
        // Process multi-type tiles
        foreach ($wordPreliminaryTileArray as $tile) {
            $nextTile = clone $tile;
            if (in_array($nextTile->text, $MULTITYPE_TILES)) {
                $nextTile->typeOfThisTileInstance = $this->getInstanceTypeForMixedTilePreliminary($tileIndex, $wordPreliminaryTileArray, $wordListWord);
                if ($nextTile->typeOfThisTileInstance === $nextTile->tileTypeB) {
                    $nextTile->stageOfFirstAppearanceForThisTileType = $nextTile->stageOfFirstAppearanceB;
                    $nextTile->audioForThisTileType = $nextTile->audioNameB;
                } elseif ($nextTile->typeOfThisTileInstance === $nextTile->tileTypeC) {
                    $nextTile->stageOfFirstAppearanceForThisTileType = $nextTile->stageOfFirstAppearanceC;
                    $nextTile->audioForThisTileType = $nextTile->audioNameC;
                } else {
                    $nextTile->stageOfFirstAppearanceForThisTileType = $nextTile->stageOfFirstAppearance;
                    $nextTile->audioForThisTileType = $nextTile->audioName;
                }
            } else {
                $nextTile->typeOfThisTileInstance = $nextTile->tileType;
                $nextTile->stageOfFirstAppearanceForThisTileType = $nextTile->stageOfFirstAppearance;
                $nextTile->audioForThisTileType = $nextTile->audioName;
            }
            $wordPreliminaryTileArrayFinal[] = $nextTile;
            $tileIndex++;
        }

        return $wordPreliminaryTileArrayFinal;
    }      
    
    function getInstanceTypeForMixedTilePreliminary(int $index, array $tilesInWordPreliminary, Word $wordListWord): ?string
    {
        // Possible types from gameTiles
        $types = ["C", "PC", "V", "X", "T", "-", "SAD", "LV", "AV", "BV", "FV", "D", "AD"];
    
        $mixedDefinitionInfoString = $wordListWord->mixedDefs;
        $instanceType = null;
    
        if (!in_array($mixedDefinitionInfoString, $types)) {
            // Extract mixed-type information (e.g., C234X6, 1FV3C5)
            $numTilesInWord = count($tilesInWordPreliminary);
            $mixedDefinitionInfoArray = array_fill(0, $numTilesInWord, null);
    
            // Store numbers in the array (1-indexed)
            for ($i = 0; $i < $numTilesInWord; $i++) {
                $number = (string)($i + 1);
                if (strpos($mixedDefinitionInfoString, $number) !== false) {
                    $mixedDefinitionInfoArray[$i] = $number;
                }
            }
    
            // Store type info between numbers
            $previousNumberEndIndex = 0;
            for ($i = 0; $i < $numTilesInWord; $i++) {
                $nextNumber = (string)($i + 2);
                $tilesInBetween = 1;
                $nextNumberStartIndex = strpos($mixedDefinitionInfoString, $nextNumber);
                $nextNumberInt = (int)$nextNumber;
    
                // Find the next number that exists
                while ($nextNumberStartIndex === false && $nextNumberInt <= $numTilesInWord) {
                    $nextNumberInt++;
                    $nextNumber = (string)$nextNumberInt;
                    $nextNumberStartIndex = strpos($mixedDefinitionInfoString, $nextNumber);
                    $tilesInBetween++;
                }
    
                if ($nextNumberStartIndex === false || $nextNumberInt > $numTilesInWord) {
                    $nextNumberStartIndex = strlen($mixedDefinitionInfoString);
                }
    
                // Extract the type information between previous and next numbers
                $infoBetweenNumbers = substr($mixedDefinitionInfoString, $previousNumberEndIndex, $nextNumberStartIndex - $previousNumberEndIndex);
                
                if ($tilesInBetween == 1) {
                    $mixedDefinitionInfoArray[$i] = $infoBetweenNumbers;
                } else {
                    // Extract first valid type in the substring
                    $type = "";
                    for ($c = 1; $c < strlen($infoBetweenNumbers); $c++) {
                        $firstC = substr($infoBetweenNumbers, 0, $c);
                        if (in_array($firstC, $types)) {
                            $type = $firstC;
                            break;
                        }
                    }
                    $mixedDefinitionInfoArray[$i] = $type;
                }
    
                $previousNumberEndIndex += strlen($mixedDefinitionInfoArray[$i]);
            }
    
            $instanceType = $mixedDefinitionInfoArray[$index] ?? null;
        } else {
            $instanceType = $mixedDefinitionInfoString; // Directly use mixedDefs if it's a simple type
        }
    
        return $instanceType;
    }        
}
