<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLinePosition extends Model
{
    protected $fillable = ['user_id', 'group', 'position_order', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
