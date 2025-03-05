<?php

namespace App\Http\Controllers;

use App\Models\Key;
use App\Enums\TabEnum;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationService;
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
    public function edit(LanguagePack $languagePack, string $key = null)
    {        
        session()->forget('success');

        $keys = Key::where('languagepackid', $languagePack->id)
        ->when(!empty($key), function ($query) use ($key) {
            return $query->where('value', $key);
        })        
        ->get();  

        $validationErrors = null;
        if(empty($key)) {
            $validationService = (new ValidationService($languagePack));
            $validationErrors = $validationService->handle(TabEnum::KEY);    
        }
        

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
            'defaultKeys' => $defaultKeys,
            'validationErrors' => $validationErrors
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $this->validateAddItems($request, $languagePack, new Key(), 'keys');

        $data = $request->all();
        $keys = explode("\r\n", $data['add_items']);

        foreach($keys as $i => $key) {
            if(!empty($key)) {
                $keyRecord = Key::withTrashed()->where([
                    'languagepackid' => $languagePack->id,
                    'value' => $key
                ])->first();
                
                if ($keyRecord) {
                    // If the record is soft-deleted, restore it
                    if ($keyRecord->trashed()) {
                        $keyRecord->restore();
                    }
                } else {
                    $keyRecord = Key::create([
                        'languagepackid' => $languagePack->id,
                        'value' => $key
                    ]);
                }               
            }
        }
        
        return redirect("languagepack/keyboard/{$languagePack->id}");    
    }        


    public function update(LanguagePack $languagePack, Request $request)
    {
        $keys = $request->all()['items'];

        $validator = Validator::make(
            $request->all(), 
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.value' => [
                    'required_unless:items.*.delete,1',
                ],
            ],
            [                
                'items.*.value' => '',
                'items.*.color' => '',
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
