<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasAuditLogs;

    protected $fillable = ['name', 'slug', 'group'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
