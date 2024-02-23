<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class KeyboardController extends BaseItemController
{
    public function __construct(Request $request)
    {
        $this->route = 'keyboard';
        $this->model = new Key();

        parent::__construct($request);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {        
        session()->forget('success');

        $keys = Key::where('languagepackid', $languagePack->id)->get();        

        $defaultKeys = '';
        if(count($keys) === 0) {
            foreach(range('a', 'z') as $letter) {
                $defaultKeys .= $letter . "\n";
            }
        }

        return view('languagepack.keyboard', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard'],
            'languagePack' => $languagePack,
            'keys' => $keys,
            'defaultKeys' => $defaultKeys
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $data = $request->all();
        $keys = explode("\r\n", $data['add_keys']);

        $insert = [];
        foreach($keys as $i => $key) {
            if(!empty($key)) {
                Key::firstOrCreate([
                    'languagepackid' => $languagePack->id,
                    'value' => $key
                ]);
            }
        }
        
        return redirect("languagepack/keyboard/{$languagePack->id}");    
    }        


    public function update(LanguagePack $languagePack, Request $request)
    {
        $keys = $request->all()['keys'];

        $validator = Validator::make(
            $request->all(), 
            [
                'keys.*' => [
                    'required_unless:keys.*.delete,1',
                    new CustomRequired(request(), 'color')
                ],
                'keys.*.languagepackid' => ['required', 'integer'],
                'keys.*.value' => [
                    'required_unless:keys.*.delete,1',
                ],
                'keys.*.color' => ['required_unless:keys.*.delete,1'],
            ],
            [                
                'keys.*.value' => '',
                'keys.*.color' => '',
            ]
        );

        DB::transaction(function() use($keys, $validator) {
            foreach($keys as $key) {

                if(empty($key['value'])) {
                    continue;
                }

                $updateData = [
                    'value' => $key['value'],
                    'color' => $key['color'],
                ];                
                
                Key::where(['id' => $key['id']])
                    ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        session()->flash('success', 'Records updated successfully');        
        $keysCollection = Key::where('languagepackid', $languagePack->id)->get();

        return view('languagepack.keyboard', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard'],
            'languagePack' => $languagePack,
            'keys' => $keysCollection,
            'defaultKeys' => ''
        ]);
    }
}
