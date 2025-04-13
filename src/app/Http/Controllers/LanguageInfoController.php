<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\Tile;
use App\Models\User;
use App\Models\Word;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Repositories\LangInfoRepository;
use App\Http\Requests\StoreLangPackRequest;

class LanguageInfoController extends Controller
{
    protected $langinfoRepository;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(LangInfoRepository $langinfoRepository)
    {
        $this->middleware('auth');

        $this->langinfoRepository = $langinfoRepository;
    }

    /**
     * Create the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {        
        return view('languagepack.info', [
            'languagePack' => null,
            'completedSteps' => ['lang_info'],
            'settings' => $this->langinfoRepository->getSettings(true),
            'tiles' => ''
        ]);
    }

    public function destroy(LanguagePack $languagePack)
    {
        try {            
            Storage::move("public/languagepacks/$languagePack->id", "public/languagepacks/old-$languagePack->id");
            LanguageSetting::where('languagepackid', $languagePack->id)->delete();
            Tile::where('languagepackid', $languagePack->id)->delete();
            Word::where('languagepackid', $languagePack->id)->delete();
            Key::where('languagepackid', $languagePack->id)->delete();
            $languagePack->delete();

            return response()->json(['message' => 'Language pack deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete the language pack'], 500);
        }
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {       
        return view('languagepack.info', [
            'languagePack' => $languagePack,
            'completedSteps' => ['lang_info'],
            'settings' => $this->langinfoRepository->getSettings(false, $languagePack),
            'tiles' => ''
        ]);
    }

    public function store(StoreLangPackRequest $request)
    {
        $data = $request->all();
        $settings = $data['settings'];

        $languagePack = [
            'user_id' => Auth::user()->id,
            'name' => $settings['lang_name_english']
        ];

        if(isset($data['id'])) {
            $languagePackSaved = LanguagePack::find($data['id']);
            $languagePackSaved->update($languagePack);
        } else {
            $languagePackSaved = LanguagePack::create($languagePack);
        }

        $this->saveSettings($languagePackSaved, $request);

        return redirect("languagepack/edit/{$languagePackSaved->id}");    
    }    
    
    private function saveSettings(LanguagePack $languagePack, StoreLangPackRequest $request): void
    {
        LanguageSetting::where('languagepackid', $languagePack->id)
            ->delete();

        $settings = [];

        if(isset($request->settings)) {
            $key = 0;
            foreach($request->settings as $key => $setting) {
                if(!empty($setting)) {
                    $settings[$key]['languagepackid'] = $languagePack->id;
                    $settings[$key]['name'] = $key;
                    $settings[$key]['value'] = $setting;
                    $key++;
                }
            }    
            
            LanguageSetting::insert($settings);
        }
    }

    public function removeCollaborator(LanguagePack $languagePack, User $user) 
    {
        $collaborator = $languagePack->collaborators()->where('user_id', $user->id)->first();

        if ($collaborator) {
            $collaborator->delete();
            return redirect('/dashboard')->with('success', 'Languagepack removed successfully from account.');
        }

        return redirect()->back()->with('error', 'Collaborator not found.');
    }
}
