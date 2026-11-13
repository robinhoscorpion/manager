<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractSignatureSigner extends Model
{
    public const ROLE_HOLDER = 'titular';
    public const ROLE_SECOND_HOLDER = 'segundo_titular';
    public const ROLE_COMPANY = 'empresa';
    public const ROLE_WITNESS = 'testemunha';

    public const ROLE_LABELS = [
        self::ROLE_HOLDER => '1º Titular',
        self::ROLE_SECOND_HOLDER => '2º Titular',
        self::ROLE_COMPANY => 'Empresa',
        self::ROLE_WITNESS => 'Testemunha',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REFUSED = 'refused';

    protected $fillable = [
        'contract_signature_id',
        'role',
        'name',
        'email',
        'phone',
        'cpf',
        'external_id',
        'status',
        'sign_url',
        'viewed_at',
        'signed_at',
        'refused_at',
        'order',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'refused_at' => 'datetime',
    ];

    protected $appends = ['role_label'];

    public function contractSignature()
    {
        return $this->belongsTo(ContractSignature::class);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }
}
