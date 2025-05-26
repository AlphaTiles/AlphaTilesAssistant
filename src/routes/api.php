<?php

use App\Models\DatabaseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiTilesController;
use App\Http\Controllers\ApiWordController;
use App\Http\Controllers\GoogleDriveController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('drive/dispatchimport', [GoogleDriveController::class, 'dispatchimport']);

Route::get('tiles/words/{languagePack}/{tileId}', [ApiTilesController::class, 'words']);
Route::get('words/tiles/{languagePack}/{wordId}', [ApiWordController::class, 'tiles']);

Route::get('/export-logs', function (Request $request) {
    $logData = DatabaseLog::where('languagepackid', $request->query('languagepackid'))
        ->where('type', 'export')
        ->first();
    
    return response()->json(['messages' => $logData->message, 'status' => $logData->status]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
