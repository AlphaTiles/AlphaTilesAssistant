<?php

namespace App\Services;

use App\Models\Key;
use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Support\Facades\Log;

class CountKeysService
{
    protected LanguagePack $languagePack;

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
    }

    /**
     * Returns the number of times each key is used in the word list.
     *
     * @return array
     */
    public function handle(): array
    {
        $wordList = Word::where('languagepackid', $this->languagePack->id)->get();
        $keyList = Key::where('languagepackid', $this->languagePack->id)->get();
        
        // Convert key list to a hash map for quick lookup
        $keyHashMap = [];
        foreach ($keyList as $keyItem) {
            $keyHashMap[$keyItem->value] = $keyItem;
        }
                
        // Initialize key usage counter
        $keyUsage = [];
        foreach ($keyList as $keyItem) {
            $keyUsage[$keyItem->value] = 0;
        }
        
        // Count the usage of each key in the word list
        foreach ($wordList as $word) {
            $wordValue = strtolower($word->value);
            foreach ($keyList as $keyItem) {
                $keyValue = strtolower($keyItem->value);
                if(str_contains($wordValue, $keyValue)) {
                    $keyUsage[$keyValue]++;
                }                                      
            }
        }

        return $keyUsage;
                  
    }
}
