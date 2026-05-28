<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLogs;

class Client extends Model
{
    use HasAuditLogs;
    protected $fillable = [
        'nome', 'cpf', 'rg', 'nacionalidade', 'data_nascimento', 'idade', 'profissao', 
        'estado_civil', 'celular1', 'celular2', 'email'
    ];

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function services()
    {
        return $this->hasMany(SalesService::class);
    }
}
