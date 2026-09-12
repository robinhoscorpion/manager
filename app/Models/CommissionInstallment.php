<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionInstallment extends Model
{
    protected $fillable = [
        'commission_id',
        'month_offset',
        'due_date',
        'reference_month',
        'amount',
        'status',
        'origin_type'
    ];

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }
}
