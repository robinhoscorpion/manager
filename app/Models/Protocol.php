<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    protected $fillable = [
        'sales_service_id',
        'user_id',
        'subject',
        'priority',
        'status',
        'message',
        'attachments',
        'protocol_number',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($protocol) {
            $date = date('Ymd');
            $lastProtocol = static::where('protocol_number', 'like', $date . '%')
                                  ->orderBy('id', 'desc')
                                  ->first();

            if ($lastProtocol) {
                // Extracts the last 4 digits and increments
                $lastSequence = (int) substr($lastProtocol->protocol_number, 8);
                $nextSequence = $lastSequence + 1;
            } else {
                // Starts at 1001 for a better sequence
                $nextSequence = 1001;
            }

            $protocol->protocol_number = $date . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }

    public function service()
    {
        return $this->belongsTo(SalesService::class, 'sales_service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies()
    {
        return $this->hasMany(ProtocolReply::class);
    }
}
