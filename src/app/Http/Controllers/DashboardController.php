<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;
use App\Models\Test;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {        
        $userid = Auth::user()->id;

        return view('dashboard', [
            'languagepacks' => LanguagePack::where('user_id', $userid)
                ->orWhereHas('collaborators', function ($query) use ($userid) {
                    $query->where('user_id', $userid);
                })
                ->orderByDesc('created_at')->get()
        ]);
    }
}
