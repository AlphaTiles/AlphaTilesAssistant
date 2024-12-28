<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\TilesController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\KeyboardController;
use App\Http\Controllers\WordlistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SyllablesController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\GameSettingsController;
use App\Http\Controllers\LanguageInfoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->middleware('guest');

Route::middleware(['auth', 'authorize.languagepack'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('home');    
    
    Route::get('languagepack/create', [LanguageInfoController::class, 'create']);
    Route::post('languagepack/edit', [LanguageInfoController::class, 'store']);
    Route::post('languagepack/edit/{id}', [LanguageInfoController::class, 'store']);       
    Route::delete('languagepack/delete/{languagePack}', [LanguageInfoController::class, 'destroy']);
    Route::get('languagepack/edit/{languagePack}', [LanguageInfoController::class, 'edit']);

    Route::get('languagepack/tiles/{languagePack}', [TilesController::class, 'edit']);
    Route::post('languagepack/tiles/{languagePack}', [TilesController::class, 'store']);
    Route::patch('languagepack/tiles/{languagePack}', [TilesController::class, 'update']);
    Route::delete('languagepack/tiles/{languagePack}', [TilesController::class, 'delete']);
    
    Route::get('languagepack/items/{languagePack}/download/{filename}', [ItemsController::class, 'downloadFile']);
    
    Route::post('languagepack/wordlist/{languagePack}', [WordlistController::class, 'store']);
    Route::get('languagepack/wordlist/{languagePack}', [WordlistController::class, 'edit']);
    Route::patch('languagepack/wordlist/{languagePack}', [WordlistController::class, 'update']);
    Route::delete('languagepack/wordlist/{languagePack}', [WordlistController::class, 'delete']);
    Route::get('languagepack/wordlist/{languagePack}/download/{filename}', [WordlistController::class, 'downloadFile']);

    Route::post('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'store']);
    Route::get('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'edit']);
    Route::patch('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'update']);
    Route::delete('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'delete']);

    Route::get('languagepack/syllables/{languagePack}', [SyllablesController::class, 'edit']);
    Route::post('languagepack/syllables/{languagePack}', [SyllablesController::class, 'store']);
    Route::patch('languagepack/syllables/{languagePack}', [SyllablesController::class, 'update']);
    Route::delete('languagepack/syllables/{languagePack}', [SyllablesController::class, 'delete']);

    Route::post('languagepack/game_settings/{languagePack}', [GameSettingsController::class, 'update']);
    Route::get('languagepack/game_settings/{languagePack}', [GameSettingsController::class, 'edit']);

    Route::get('languagepack/export/{languagePack}', [ExportController::class, 'show']);    
    Route::post('languagepack/export/{languagePack}', [ExportController::class, 'store']);    

    Route::get('drive/import', [GoogleDriveController::class, 'import'])->name('drive.import');    
    Route::get('drive/export/{languagePack}', [GoogleDriveController::class, 'export'])->name('drive.export');    
});

Auth::routes(['register' => false]);
Route::get('/login/google', [SocialController::class, 'redirect'])->name('redirect');
Route::get('/login/google/callback', [SocialController::class, 'callback'])->name('callback');