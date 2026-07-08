<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProtocolReply extends Model
{
    protected $fillable = [
        'protocol_id',
        'user_id',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function protocol()
    {
        return $this->belongsTo(Protocol::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
