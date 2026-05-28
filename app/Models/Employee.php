<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, HasAuditLogs;

    protected $fillable = [
        'user_id',
        'name',
        'role',
        'email',
        'position',
        'salary',
        'hired_at',
        'type',
        'workload',
        'status',
        'is_active',
    ];

    protected $appends = ['profile_photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->user?->profile_photo_url ?? $this->defaultProfilePhotoUrl();
    }

    protected function defaultProfilePhotoUrl()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }
}
