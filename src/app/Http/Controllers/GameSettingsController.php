<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\GameSetting;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Enums\GameSettingEnum;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Repositories\GameSettingsRepository;
use App\Http\Requests\StoreGameSettingsRequest;

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
}
