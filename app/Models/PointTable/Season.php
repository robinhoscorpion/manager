<?php

namespace App\Models\PointTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $table = 'point_seasons';

    protected $fillable = [
        'name',
        'advance_days',
        'period_description',
        'months_active',
        'special_dates',
    ];

    protected $casts = [
        'months_active' => 'array',
        'special_dates' => 'array',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
