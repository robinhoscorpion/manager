<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'reason',
        'details',
        'total_paid',
        'fine_amount',
        'refund_amount',
        'status',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
