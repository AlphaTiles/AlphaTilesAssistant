<?php

namespace App\Services;

use App\Models\Key;
use App\Models\Word;
use App\Models\LanguagePack;

class ParseWordsIntoKeysService
{
    protected LanguagePack $languagePack;

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
    }

    /**
     * Returns words that cannot be fully segmented using keyboard keys.
     *
     * @return array<string, array{parsed_keys: array<int, string>, missing_characters: array<int, string>}>
     */
    public function handle(): array
    {
        $words = Word::where('languagepackid', $this->languagePack->id)->get();
        $keys = Key::where('languagepackid', $this->languagePack->id)
            ->pluck('value')
            ->filter(fn ($value) => !empty($value))
            ->map(fn ($value) => $this->normalize(mb_strtolower($value)))
            ->unique()
            ->values()
            ->all();

        if (empty($keys)) {
            return [];
        }

        $maxKeyLength = max(array_map(fn ($value) => mb_strlen($value), $keys));
        $keyMap = array_fill_keys($keys, null);
        foreach ($keys as $key) {
            $keyMap[$key] = $key;
        }

        $parseWordByInventoryService = new ParseWordByInventoryService();

        $parseErrors = [];

        foreach ($words as $word) {
            $wordValue = $this->normalize(mb_strtolower(str_replace(['.', '#'], '', $word->value)));

            if ($wordValue === '') {
                continue;
            }

            $parseResult = $parseWordByInventoryService->handle($wordValue, $keyMap, $maxKeyLength);
            $parsedKeys = $parseResult['items'];
            $fullyParsed = $parseResult['fully_parsed'];
            $missingCharacters = $this->getMissingCharacters($wordValue, $keys);

            if (!$fullyParsed || !empty($missingCharacters)) {
                $parseErrors[$word->value] = [
                    'parsed_keys' => $parsedKeys,
                    'missing_characters' => $missingCharacters,
                ];
            }
        }

        return $parseErrors;
    }

    private function normalize(string $value): string
    {
        if (class_exists('Normalizer')) {
            return \Normalizer::normalize($value, \Normalizer::FORM_C);
        }

        return $value;
    }

    /**
     * Returns the unique characters that are missing from the keyboard inventory.
     *
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function getMissingCharacters(string $word, array $keys): array
    {
        $missing = [];
        $wordLength = mb_strlen($word);

        for ($i = 0; $i < $wordLength; $i++) {
            $character = mb_substr($word, $i, 1);
            $covered = false;

            foreach ($keys as $key) {
                if (mb_strpos($key, $character) !== false) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered && !in_array($character, $missing, true)) {
                $missing[] = $character;
            }
        }

        return $missing;
    }
}
