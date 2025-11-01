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
use App\Http\Controllers\ResourcesController;
use App\Http\Controllers\SyllablesController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\GameSettingsController;
use App\Http\Controllers\LanguageInfoController;
use App\Http\Controllers\LanguagePackController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');    
    
    Route::get('/languagepack/users/{languagepack}', [LanguagePackController::class, 'users'])
        ->name('languagepack.users');
        Route::post('/languagepack/{languagepack}/users', [LanguagePackController::class, 'addUser'])
            ->name('languagepack.addUser');

    Route::delete('/languagepack/{languagepack}/users/{user}', [LanguagePackController::class, 'removeUser'])
        ->name('languagepack.removeUser');


    Route::get('languagepack/create', [LanguageInfoController::class, 'create']);
    Route::post('languagepack/edit', [LanguageInfoController::class, 'store']);
    Route::post('languagepack/edit/{id}', [LanguageInfoController::class, 'store']);       
    Route::delete('languagepack/delete/{languagePack}', [LanguageInfoController::class, 'destroy']);
    Route::get('languagepack/edit/{languagePack}', [LanguageInfoController::class, 'edit']);
    Route::get('languagepack/remove/{languagePack}/{user}', [LanguageInfoController::class, 'removeCollaborator']);    

    Route::get('languagepack/tiles/{languagePack}', [TilesController::class, 'edit']);
    Route::get('languagepack/tiles/{languagePack}/{tile}', [TilesController::class, 'edit']);
    Route::post('languagepack/tiles/{languagePack}', [TilesController::class, 'store']);
    Route::patch('languagepack/tiles/{languagePack}', [TilesController::class, 'update'])->name('update-tiles');
    Route::delete('languagepack/tiles/{languagePack}', [TilesController::class, 'delete'])->name('delete-tiles');
    
    Route::get('languagepack/items/{languagePack}/download/{filename}', [ItemsController::class, 'downloadFile']);
    
    Route::post('languagepack/wordlist/{languagePack}', [WordlistController::class, 'store']);
    Route::get('languagepack/wordlist/{languagePack}', [WordlistController::class, 'edit']);
    Route::get('languagepack/wordlist/{languagePack}/{word}', [WordlistController::class, 'edit']);
    Route::patch('languagepack/wordlist/{languagePack}', [WordlistController::class, 'update']);
    Route::delete('languagepack/wordlist/{languagePack}', [WordlistController::class, 'delete']);

    Route::post('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'store']);
    Route::get('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'edit']);
    Route::get('languagepack/keyboard/{languagePack}/{key}', [KeyboardController::class, 'edit']);
    Route::patch('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'update']);
    Route::delete('languagepack/keyboard/{languagePack}', [KeyboardController::class, 'delete']);

    Route::get('languagepack/resources/{languagePack}', [ResourcesController::class, 'edit']);
    Route::post('languagepack/resources/{languagePack}', [ResourcesController::class, 'store']);
    Route::patch('languagepack/resources/{languagePack}', [ResourcesController::class, 'update']);
    Route::delete('languagepack/resources/{languagePack}', [ResourcesController::class, 'delete']);    

    Route::get('languagepack/syllables/{languagePack}', [SyllablesController::class, 'edit']);
    Route::get('languagepack/syllables/{languagePack}/{syllable}', [SyllablesController::class, 'edit']);
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