<?php
namespace App\Services;

use ZipArchive;
use App\Models\Tile;
use App\Models\LanguagePack;
use Illuminate\Support\Facades\Log;

class GenerateZipExportService
{
    protected string $tempDir;
    protected LanguagePack $languagePack;

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
        $tilesFile = $this->generateTilesFile($tilesFileName);
        $zip->addFile($tilesFile, "{$zipFileName}/res/raw/{$tilesFileName}");

        $tiles = Tile::where('languagepackid', $this->languagePack->id)->get();
        foreach ($tiles as $tile) {
            $this->saveTileFile(1, $tile, $zip, $zipFileName);
            $this->saveTileFile(2, $tile, $zip, $zipFileName);
            $this->saveTileFile(3, $tile, $zip, $zipFileName);
        }

        $resourceFile = 'app/public/languagepacks/4/res/raw/culebra.mp3';
        $outputFolder = "{$zipFileName}/res/raw/culebra.mp3";
        $zip->addFile(storage_path($resourceFile), $outputFolder);
        $zip->close();

        return $zipFile;
    }

    public function generateTilesFile(string $tilesFileName): string
    {
        $tiles = Tile::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $fileContent = "tiles\tOr1\tOr2\tOr3\tType\tAudioName\tUpper\t" .
                        "Type2\tAudioName2\tType3\tAudioName3\n";

        foreach ($tiles as $tile) {            
            $separator = "\t";                               
            $file1 = $tile->file ? basename($tile->file->file_path) : '';            
            $type1 = !empty($tile->type) ? $tile->type : 'none';
            $file2 = $tile->file2 ? basename($tile->file2->file_path) : '';            
            $type2 = !empty($tile->type2) ? $tile->type2 : 'none';
            $file3 = $tile->file3 ? basename($tile->file3->file_path) : '';            
            $type3 = !empty($tile->type3) ? $tile->type3 : 'none';

            $fileContent .= "{$tile->value}" . $separator .
            "{$tile->or_1}" . $separator .
            "{$tile->or_2}" . $separator .
            "{$tile->or_3}" . $separator .
            "{$type1}" . $separator .
            "{$file1}" . $separator .
            "{$tile->upper}" . $separator .
            "{$type2}" . $separator .
            "{$file2}" . $separator .
            "{$type3}" . $separator .
            "{$file3}" . "\n";
        }

        $tilesFile = "{$this->tempDir}/{$tilesFileName}";
        file_put_contents($tilesFile, $fileContent);

        return $tilesFile;
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
}