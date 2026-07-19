<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'revenue_target',
        'contracts_target',
    ];
}
