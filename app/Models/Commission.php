<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'role',
        'total_amount',
        'base_sale_value',
        'base_commission_percentage'
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installments()
    {
        return $this->hasMany(CommissionInstallment::class);
    }
}
