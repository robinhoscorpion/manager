<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'type',
        'recipient',
        'description',
        'is_active',
        'auto_baixa',
        'bank_account_id',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    protected $casts = [
        'is_active' => 'boolean',
        'auto_baixa' => 'boolean',
    ];
}
