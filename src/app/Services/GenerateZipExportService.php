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
            if($tile->file) {
                $file = basename($tile->file->file_path);
                $resourceFile = "app/public/languagepacks/4/res/raw/{$file}";
                $outputFolder = "{$zipFileName}/res/raw/{$file}";
                $zip->addFile(storage_path($resourceFile), $outputFolder);        
            }
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
            $file = $tile->file ? basename($tile->file->file_path) : '';            
            $type = !empty($tile->type) ? $tile->type : 'none';

            $fileContent .= "{$tile->value}" . $separator .
            "{$tile->or_1}" . $separator .
            "{$tile->or_2}" . $separator .
            "{$tile->or_3}" . $separator .
            "{$type}" . $separator .
            "{$file}" . $separator .
            "{$tile->upper}" . "\n";
        }

        $tilesFile = "{$this->tempDir}/{$tilesFileName}";
        file_put_contents($tilesFile, $fileContent);

        return $tilesFile;
    }
}