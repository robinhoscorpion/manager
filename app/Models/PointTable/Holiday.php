<?php

namespace App\Models\PointTable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'point_holidays';

    protected $fillable = [
        'name',
        'holiday_date',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
