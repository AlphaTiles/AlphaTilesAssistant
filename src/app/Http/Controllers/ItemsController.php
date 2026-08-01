<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;

class ItemsController extends Controller
{
    public function downloadFile(LanguagePack $languagePack, $filename)
    {
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        if (!file_exists($filePath)) {
            $alternateFilename = str_replace('-', '_', $filename);
            $alternateFilePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$alternateFilename}");

            if (!file_exists($alternateFilePath)) {
                abort(404);
            }

            $filePath = $alternateFilePath;
        }

        return response()->download($filePath, $filename, ['Cache-Control' => 'no-cache, must-revalidate']);
    }
}
