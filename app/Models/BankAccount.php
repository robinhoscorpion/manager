<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'owner_type',
        'gateway',
        'api_token',
        'webhook_secret',
        'bank_name',
        'agency',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
