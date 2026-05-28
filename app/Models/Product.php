<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLogs;

class Product extends Model
{
    use HasAuditLogs;
    protected $fillable = [
        'product_type_id',
        'name',
        'price',
        'min_price',
        'duration',
        'quantity',
        'is_active',
        'min_down_payment_percentage',
        'contract_fee',
        'maintenance_fee_value',
        'maintenance_fee_installments',
        'maintenance_fee_frequency',
        'maintenance_fee_start_rule',
        'maintenance_fee_day',
        'maintenance_fee_delay_years',
        'is_maintenance_exempt',
        'description',
        'contract_prefix',
        'contract_format',
        'current_sequence',
        'proposal_template_id',
        'contract_template_id'
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function proposalTemplate()
    {
        return $this->belongsTo(ProposalTemplate::class);
    }

    public function contractTemplate()
    {
        return $this->belongsTo(ContractTemplate::class);
    }
}
