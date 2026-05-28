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
        'description',
        'content',
        'type',
        'is_active',
    ];
}
