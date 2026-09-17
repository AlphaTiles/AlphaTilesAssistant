<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class NotesController extends BaseItemController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->route = 'notes';
        $this->model = new Note();
        $this->fileKeyname = 'note';

        parent::__construct();
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {
        $items = Note::where('languagepackid', $languagePack->id)
            ->orderBy('id')
            ->paginate(config('pagination.default'));

        return view('languagepack.notes', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'games', 'notes'],
            'languagePack' => $languagePack,
            'items' => $items,
            'pagination' => $items->links(),
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        Note::create([
            'languagepackid' => $languagePack->id,
            'text' => $request->input('text'),
        ]);

        $totalPages = (int) ceil(Note::where('languagepackid', $languagePack->id)->count() / config('pagination.default'));

        return redirect("languagepack/notes/{$languagePack->id}?page={$totalPages}");
    }

    public function update(LanguagePack $languagePack, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items.*.id' => 'required|integer',
            'items.*.text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        foreach ($request->input('items', []) as $item) {
            Note::where('id', $item['id'])
                ->where('languagepackid', $languagePack->id)
                ->update(['text' => $item['text']]);
        }

        session()->flash('success', 'Records updated successfully');

        // return the view directly (not a redirect) so the delete confirmation
        // step below can see the submitted 'items' delete checkboxes
        $itemCollection = Note::where('languagepackid', $languagePack->id)
            ->orderBy('id')
            ->paginate(config('pagination.default'));

        return view('languagepack.notes', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'games', 'notes'],
            'languagePack' => $languagePack,
            'items' => $itemCollection,
            'pagination' => $itemCollection->links(),
        ]);
    }
}
