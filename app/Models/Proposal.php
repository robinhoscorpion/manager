<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'contract_number',
        'client_id',
        'sales_service_id',
        'product_id',
        'total_value',
        'quantity',
        'payment_method',
        'status',
        'observations',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function salesService()
    {
        return $this->belongsTo(SalesService::class, 'sales_service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function payments()
    {
        return $this->hasMany(ProposalPayment::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
