<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'date',
        'time',
        'status',
        'has_spouse',
        'spouse_name',
        'spouse_phone',
        'spouse_email',
        'observations',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
