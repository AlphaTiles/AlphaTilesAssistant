<?php

namespace App\Services;

use App\Models\Key;
use App\Models\Word;
use App\Models\LanguagePack;
use App\Services\Traits\FormatSpaceTrait;

class CountKeysService
{
    use FormatSpaceTrait;

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
                
        // Initialize key usage counter
        $keyUsage = [];
        foreach ($keyList as $keyItem) {
            $keyUsage[$keyItem->value] = 0;
        }
        
        // Count the usage of each key in the word list
        foreach ($wordList as $word) {
            $wordValue = mb_strtolower($word->value);
            foreach ($keyList as $keyItem) {
                $keyValue = $this->normalizeKeyValueForMatching($keyItem->value ?? '');
                if ($keyValue !== '' && str_contains($wordValue, $keyValue)) {
                    $keyUsage[$keyItem->value]++;
                }
            }
        }

        return $keyUsage;
                  
    }

    private function normalizeKeyValueForMatching(string $keyValue): string
    {
        $normalizedValue = $this->normalizeSpaceTokenForComparison($keyValue);

        return mb_strtolower($normalizedValue);
    }
}
