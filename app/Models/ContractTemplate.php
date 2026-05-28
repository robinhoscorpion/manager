<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
