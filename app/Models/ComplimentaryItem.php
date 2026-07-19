<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class ComplimentaryItem extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'template_type',
        'file_path',
        'content',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
