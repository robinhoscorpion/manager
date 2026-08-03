<?php

namespace App\Models\PointTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $table = 'point_scores';

    protected $fillable = [
        'accommodation_id',
        'season_id',
        'pax',
        'points',
    ];

    protected $casts = [
        'points' => 'decimal:2',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
