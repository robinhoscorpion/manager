<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'date',
        'time',
        'status',
        'observations',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
