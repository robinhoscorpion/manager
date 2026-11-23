<?php

namespace App\Models;

use App\Traits\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;

/**
 * Estorno de comissão gerado por downgrade de contrato.
 *
 * - pending_reduced_amount: abatido das parcelas de comissão ainda não pagas.
 * - to_recover_amount: parte já paga ao funcionário que precisa ser recuperada
 *   (do funcionário ou do cliente, conforme `responsible`).
 */
class CommissionReversal extends Model
{
    use HasAuditLogs;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'commission_id',
        'contract_amendment_id',
        'user_id',
        'reversal_amount',
        'pending_reduced_amount',
        'to_recover_amount',
        'responsible',
        'status',
        'notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'reversal_amount' => 'decimal:2',
        'pending_reduced_amount' => 'decimal:2',
        'to_recover_amount' => 'decimal:2',
    ];

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function amendment()
    {
        return $this->belongsTo(ContractAmendment::class, 'contract_amendment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
