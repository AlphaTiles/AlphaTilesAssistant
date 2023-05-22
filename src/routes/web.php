<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageInfoController;
use App\Http\Controllers\TilesController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('home');    
    Route::get('languagepack/create', [LanguageInfoController::class, 'create']);
    Route::get('languagepack/edit/{languagePack}', [LanguageInfoController::class, 'edit']);
    Route::get('languagepack/tiles/{languagePack}', [TilesController::class, 'edit']);
    Route::post('languagepack/edit', [LanguageInfoController::class, 'store']);
    Route::post('languagepack/edit/{id}', [LanguageInfoController::class, 'store']);      
});

Auth::routes(['register' => false]);
Route::get('/login/google', [SocialController::class, 'redirect'])->name('redirect');
Route::get('/login/google/callback', [SocialController::class, 'callback'])->name('callback');