<?php
namespace App\Services;

use ZipArchive;
use App\Models\Tile;
use App\Models\Word;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Log;
use App\Repositories\LangInfoRepository;

class GenerateZipExportService
{
    protected string $tempDir;
    protected LanguagePack $languagePack;
    const SEPARATOR = "\t";                               

    public function __construct(LanguagePack $languagePack)
    {
        $this->languagePack = $languagePack;
        $this->tempDir = sys_get_temp_dir() . '/temp_files';
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir);
        }        
    }

    public function handle(): string
    {
        $zipFileName = $this->languagePack->name;
        $zipFile = sys_get_temp_dir() . '/' . $zipFileName;

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $tilesFileName = 'aa_langinfo.txt';
        $tilesFile = $this->generateLanginfoFile($tilesFileName, $zip, $zipFileName);
        $zip->addFile($tilesFile, "{$zipFileName}/res/raw/{$tilesFileName}");

        $tilesFileName = 'aa_gametiles.txt';
        $tilesFile = $this->generateTilesFile($tilesFileName, $zip, $zipFileName);
        $zip->addFile($tilesFile, "{$zipFileName}/res/raw/{$tilesFileName}");

        $wordlistFileName = 'aa_wordlist.txt';
        $wordsFile = $this->generateWordlistFile($wordlistFileName, $zip, $zipFileName);
        $zip->addFile($wordsFile, "{$zipFileName}/res/raw/{$wordlistFileName}");

        $fontPath = resource_path('font'); 
        $this->addFolderToZip($fontPath, $zip, "/{$zipFileName}/res/font/");

        $avatarPath = resource_path('images/avatars'); 
        $this->addFolderToZip($avatarPath, $zip, "/{$zipFileName}/res/drawable-xxxhdpi/");

        $settingsPath = resource_path('settings'); 
        $this->addFolderToZip($settingsPath, $zip, "/{$zipFileName}/res/raw/");

        return $zipFile;
    }

    public function generateLanginfoFile(string $fileName, ZipArchive $zip, string $zipFileName): string
    {
        $fileContent = "Item\tAnswer\n";

        $settings = app(LangInfoRepository::class)->getSettings(false, $this->languagePack);
        foreach($settings as $setting) {        
            $value = !empty($setting['value']) ? $setting['value'] : 'none';
            $fileContent .= $setting['export_key'] . self::SEPARATOR . $value . "\n";
        }

        $file = "{$this->tempDir}/{$fileName}";
        file_put_contents($file, $fileContent);
        
        return $file;
    }

    public function generateTilesFile(string $tilesFileName, ZipArchive $zip, string $zipFileName): string
    {
        $tiles = Tile::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $fileContent = "tiles\tOr1\tOr2\tOr3\tType\tAudioName\tUpper\t" .
                        "Type2\tAudioName2\tType3\tAudioName3\t" .
                        "Duration1\tDuration2\tDuration3\t" .
                        "FirstAppearsInStage...\tFirstAppearsInStage...(Type2)\t" .
                        "FirstAppearsInStage...(Type3)\n";

        foreach ($tiles as $tile) {            
            $file1 = $tile->file ? basename($tile->file->file_path) : 'X';     
            $file1 = str_replace('.mp3', '', $file1);       
            $type1 = !empty($tile->type) ? $tile->type : 'none';
            $file2 = $tile->file2 ? basename($tile->file2->file_path) : 'X';            
            $file2 = str_replace('.mp3', '', $file2);       
            $type2 = !empty($tile->type2) ? $tile->type2 : 'none';
            $file3 = $tile->file3 ? basename($tile->file3->file_path) : 'X';            
            $file3 = str_replace('.mp3', '', $file3);       
            $type3 = !empty($tile->type3) ? $tile->type3 : 'none';
            $stage1 = $tile->stage ?? '-';
            $stage2 = $tile->stage2 ?? '-';
            $stage3 = $tile->stage3 ?? '-';

            $fileContent .= "{$tile->value}" . self::SEPARATOR .
            "{$tile->or_1}" . self::SEPARATOR .
            "{$tile->or_2}" . self::SEPARATOR .
            "{$tile->or_3}" . self::SEPARATOR .
            "{$type1}" . self::SEPARATOR .
            "{$file1}" . self::SEPARATOR .
            "{$tile->upper}" . self::SEPARATOR .
            "{$type2}" . self::SEPARATOR .
            "{$file2}" . self::SEPARATOR .
            "{$type3}" . self::SEPARATOR .
            "{$file3}" . self::SEPARATOR .
            "0"  . self::SEPARATOR . "0"  . self::SEPARATOR . "0" . self::SEPARATOR .
            "{$stage1}"  . self::SEPARATOR . "{$stage2}"  . self::SEPARATOR . "{$stage3}" . "\n";
        }

        $tilesFile = "{$this->tempDir}/{$tilesFileName}";
        file_put_contents($tilesFile, $fileContent);

        foreach ($tiles as $tile) {
            $this->saveTileFile(1, $tile, $zip, $zipFileName);
            $this->saveTileFile(2, $tile, $zip, $zipFileName);
            $this->saveTileFile(3, $tile, $zip, $zipFileName);
        }

        return $tilesFile;
    }

    public function generateWordlistFile(string $wordlistFileName, ZipArchive $zip, string $zipFileName): string
    {
        $words = Word::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $localLangName = LanguageSetting::where('languagepackid', $this->languagePack->id)
            ->where('name', LangInfoEnum::LANG_NAME_LOCAL->value)->first()->value;
        $fileContent = "FileName\t$localLangName\tDuration\tMixedTypes\tAdjustment\t" .
                        "FirstAppearsInStage(IFOverrulingDefault)...\n";        

        foreach ($words as $word) {            
            $mixedTypes = !empty($word->mixed_types) ? $word->mixed_types : '-';                        
            $fileName = str_replace('.mp3', '', $word->audioFile->name);
            $fileContent .= "{$fileName}" . self::SEPARATOR .
            "{$word->value}" . self::SEPARATOR .
            "0" . self::SEPARATOR .
            "{$mixedTypes}" . self::SEPARATOR .
            "0" . self::SEPARATOR .
            "-" . "\n";

            $this->saveWordlistFile($word, $zip, $zipFileName);
        }

        $wordlistFile = "{$this->tempDir}/{$wordlistFileName}";
        file_put_contents($wordlistFile, $fileContent);

        return $wordlistFile;
    }

    private function saveTileFile(int $nr, Tile $tile, ZipArchive $zip, string $zipFileName): void
    {
        $fileRelation = $nr > 1 ? "file{$nr}" : 'file';

        if($tile->{$fileRelation}) {
            $file = basename($tile->{$fileRelation}->file_path);
            $resourceFile = "app/public/languagepacks/{$this->languagePack->id}/res/raw/{$file}";
            $outputFolder = "{$zipFileName}/res/raw/{$file}";
            $zip->addFile(storage_path($resourceFile), $outputFolder);        
        }
    }

    private function saveWordlistFile(Word $word, ZipArchive $zip, string $zipFileName): void
    {
        if($word->audioFile) {
            $file = basename($word->audioFile->file_path);
            $resourceFile = "app/public/languagepacks/{$this->languagePack->id}/res/raw/{$file}";
            $outputFolder = "{$zipFileName}/res/raw/{$file}";
            $zip->addFile(storage_path($resourceFile), $outputFolder);        
        }

        if($word->imageFile) {
            $file = basename($word->imageFile->file_path);
            $resourceFile = "app/public/languagepacks/{$this->languagePack->id}/res/raw/{$file}";
            $outputFolder = "{$zipFileName}/res/drawable-xxxhdpi/{$file}";
            $zip->addFile(storage_path($resourceFile), $outputFolder);        
        }
    }

    private function addFolderToZip($folder, $zip, $outputFolder)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $zip->addFile($filePath, $outputFolder . basename($file));
            }
        }
    }    
}