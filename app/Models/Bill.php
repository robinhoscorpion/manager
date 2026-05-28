<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLogs;

class Bill extends Model
{
    use HasAuditLogs;
    protected $fillable = [
        'client_id',
        'proposal_id',
        'sales_service_id',
        'category',
        'description',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'installment_number',
        'total_installments',
        'interest_amount',
        'paid_amount',
        'observations',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function salesService()
    {
        return $this->belongsTo(SalesService::class);
    }
}
