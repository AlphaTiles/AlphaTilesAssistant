<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\LanguagePack;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleService;
use App\Services\ImportSheetService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public string $token;
    public LanguagePack $languagePack;
    public GoogleService $googleService;
    public string $driveRootFolderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $token, $languagePack, string $driveRootFolderId)
    {
        $this->token = $token;
        $this->languagePack = $languagePack;
        $this->driveRootFolderId = $driveRootFolderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        Log::error('Export Job started for language pack id: ' . $this->languagePack->id);
        $this->googleService = new GoogleService($this->token);                
        $this->googleService->handleExport($this->languagePack, $this->driveRootFolderId);
    }
}
