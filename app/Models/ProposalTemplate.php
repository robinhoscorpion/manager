<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class ProposalTemplate extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'content',
        'is_active'
    ];
}
