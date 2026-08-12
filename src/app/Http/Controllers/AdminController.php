<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * List every language pack in the system.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function languagePacks()
    {
        return view('admin.languagepacks', [
            'languagepacks' => LanguagePack::with('owner')
                ->orderByDesc('created_at')
                ->get()
        ]);
    }
}
