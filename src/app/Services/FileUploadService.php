<?php
namespace App\Services;

use App\Enums\FileTypeEnum;
use App\Models\File;
use App\Models\LanguagePack;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileUploadService
{
    public function handle(LanguagePack $languagePack, array $word, Rule $ruleClass, FileTypeEnum $fileTypeEnum): File
    {
        $fileModel = new File;
        $type = $fileTypeEnum->value;
        $fieldName = "{$type}File";
        if(isset($word[$fieldName])) {
            $fileRules = ["words.*.{$fieldName}" => [            
                $ruleClass,
            ]];            
            $fileValdidation = Validator::make(['words' => [$word]], $fileRules);     
                if($fileValdidation->passes()){
                    $filename = strtolower(preg_replace("/\s+/", "", $word['translation']));
                    $extension = $type === FileTypeEnum::AUDIO->value ? 'mp3' : 'png';
                    $newFileName = $filename . '.' . $extension;
                    $languagePackPath = "languagepacks/{$languagePack->id}/res/raw/";
                    $filePath = $word[$fieldName]->storeAs($languagePackPath, $newFileName, 'public');
                    $fileModel->name = $word[$fieldName]->getClientOriginalName();
                    $fileModel->type = $type;
                    $fileModel->file_path = '/storage/' . $filePath;
                    $fileModel->save();
                }           
        }

        return $fileModel;
    }
}