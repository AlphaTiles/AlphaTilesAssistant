<?php
namespace App\Services;

use App\Enums\LangInfoEnum;
use ZipArchive;
use App\Models\Tile;
use App\Models\Word;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Log;

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

        $tilesFileName = 'aa_gametiles.txt';
        $tilesFile = $this->generateTilesFile($tilesFileName, $zip, $zipFileName);
        $zip->addFile($tilesFile, "{$zipFileName}/res/raw/{$tilesFileName}");

        $wordlistFileName = 'aa_wordlist.txt';
        $wordsFile = $this->generateWordlistFile($wordlistFileName, $zip, $zipFileName);
        $zip->addFile($wordsFile, "{$zipFileName}/res/raw/{$wordlistFileName}");

        // $resourceFile = 'app/public/languagepacks/4/res/raw/culebra.mp3';
        // $outputFolder = "{$zipFileName}/res/raw/culebra.mp3";
        // $zip->addFile(storage_path($resourceFile), $outputFolder);
        // $zip->close();

        return $zipFile;
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
            $file1 = $tile->file ? basename($tile->file->file_path) : '';            
            $type1 = !empty($tile->type) ? $tile->type : 'none';
            $file2 = $tile->file2 ? basename($tile->file2->file_path) : '';            
            $type2 = !empty($tile->type2) ? $tile->type2 : 'none';
            $file3 = $tile->file3 ? basename($tile->file3->file_path) : '';            
            $type3 = !empty($tile->type3) ? $tile->type3 : 'none';

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
            "-"  . self::SEPARATOR . "-"  . self::SEPARATOR . "-" . "\n";
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
        $fileContent = "English\t$localLangName\tDuration\tMixedTypes\tAdjustment\t" .
                        "FirstAppearsInStage(IFOverrulingDefault)...\n";        

        foreach ($words as $word) {            
            $mixedTypes = !empty($word->mixed_types) ? $word->mixed_types : '-';                        
            $fileContent .= "{$word->translation}" . self::SEPARATOR .
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
            $resourceFile = "app/public/languagepacks/4/res/raw/{$file}";
            $outputFolder = "{$zipFileName}/res/raw/{$file}";
            $zip->addFile(storage_path($resourceFile), $outputFolder);        
        }
    }

    private function saveWordlistFile(Word $word, ZipArchive $zip, string $zipFileName): void
    {
        if($word->file) {
            $file = basename($word->file->file_path);
            $resourceFile = "app/public/languagepacks/4/res/raw/{$file}";
            $outputFolder = "{$zipFileName}/res/raw/{$file}";
            $zip->addFile(storage_path($resourceFile), $outputFolder);        
        }
    }
}