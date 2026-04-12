<?php

namespace App\Services;

use App\Models\Tile;
use App\Models\Word;
use App\Enums\TabEnum;
use App\Enums\ErrorTypeEnum;
use App\Enums\GameSettingEnum;
use App\Models\GameSetting;
use App\Models\Key;
use App\Models\LanguagePack;
use App\Models\Syllable;
use Google\Service\Vision\Symbol;
use Illuminate\Database\Eloquent\Model;

class ValidationService
{
    const NUM_TIMES_TILES_WANTED_IN_WORDS = 5;

    protected LanguagePack $languagePack;

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
    }

    /**
     * returns an array of errors
     */
    public function handle(TabEnum $tab = null): array
    {
        $errors = [];

        if(empty($tab) || $tab === TabEnum::WORD) {
            $errors = $this->checkWordFilesMissing();
            $errors = $this->checkDuplicates($errors, new Word(), ErrorTypeEnum::DUPLICATE_WORD);     
            $errors = $this->checkParsingWordsIntoTiles($errors);
            $errors = $this->checkParsingWordsIntoKeys($errors);
        }

        if(empty($tab) || $tab === TabEnum::KEY) {
            $errors = $this->checkKeyboardHasKeys($errors);
            $errors = $this->checkDuplicates($errors, new Key(), ErrorTypeEnum::DUPLICATE_KEY);
            $errors = $this->checkColor($errors, new Key(), ErrorTypeEnum::COLOR_KEY);
        }

        if(empty($tab) || $tab === TabEnum::SYLLABLE) {
            $errors = $this->checkDuplicates($errors, new Syllable(), ErrorTypeEnum::DUPLICATE_SYLLABLE);
            $errors = $this->checkDistractors($errors, new Syllable(), ErrorTypeEnum::EMPTY_DISTRACTOR_SYLLABLE);
            $errors = $this->checkAudioFilesMissing($errors, new Syllable(), ErrorTypeEnum::MISSING_TILE_AUDIO_FILE);            
        }

        if(empty($tab) || $tab === TabEnum::TILE) {
            $errors = $this->checkDuplicates($errors, new Tile(), ErrorTypeEnum::DUPLICATE_TILE);
            $errors = $this->checkDistractors($errors, new Tile(), ErrorTypeEnum::EMPTY_DISTRACTOR_TILE);
            $errors = $this->checkTypes($errors, new Tile(), ErrorTypeEnum::EMPTY_TYPE_TILE);
            $errors = $this->checkAudioFilesMissing($errors, new Tile(), ErrorTypeEnum::MISSING_TILE_AUDIO_FILE);
        }

        if(empty($tab)) {
            $errors = $this->checkTileUsage($errors);
            $errors = $this->checkKeyExistsInWords($errors);
        }

        $groupedErrors = collect($errors)->sortBy('tab')->groupBy('type');

        return $groupedErrors->toArray();
    }

    private function checkKeyboardHasKeys(array $errors): array
    {
        $hasKeys = Key::where('languagepackid', $this->languagePack->id)->exists();

        if ($hasKeys) {
            return $errors;
        }

        $i = count($errors);
        $errors[$i]['value'] = 'Add at least one key in the keyboard tab.';
        $errors[$i]['type'] = ErrorTypeEnum::NO_KEYBOARD_KEYS;
        $errors[$i]['tab'] = ErrorTypeEnum::NO_KEYBOARD_KEYS->tab()->name();

        return $errors;
    }

    private function checkWordFilesMissing(): array
    {
        $wordsWithoutFile = Word::where('languagepackid', $this->languagePack->id)
        ->where(function ($query) {
            $query->whereNull('audiofile_id')
                ->orWhereNull('imagefile_id');
        })
        ->get();

        $errors = [];
        $i = 0;
        foreach ($wordsWithoutFile as $word) {
            if (empty($word->audiofile_id)) {
                $errors[$i]['value'] = $word->value;
                $errors[$i]['type'] = ErrorTypeEnum::MISSING_WORD_AUDIO_FILE;
                $errors[$i]['tab'] = ErrorTypeEnum::MISSING_WORD_AUDIO_FILE->tab()->name();
                $i++;
            }
            if (empty($word->imagefile_id)) {
                $errors[$i]['value'] = $word->value;
                $errors[$i]['type'] = ErrorTypeEnum::MISSING_WORD_IMAGE_FILE;
                $errors[$i]['tab'] = ErrorTypeEnum::MISSING_WORD_AUDIO_FILE->tab()->name();
                $i++;
            }
        }

        return $errors;
    }

    private function checkAudioFilesMissing(array $errors, Model $model, ErrorTypeEnum $errorEnum): array
    {
        if($model instanceof Tile) {
            $audioFileRequired = GameSetting::where('languagepackid', $this->languagePack->id)
            ->where('name', GameSettingEnum::HAS_TILE_AUDIO->value)
            ->first();
            
            if(empty($audioFileRequired) || empty($audioFileRequired->value)) {
                return $errors;
            }
        }        

        if($model instanceof Syllable) {
            $audioFileRequired = GameSetting::where('languagepackid', $this->languagePack->id)
            ->where('name', GameSettingEnum::SYLLABLE_AUDIO->value)
            ->first();
            
            if(empty($audioFileRequired) ||  empty($audioFileRequired->value)) {
                return $errors;
            }
        }             

        $itemsWithoutFile = $model::where('languagepackid', $this->languagePack->id)
        ->where(function ($query) use ($model) {
            $query->whereNull('file_id');
            if (in_array('file2_id', $model->getFillable())) {
                $query->orWhereNull('file2_id');
            }
            if (in_array('file3_id', $model->getFillable())) {
                $query->orWhereNull('file3_id');
            }
        })
        ->get();

        $i = count($errors);
        foreach ($itemsWithoutFile as $item) {
            if (empty($item->file_id) || 
                (!empty($item->type2) && empty($item->file2_id)) || 
                (!empty($item->type3) && empty($item->file3_id))) {
                    $errors[$i]['value'] = $item->value;
                    $errors[$i]['type'] = $errorEnum;
                    $errors[$i]['tab'] = $errorEnum->tab()->name();
                    $i++;
            }
        }

        return $errors;
    }    

    public function checkColor(array $errors, Model $model, ErrorTypeEnum $errorTypeEnum): array{
        $itemsWithMissingType = $model::where('languagepackid', $this->languagePack->id)
            ->whereNull('color')
            ->get();
            
        $i = count($errors);

        if (!empty($itemsWithMissingType)) {
            foreach($itemsWithMissingType as $item) {
                $errors[$i]['value'] = $item->value;
                $errors[$i]['type'] = $errorTypeEnum;
                $errors[$i]['tab'] = $errorTypeEnum->tab()->name();    
                $i++;
            }
        }

        return $errors;            
    }    

    public function checkDistractors(array $errors, Model $model, ErrorTypeEnum $errorTypeEnum): array{
        $itemsWithMissingDistractors = $model::where('languagepackid', $this->languagePack->id)
            ->where(function ($query) {
            $query->whereNull('or_1')
                ->orWhereNull('or_2')
                ->orWhereNull('or_3');
            })
            ->get();
            
        $i = count($errors);

        if (!empty($itemsWithMissingDistractors)) {
            foreach($itemsWithMissingDistractors as $item) {
                $errors[$i]['value'] = $item->value;
                $errors[$i]['type'] = $errorTypeEnum;
                $errors[$i]['tab'] = $errorTypeEnum->tab()->name();    
                $i++;
            }
        }

        return $errors;            
    }

    public function checkTypes(array $errors, Model $model, ErrorTypeEnum $errorTypeEnum): array{
        $itemsWithMissingType = $model::where('languagepackid', $this->languagePack->id)
            ->whereNull('type')
            ->get();
            
        $i = count($errors);

        if (!empty($itemsWithMissingType)) {
            foreach($itemsWithMissingType as $item) {
                $errors[$i]['value'] = $item->value;
                $errors[$i]['type'] = $errorTypeEnum;
                $errors[$i]['tab'] = $errorTypeEnum->tab()->name();    
                $i++;
            }
        }

        return $errors;            
    }

    public function checkDuplicates(array $errors, Model $model, ErrorTypeEnum $errorTypeEnum) {
        $duplicates = $model::where('languagepackid', $this->languagePack->id)
            ->selectRaw("value, LOWER(value) COLLATE utf8mb4_bin as normalized_value") // Ensures diacritics are ignored
            ->groupBy('normalized_value', 'value')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('value')
            ->toArray();

        $i = count($errors);

        if (!empty($duplicates)) {
            foreach($duplicates as $duplicate) {
                $errors[$i]['value'] = $duplicate;
                $errors[$i]['type'] = $errorTypeEnum;
                $errors[$i]['tab'] = $errorTypeEnum->tab()->name();    
                $i++;
            }
        }

        return $errors;
    }

    public function checkTileUsage(array $errors): array
    {
        $counTilesService = new CountTilesService($this->languagePack);
        $tileUsage = $counTilesService->handle();

        $i = count($errors);
        foreach ($tileUsage as $tile => $count) {
            if ($count < self::NUM_TIMES_TILES_WANTED_IN_WORDS) {
                $errors[$i]['value'] = $tile . ' (' . $count . ')';
                $errors[$i]['type'] = ErrorTypeEnum::TILE_USAGE;
                $errors[$i]['tab'] = ErrorTypeEnum::TILE_USAGE->tab()->name();
                $i++;
            }
        }

        return $errors;
    }

    public function checkParsingWordsIntoTiles(array $errors): array
    {
        $parseWordsIntoTilesService = new ParseWordsIntoTilesService($this->languagePack);
        $parseErrors = $parseWordsIntoTilesService->handle($errors);

        $i = count($errors);
        foreach ($parseErrors as $word => $parsedTiles) {
            $errors[$i]['value'] = sprintf("%s - the tiles parsed (simple parsing) are: %s", $word, implode(", ", $parsedTiles));
            $errors[$i]['type'] = ErrorTypeEnum::PARSE_WORD_INTO_TILES;
            $errors[$i]['tab'] = ErrorTypeEnum::PARSE_WORD_INTO_TILES->tab()->name();
            $i++;
        }        

        return $errors;
    }

    public function checkParsingWordsIntoKeys(array $errors): array
    {
        $parseWordsIntoKeysService = new ParseWordsIntoKeysService($this->languagePack);
        $parseErrors = $parseWordsIntoKeysService->handle();

        $i = count($errors);
        foreach ($parseErrors as $word => $details) {
            $missingCharacters = $details['missing_characters'] ?? [];

            $message = sprintf("%s - missing key character(s): %s", $word, implode(", ", $missingCharacters));

            $errors[$i]['value'] = $message;
            $errors[$i]['type'] = ErrorTypeEnum::PARSE_WORD_INTO_KEYS;
            $errors[$i]['tab'] = ErrorTypeEnum::PARSE_WORD_INTO_KEYS->tab()->name();
            $i++;
        }

        return $errors;
    }

    public function checkKeyExistsInWords(array $errors): array
    {
        $countKeysService = new CountKeysService($this->languagePack);
        $keyUsage = $countKeysService->handle();

        $i = count($errors);
        foreach ($keyUsage as $key => $count) {
            if ($count === 0) {
                $errors[$i]['value'] = $key;
                $errors[$i]['type'] = ErrorTypeEnum::KEY_NOT_USED_IN_WORDS;
                $errors[$i]['tab'] = ErrorTypeEnum::KEY_NOT_USED_IN_WORDS->tab()->name();
                $i++;
            }
        }

        return $errors;
    }
}
