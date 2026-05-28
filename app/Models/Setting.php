<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasAuditLogs;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];
}
