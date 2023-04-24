<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class User extends Authenticatable
{
    use HasRoleAndPermission;
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
