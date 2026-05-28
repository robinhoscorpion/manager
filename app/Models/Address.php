<?php

namespace App\Models;

use App\Traits\HasAuditLogs;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasAuditLogs;

    protected $fillable = [
        'client_id', 'cep', 'rua', 'bairro', 'numero', 
        'cidade', 'estado', 'pais', 'complemento', 'ponto_referencia'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
