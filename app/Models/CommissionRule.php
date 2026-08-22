<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    protected $fillable = [
        'name',
        'role_column',
        'percentage',
        'distribution_base_percentage',
        'cash_installments',
        'credit_installments',
        'boleto_installments_type',
        'boleto_fixed_installments',
        'is_active'
    ];
}
