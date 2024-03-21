<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ImportDriveFolderJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class GoogleDriveController extends Controller
{        
    public function import()
    {        
        $this->middleware('auth');

        return view('drive-import', [
            'accessToken' => Session::get("socialite_token"),
            'userId' => Auth::user()->id
        ]);
    }

    public function dispatchimport(Request $request)
    {
        ImportDriveFolderJob::dispatch($request->userId, $request->token, $request->folderId);
    }    
}
