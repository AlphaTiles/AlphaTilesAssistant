<?php

namespace App\Services;

use App\Models\File;
use App\Models\Tile;
use App\Models\Word;
use App\Enums\FileTypeEnum;
use App\Enums\ImportStatus;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SheetService
{
    protected GoogleService $googleService;
    protected LanguagePack $languagePack;

    public function __construct(LanguagePack $languagePack, string $googleToken)
    {
        $this->languagePack = $languagePack;    
        $this->googleService = new GoogleService($googleToken);  
    }

    public function readAndSaveData(string $downloadPath)
    {
        $spreadsheet = IOFactory::load($downloadPath);
        $this->saveLanginfo($spreadsheet);
        $this->saveTiles($spreadsheet);
        $this->saveWords($spreadsheet);

        $this->languagePack->import_status = ImportStatus::SUCCESS->value;
        $this->languagePack->save();
        Log::error("import complete");
    }

    private function saveLanginfo(Spreadsheet $spreadsheet)
    {
        $worksheet = $spreadsheet->getSheetByName('langinfo');
        $rows = $worksheet->toArray();
        
        $langInfoExportLabels = [];
        foreach(LangInfoEnum::cases() as $langInfoEnum) {
            $langInfoExportLabels[$langInfoEnum->value] = $langInfoEnum->exportKey();
        }

        $key = 0;
        foreach ($rows as $row) {
            $langInfoEnumKey = array_search($row[0], $langInfoExportLabels);

            if($langInfoEnumKey && $langInfoEnumKey !== LangInfoEnum::LANG_NAME_LOCAL) {
                $settings[$key]['languagepackid'] = $this->languagePack->id;
                $settings[$key]['name'] = LangInfoEnum::from($langInfoEnumKey)->value;
                $settings[$key]['value'] = $row[1];
                $key++;
            }
        }            

        LanguageSetting::insert($settings);
    }

    private function saveTiles(Spreadsheet $spreadsheet)
    {
        $worksheet = $spreadsheet->getSheetByName('gametiles');
        $rows = $worksheet->toArray();
        
        $firstRow = true;
        foreach ($rows as $row) {
            if ($firstRow) {
                $firstRow = false;
                continue; 
            }

            if(!empty($row[0])) {
                $tile['languagepackid'] = $this->languagePack->id;
                $tile['value'] = $row[0];                
                $tile['or_1'] = $row[1];
                $tile['or_2'] = $row[2];
                $tile['or_3'] = $row[3];
                $tile['type'] = $row[4];
                $tile['upper'] = $row[6];
                $tile['type2'] = trim($row[7]) === 'none' ? null : $row[7];
                $tile['type3'] = trim($row[9]) === 'none' ? null : $row[9];
                $tile['stage'] = $row[14] === '-' ? null : $row[14];
                $tile['stage2'] = $row[15] === '-' ? null : $row[15];
                $tile['stage3'] = $row[16] === '-' ? null : $row[16];

                $myTile = Tile::create($tile);

                $this->uploadTileFile(1, $myTile, $row[5]);
                $this->uploadTileFile(2, $myTile, $row[8]);
                $this->uploadTileFile(3, $myTile, $row[10]);

            }

            
        }            
    }

    private function uploadTileFile(int $fileNr, Tile $myTile, string $fileName): void
    {
        if(strtolower($fileName) === 'x') {
            return;
        }

        $file = $fileName . '.mp3';
        $driveFileId = $this->googleService->getFileIdByFileName($file, 'audio_tiles_optional');
        $path = "public/languagepacks/{$this->languagePack->id}/res/raw/";
        $fileNr = 1;
        $newFileName = "tile_" .  str_pad($myTile->id, 3, '0', STR_PAD_LEFT) . '_' . $fileNr . '.mp3';
        $this->googleService->saveFile($path, $driveFileId, $newFileName);
        
        $fileModel = new File();
        $fileModel->name = $file;
        $fileModel->file_path = '/storage/' . $path;
        $fileModel->save();
        $myTile->file_id = $fileModel->id;
        $myTile->save();
    }

    private function saveWords(Spreadsheet $spreadsheet)
    {
        $worksheet = $spreadsheet->getSheetByName('wordlist');
        $rows = $worksheet->toArray();
        
        $firstRow = true;
        foreach ($rows as $row) {
            if ($firstRow) {
                $firstRow = false;
                continue; 
            }

            if(!empty($row[0])) {
                $word['languagepackid'] = $this->languagePack->id;
                $word['value'] = $row[1];                
                $word['mixed_types'] = $row[3];                

                $myWord = Word::create($word);
                $this->uploadWordFile($myWord, $row[0], FileTypeEnum::AUDIO);
                $this->uploadWordFile($myWord, $row[0], FileTypeEnum::IMAGE);
            }
        }            
    }    

    private function uploadWordFile(Word $myWord, string $fileName, FileTypeEnum $fileTypeEnum): void
    {
        if(strtolower($fileName) === 'x') {
            return;
        }

        $extension = $fileTypeEnum === FileTypeEnum::AUDIO ? 'mp3' : 'png';        
        $folder = $fileTypeEnum === FileTypeEnum::AUDIO ? 'audio_words' : 'images_words';                
        $file = $fileName . '.' . $extension;
        $driveFileId = $this->googleService->getFileIdByFileName($file, $folder);
        $path = "public/languagepacks/{$this->languagePack->id}/res/raw/";
        $newFileName = "word_" .  str_pad($myWord->id, 3, '0', STR_PAD_LEFT) . '.' . $extension;
        $this->googleService->saveFile($path, $driveFileId, $newFileName);
        
        $fileModel = new File();
        $fileModel->name = $file;
        $fileModel->type = $fileTypeEnum->value;
        Log::error('/storage' . str_replace('public', '', $path) . $newFileName);
        $fileModel->file_path = '/storage' . str_replace('public', '', $path) . $newFileName;
        $fileModel->save();
        $columnName = $fileTypeEnum === FileTypeEnum::AUDIO ? 'audiofile_id' : 'imagefile_id';
        $myWord->{$columnName} = $fileModel->id;
        $myWord->save();
    }
}
