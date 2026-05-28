<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalPayment extends Model
{
    use HasFactory, HasAuditLogs;

    protected $fillable = [
        'proposal_id',
        'category',
        'payment_method',
        'installments',
        'installment_value',
        'total_value',
        'start_date'
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
