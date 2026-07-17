<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class ProposalTemplate extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'name',
        'file_path',
        'original_filename',
        'is_active'
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
