<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class User extends Authenticatable
{
    use HasRoleAndPermission;
    use HasFactory;
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function isAdmin(): bool
    {
        return DB::table(config('roles.roleUserTable', 'role_user') . ' as ru')
            ->join(config('roles.rolesTable', 'roles') . ' as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $this->id)
            ->where('r.slug', 'admin')
            ->exists();
    }
}
