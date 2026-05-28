<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];
}
