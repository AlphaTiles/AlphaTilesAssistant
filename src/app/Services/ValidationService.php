<?php

namespace App\Services;

use App\Models\Tile;
use App\Models\Word;
use App\Enums\TabEnum;
use App\Enums\ErrorTypeEnum;
use App\Models\Key;
use App\Models\LanguagePack;
use App\Models\Syllable;
use Illuminate\Database\Eloquent\Model;

class ValidationService
{
    const NUM_TIMES_KEYS_WANTED_IN_WORDS = 5;
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
        }

        if(empty($tab) || $tab === TabEnum::KEY) {
            $errors = $this->checkDuplicates($errors, new Key(), ErrorTypeEnum::DUPLICATE_KEY);
            $errors = $this->checkColor($errors, new Key(), ErrorTypeEnum::COLOR_KEY);
        }

        if(empty($tab) || $tab === TabEnum::SYLLABLE) {
            $errors = $this->checkDuplicates($errors, new Syllable(), ErrorTypeEnum::DUPLICATE_SYLLABLE);
            $errors = $this->checkDistractors($errors, new Syllable(), ErrorTypeEnum::EMPTY_DISTRACTOR_SYLLABLE);
        }

        if(empty($tab) || $tab === TabEnum::TILE) {
            $errors = $this->checkDuplicates($errors, new Tile(), ErrorTypeEnum::DUPLICATE_TILE);
            $errors = $this->checkDistractors($errors, new Tile(), ErrorTypeEnum::EMPTY_DISTRACTOR_TILE);
            $errors = $this->checkTypes($errors, new Tile(), ErrorTypeEnum::EMPTY_TYPE_TILE);
        }

        if(empty($tab)) {
            $errors = $this->checkTileUsage($errors);
            $errors = $this->checkKeyUsage($errors);
        }

        $groupedErrors = collect($errors)->sortBy('tab')->groupBy('type');

        return $groupedErrors->toArray();
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

    public function checkKeyUsage(array $errors): array
    {
        $counKeysService = new CountKeysService($this->languagePack);
        $keyUsage = $counKeysService->handle();

        $i = count($errors);
        foreach ($keyUsage as $key => $count) {
            if ($count < self::NUM_TIMES_KEYS_WANTED_IN_WORDS) {
                $errors[$i]['value'] = $key . ' (' . $count . ')';
                $errors[$i]['type'] = ErrorTypeEnum::KEY_USAGE;
                $errors[$i]['tab'] = ErrorTypeEnum::KEY_USAGE->tab()->name();
                $i++;
            }
        }

        return $errors;
    }    
}
