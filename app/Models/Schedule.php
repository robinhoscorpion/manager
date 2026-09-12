<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'nacionalidade',
        'data_nascimento',
        'profissao',
        'date',
        'time',
        'status',
        'has_spouse',
        'spouse_name',
        'spouse_phone',
        'spouse_email',
        'spouse_nacionalidade',
        'spouse_data_nascimento',
        'spouse_profissao',
        'observations',
        'renda_familiar',
        'cortesia',
        'user_id'
    ];

    protected $casts = [
        'cortesia' => 'array',
        'has_spouse' => 'boolean',
        'date' => 'date',
        'data_nascimento' => 'date',
        'spouse_data_nascimento' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
