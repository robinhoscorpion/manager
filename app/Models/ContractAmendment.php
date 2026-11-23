<?php

namespace App\Models;

use App\Traits\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;

/**
 * Aditivo de contrato (Upgrade / Downgrade).
 *
 * Mantém o mesmo número de contrato e guarda em `snapshot` o espelho
 * completo da versão anterior (produto, valores, pagamentos e parcelas).
 */
class ContractAmendment extends Model
{
    use HasAuditLogs;

    public const TYPE_UPGRADE = 'upgrade';
    public const TYPE_DOWNGRADE = 'downgrade';

    public const RESPONSIBLE_EMPLOYEE = 'employee';
    public const RESPONSIBLE_CLIENT = 'client';
    public const RESPONSIBLE_UNDEFINED = 'undefined';

    public const TYPE_LABELS = [
        self::TYPE_UPGRADE => 'Upgrade',
        self::TYPE_DOWNGRADE => 'Downgrade',
    ];

    protected $fillable = [
        'proposal_id',
        'user_id',
        'type',
        'from_version',
        'to_version',
        'from_product_id',
        'to_product_id',
        'old_total_value',
        'new_total_value',
        'old_quantity',
        'new_quantity',
        'paid_amount',
        'credit_applied',
        'credit_balance',
        'difference_amount',
        'remaining_amount',
        'charge_contract_fee',
        'contract_fee_amount',
        'commission_reversal_responsible',
        'snapshot',
        'reason',
        'details',
        'status',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'charge_contract_fee' => 'boolean',
        'old_total_value' => 'decimal:2',
        'new_total_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credit_applied' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'contract_fee_amount' => 'decimal:2',
    ];

    protected $appends = ['type_label'];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? (string) $this->type;
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromProduct()
    {
        return $this->belongsTo(Product::class, 'from_product_id');
    }

    public function toProduct()
    {
        return $this->belongsTo(Product::class, 'to_product_id');
    }

    /** Pagamentos negociados nesta nova versão. */
    public function payments()
    {
        return $this->hasMany(ProposalPayment::class);
    }

    /** Parcelas geradas por este aditivo. */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /** Parcelas da versão anterior substituídas por este aditivo. */
    public function replacedBills()
    {
        return $this->hasMany(Bill::class, 'replaced_by_amendment_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function commissionReversals()
    {
        return $this->hasMany(CommissionReversal::class);
    }
}
