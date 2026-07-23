<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RciTemplate extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'is_default',
        'mapping_config',
        'preview_image_path',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'mapping_config' => 'array',
    ];
}
