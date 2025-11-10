<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\GameSetting;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Enums\GameSettingEnum;
use App\Enums\LangInfoEnum;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Repositories\GameSettingsRepository;
use App\Http\Requests\StoreGameSettingsRequest;
use App\Models\LanguageSetting;
use Carbon\Language;

class GameSettingsController extends Controller
{
    protected $gameSettingsRepository;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(GameSettingsRepository $gameSettingsRepository)
    {
        $this->middleware('auth');

        $this->gameSettingsRepository = $gameSettingsRepository;
    }

    /**
     * Edit the game settings
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {       
        $create = !GameSetting::where('languagepackid', $languagePack->id)->exists();

        return view('languagepack.game_settings', [
            'languagePack' => $languagePack,
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings'],
            'settings' => $this->gameSettingsRepository->getSettings($create, $languagePack),
            'tiles' => ''
        ]);
    }

    public function update(LanguagePack $languagePack, StoreGameSettingsRequest $request)
    {
        $this->saveSettings($languagePack, $request);

        session()->flash('success', 'Records updated successfully');    

        return redirect("languagepack/game_settings/{$languagePack->id}");    
    }    
    
    private function saveSettings(LanguagePack $languagePack, StoreGameSettingsRequest $request): void
    {
        if (empty($request->settings)) {
            return;
        }

        foreach ($request->settings as $key => $value) {
            if ($this->isGoogleServicesFileRemoval($key)) {
                $this->removeGoogleServicesFile($languagePack);
                continue;
            }

            if ($this->isGoogleServicesFileUpload($key)) {
                $value = $this->handleGoogleServicesFileUpload($languagePack, $value);
            }

            if (isset($value)) {
                GameSetting::updateOrCreate(
                    [
                        'languagepackid' => $languagePack->id,
                        'name' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    /**
     * Determine if this key indicates Google Services JSON removal.
     */
    private function isGoogleServicesFileRemoval(string $key): bool
    {
        return $key === 'google_services_json_remove';
    }

    /**
     * Determine if this key indicates a Google Services JSON upload.
     */
    private function isGoogleServicesFileUpload(string $key): bool
    {
        return $key === GameSettingEnum::GOOGLE_SERVICES_JSON->value;
    }

    /**
     * Handle deletion of the Google Services file and its database record.
     */
    private function removeGoogleServicesFile(LanguagePack $languagePack): void
    {
        $googleServicesSetting = GameSetting::where('languagepackid', $languagePack->id)
            ->where('name', GameSettingEnum::GOOGLE_SERVICES_JSON->value)
            ->first();

        if (!$googleServicesSetting) {
            return;
        }

        $file = File::find((int) $googleServicesSetting->value);
        if ($file) {
            $relativePath = ltrim(str_replace('/storage/', '', $file->file_path), '/');
            Storage::disk('public')->delete($relativePath);
            $file->delete();
        }

        $googleServicesSetting->delete();
    }

    /**
     * Handle upload and storage of a Google Services file.
     */
    private function handleGoogleServicesFileUpload(LanguagePack $languagePack, mixed $value): ?int
    {
        $uploadData = is_array($value)
            ? $value
            : [
                'languagepackid' => $languagePack->id,
                'file' => $value,
            ];

        $fileModel = $this->upload($uploadData, 'google_services.json');
        // process google-services.json to extract and store APP_ID if present
        if ($fileModel) {
            $this->extractAppIdFromGoogleServicesJson($fileModel, $languagePack);
        }

        return $fileModel?->id;
    }    

    public function upload(array $item, $fileName): ?File
    {
        $fileField = 'file';

        if(!isset($item[$fileField])) {
            return null;
        }

        $fileModel = new File;

        $languagePackPath = "languagepacks/{$item['languagepackid']}/res/raw";
        $filePath = $item[$fileField]->storeAs($languagePackPath, $fileName, 'public');
        $fileModel->name = $item[$fileField]->getClientOriginalName();
        $fileModel->file_path = '/storage/' . $filePath;
        $fileModel->save();

        return $fileModel;
    }

    /**
     * Parse google-services.json file and persist APP_ID game setting when found.
     */
    private function extractAppIdFromGoogleServicesJson(File $fileModel, LanguagePack $languagePack): void
    {
        $ethnologueCode = LanguageSetting::where('languagepackid', $languagePack->id)
            ->where('name', LangInfoEnum::ETHNOLOGUE_CODE->value)
            ->first()?->value;

        try {
            $relative = ltrim(str_replace('/storage/', '', $fileModel->file_path), '/');
            $fileContent = Storage::disk('public')->get($relative);
        } catch (\Throwable $e) {
            Log::error('Failed to read google-services.json: ' . $e->getMessage());
            return;
        }

        $jsonData = json_decode($fileContent, true);
        if (!is_array($jsonData) || !isset($jsonData['client']) || !is_array($jsonData['client'])) {
            return;
        }

        foreach ($jsonData['client'] as $client) {
            $packageName = $client['client_info']['android_client_info']['package_name'] ?? null;
            if (empty($packageName)) {
                continue;
            }

            $parts = explode('.', $packageName);
            $appId = end($parts);
            if (empty($appId)) {
                continue;
            }

            // get first 3 letters of appId and apply any project-specific check
            $packageEthnologueCode = substr($appId, 0, 3);
            if (strtolower($ethnologueCode) === strtolower($packageEthnologueCode)) {
                Log::info("Extracted App ID: {$appId} from package name: {$packageName}");
                GameSetting::updateOrCreate(
                    [
                        'languagepackid' => $languagePack->id,
                        'name' => GameSettingEnum::APP_ID->value,
                    ],
                    [
                        'value' => $appId,
                    ]
                );
            }
        }
    }
}
