<?php

namespace App\Models\PointTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accommodation extends Model
{
    protected $table = 'point_accommodations';

    protected $fillable = [
        'resort_id',
        'name',
        'group_name',
        'max_pax',
    ];

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
