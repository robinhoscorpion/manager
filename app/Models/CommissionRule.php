<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    protected $fillable = [
        'name',
        'role_column',
        'rule_type',
        'percentage',
        'distribution_base_percentage',
        'cash_installments',
        'credit_installments',
        'boleto_installments_type',
        'boleto_fixed_installments',
        'score_rules',
        'qualification_rules',
        'is_active',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'score_rules'         => 'array',
        'qualification_rules' => 'array',
    ];
}
