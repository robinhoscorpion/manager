<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasAuditLogs;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Opções da Ficha de Atendimento (Configurações → Ficha de Atendimento).
     * relationship_field: exibe "Relação com o 1º Titular". Desligado, o 2º titular é tratado como casal
     * e o estado civil do 2º titular acompanha o do 1º.
     */
    public static function serviceForm(): array
    {
        $defaults = ['relationship_field' => true];
        $value = static::where('key', 'service_form')->value('value');

        return array_merge($defaults, is_array($value) ? $value : []);
    }
}
