<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapturePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
