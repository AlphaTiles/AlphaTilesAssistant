<?php

namespace App\Services;

use App\Models\Word;
use App\Enums\ErrorType;
use App\Enums\ErrorTypeEnum;
use App\Models\LanguagePack;

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
    public function handle(): array
    {
        $errors = $this->checkWordFilesMissing();

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
                $errors[$i]['message'] = "An audio file is required";
                $errors[$i]['value'] = $word->value;
                $errors[$i]['type'] = ErrorTypeEnum::MISSING_WORD_AUDIO_FILE;
                $i++;
            }
            if (empty($word->imagefile_id)) {
                $errors[$i]['message'] = "An image file is required";
                $errors[$i]['value'] = $word->value;
                $errors[$i]['type'] = ErrorTypeEnum::MISSING_WORD_IMAGE_FILE;
                $i++;
            }
        }

        return $errors;
    }
}
