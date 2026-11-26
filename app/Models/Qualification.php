<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasFactory, HasAuditLogs;

    protected $fillable = [
        'name',
        'code',
        'color',
        'description',
        'is_active',
        'counts_in_metrics',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'counts_in_metrics' => 'boolean',
    ];

    /**
     * Códigos (maiúsculos) das qualificações que NÃO entram no cálculo
     * de aproveitamento/conversão (ex.: NT - No Tour).
     *
     * @return string[]
     */
    public static function excludedFromMetricsCodes(): array
    {
        return static::where('counts_in_metrics', false)
            ->pluck('code')
            ->map(fn ($code) => strtoupper(trim($code)))
            ->unique()
            ->values()
            ->all();
    }

    /** Informa se a qualificação de um atendimento entra no cálculo. */
    public static function countsInMetrics(?string $code, array $excludedCodes): bool
    {
        return !in_array(strtoupper(trim((string) $code)), $excludedCodes, true);
    }
}
