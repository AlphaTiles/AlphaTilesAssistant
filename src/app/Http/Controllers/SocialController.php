<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([Drive::DRIVE, Drive::DRIVE_FILE])
            ->redirect();
    }

    public function callback()
    {
        try {
                $googleUser = Socialite::driver('google')->user();
                $user = User::where('email', $googleUser->email)->first();
                Session::put('socialite_token', $googleUser->token);                
                
                if($user){
                    Auth::login($user);
                    //return redirect()->to('/dashboard');
                    return redirect()->intended();
                }else{
                    echo "No account exists for the email address {$googleUser->email}. " .
                    "<a href=\"https://alphatilesapps.org/contact.html\">Contact us</a> to get started " .
                    "or <a href=\"https://accounts.google.com/AccountChooser\">login with another account</a>.";
                }
            } catch (Exception $e) {
                dd($e);
            }
    }
}