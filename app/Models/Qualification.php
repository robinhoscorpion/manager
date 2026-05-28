<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasFactory, HasAuditLogs;

    protected $fillable = [
        'name',
        'code',
        'color',
        'description',
        'is_active'
    ];
}
