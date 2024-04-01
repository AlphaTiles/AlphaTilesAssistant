<?php
namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TileFileUploadService
{
    public function handle(array $tile, int $fileNr, string $fileRules): ?File
    {
        $fileField = $fileNr > 1 ? "file{$fileNr}" : 'file';

        if(!isset($tile[$fileField])) {
            return null;
        }

        $fileModel = new File;
        $fileValdidation = Validator::make(
            ['tiles' => [$tile]], 
            ["tiles.*.{$fileField}" => $fileRules]
        );                        
        if($fileValdidation->passes()){
            $newFileName = "tile_" .  str_pad($tile['id'], 3, '0', STR_PAD_LEFT) . '_' . $fileNr . '.mp3';
            $languagePackPath = "languagepacks/{$tile['languagepackid']}/res/raw/";
            $filePath = $tile[$fileField]->storeAs($languagePackPath, $newFileName, 'public');
            $fileModel->name = $tile[$fileField]->getClientOriginalName();
            $fileModel->file_path = '/storage/' . $filePath;
            $fileModel->save();

            return $fileModel;
        }           

        return null;        
    }
}