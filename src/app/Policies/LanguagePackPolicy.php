<?php

namespace App\Policies;

use App\Models\LanguagePack;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LanguagePackPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, LanguagePack $languagePack)
    {
        return $user->id === $languagePack->userid;
    }    

    public function update(User $user, LanguagePack $languagePack)
    {
        return $user->id === $languagePack->userid;
    }    

    public function delete(User $user, LanguagePack $languagePack)
    {
        return $user->id === $languagePack->user_id;
    }    
}
