<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtocolSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
    ];
}
