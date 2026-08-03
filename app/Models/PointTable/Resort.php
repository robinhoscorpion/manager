<?php

namespace App\Models\PointTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resort extends Model
{
    protected $table = 'point_resorts';

    protected $fillable = [
        'name',
        'icon',
        'color_theme',
    ];

    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }
}
