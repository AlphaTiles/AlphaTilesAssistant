<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Collaborator;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LanguagePackController extends Controller
{
    public function users(LanguagePack $languagepack)
    {
        $this->authorize('update', $languagepack);

        $collaborators = Collaborator::where('languagepack_id', $languagepack->id)
            ->with('user')
            ->get();

        return view('languagepack.users', [
            'languagepack' => $languagepack,
            'collaborators' => $collaborators
        ]);
    }

    public function addUser(Request $request, LanguagePack $languagepack)
    {
        $this->authorize('update', $languagepack);

        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();

        // Check if user is already a collaborator
        $exists = Collaborator::where('languagepack_id', $languagepack->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'User is already a collaborator');
        }

        Collaborator::create([
            'languagepack_id' => $languagepack->id,
            'user_id' => $user->id
        ]);

        return back()->with('success', 'Collaborator added successfully');
    }

    public function removeUser(LanguagePack $languagepack, User $user)
    {
        $this->authorize('update', $languagepack);

        Collaborator::where('languagepack_id', $languagepack->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Collaborator removed successfully');
    }
}