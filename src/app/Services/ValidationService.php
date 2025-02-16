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
        }

        if(empty($tab) || $tab === TabEnum::SYLLABLE) {
            $errors = $this->checkDuplicates($errors, new Syllable(), ErrorTypeEnum::DUPLICATE_SYLLABLE);
        }

        if(empty($tab) || $tab === TabEnum::TILE) {
            $errors = $this->checkDuplicates($errors, new Tile(), ErrorTypeEnum::DUPLICATE_TILE);
        }

        $groupedErrors = collect($errors)->groupBy('type')->sortBy('tab');

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

    public function checkDuplicates(array $errors, Model $model, ErrorTypeEnum $errorTypeEnum) {
        $duplicates = $model::where('languagepackid', $this->languagePack->id)
            ->selectRaw("value COLLATE utf8mb4_bin as normalized_value") // Ensures diacritics are ignored
            ->groupBy('normalized_value')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_value')
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
}
