<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAdjustment extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'target_month',
        'target_year',
        'igpm_rate',
        'base_type',
        'exempt_proposal_ids',
        'applied_at',
    ];

    protected $casts = [
        'exempt_proposal_ids' => 'array',
        'applied_at' => 'datetime',
        'igpm_rate' => 'decimal:4',
    ];
}
