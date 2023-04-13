<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PlanController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\EditTestController;
use App\Http\Controllers\DisplayTestController;
use App\Http\Controllers\GenerateTestController;

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
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');    
});

Auth::routes(['register' => false]);
Route::get('/login/google', [SocialController::class, 'redirect'])->name('redirect');
Route::get('/login/google/callback', [SocialController::class, 'callback'])->name('callback');