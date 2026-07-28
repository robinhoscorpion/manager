<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationRequest extends Model
{
    protected $fillable = [
        'sales_service_id',
        'user_id',
        'destination',
        'check_in',
        'check_out',
        'adults',
        'children',
        'guests_list',
        'status',
        'observations',
    ];

    protected $casts = [
        'guests_list' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(SalesService::class, 'sales_service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
