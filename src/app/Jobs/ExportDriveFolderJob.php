<?php

namespace App\Jobs;

use App\Enums\ExportStatus;
use App\Enums\ImportStatus;
use App\Models\LanguagePack;
use Illuminate\Bus\Queueable;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;
use App\Services\ImportSheetService;
use App\Services\LogToDatabaseService;
use Google\Service\Vault\ExportStats;
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
    public ?string $refreshToken = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $token, $languagePack, string $driveRootFolderId, ?string $refreshToken = null)
    {
        $this->token = $token;
        $this->languagePack = $languagePack;
        $this->driveRootFolderId = $driveRootFolderId;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        $this->googleService = new GoogleService($this->languagePack, $this->token, 'export', $this->refreshToken);                
        $this->googleService->handleExport($this->languagePack, $this->driveRootFolderId);
    }
}
