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
        'version',
        'contract_amendment_id',
        'replaced_by_amendment_id',
        'sales_service_id',
        'category',
        'description',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'recipient',
        'bank_account_id',
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

    public function amendment()
    {
        return $this->belongsTo(ContractAmendment::class, 'contract_amendment_id');
    }

    public function replacedByAmendment()
    {
        return $this->belongsTo(ContractAmendment::class, 'replaced_by_amendment_id');
    }

    public function salesService()
    {
        return $this->belongsTo(SalesService::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
