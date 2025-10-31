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
        if(isset($request->settings)) {
            $key = 0;
            foreach($request->settings as $key => $setting) {
                $removeGoogleServicesFileFlag = $key == 'google_services_json_remove';

                if ($removeGoogleServicesFileFlag) {
                    // try to resolve a File model first (numeric id or matching name/path)
                    $fileRecord = null;                    
                    $googleSerivcesFile = GameSetting::where('languagepackid', $languagePack->id)
                        ->where('name', GameSettingEnum::GOOGLE_SERVICES_JSON->value)
                        ->firstOrFail();
                    $fileRecord = File::find((int) $googleSerivcesFile->value);

                    if ($fileRecord) {
                        // delete the file from storage
                        $relative = ltrim(str_replace('/storage/', '', $fileRecord->file_path), '/');
                        Storage::disk('public')->delete($relative);
                        // delete the file record
                        $fileRecord->delete();
                        $googleSerivcesFile->delete();
                    }
                    // do not re-insert this setting
                    continue;
                }

                if($key === GameSettingEnum::GOOGLE_SERVICES_JSON->value) {
                    // Ensure we pass an array to the service (it may be a string from the request)
                    $itemForUpload = is_array($setting) ? $setting : [
                        'languagepackid' => $languagePack->id,
                        'file' => $setting
                    ];

                    $fileModel = $this->upload($itemForUpload, 'google_services.json');
                    $setting = $fileModel ? $fileModel->id : null;
                }

                if(isset($setting)) {
                    GameSetting::updateOrCreate(
                        ['languagepackid' => $languagePack->id, 'name' => $key],
                        ['value' => $setting]
                    );
                }
            }            
        }
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
