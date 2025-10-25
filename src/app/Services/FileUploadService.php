<?php
namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileUploadService
{
    public function handle(array $item, string $itemType, int $fileNr, string $fileRules, string $extension): ?File
    {
        $fileField = $fileNr > 1 ? "file{$fileNr}" : 'file';

        if(!isset($item[$fileField])) {
            return null;
        }

        $fileModel = new File;
        $fileValdidation = Validator::make(
            ["{$itemType}s" => [$item]], 
            ["{$itemType}s.*.{$fileField}" => $fileRules]
        );                        
        if($fileValdidation->passes()){
            $newFileName = "{$itemType}_" .  str_pad($item['id'], 3, '0', STR_PAD_LEFT) . '_' . $fileNr . '.' . $extension;
            $languagePackPath = "languagepacks/{$item['languagepackid']}/res/raw";
            $filePath = $item[$fileField]->storeAs($languagePackPath, $newFileName, 'public');
            $fileModel->name = $item[$fileField]->getClientOriginalName();
            $fileModel->file_path = '/storage/' . $filePath;
            $fileModel->save();

            return $fileModel;
        }           

        return null;        
    }
}