<?php

namespace App\Http\Controllers;

use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreLangPackRequest;
use App\Repositories\LangInfoRepository;

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
            'id' => '',
            'completedSteps' => ['lang_info'],
            'settings' => $this->langinfoRepository->getSettings(true),
            'tiles' => ''
        ]);
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
            'userid' => Auth::user()->id,
            'name' => $settings['lang_name_english']
        ];

        if(isset($data['id'])) {
            $languagePackSaved = LanguagePack::find($data['id']);
            $languagePackSaved->update($languagePack);
        } else {
            $languagePackSaved = LanguagePack::create($languagePack);
        }

        $this->saveSettings($languagePackSaved, $request);

        if($request->btnNext) {
            return redirect("languagepack/tiles/{$languagePackSaved->id}");    
        }

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
}
