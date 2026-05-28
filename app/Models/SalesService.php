<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Traits\HasAuditLogs;

class SalesService extends Model
{
    use HasAuditLogs;
    const STATUS_MESA = 'table';
    const STATUS_REPROVADO = 'rejected';
    const STATUS_CANCELADO = 'cancelled';
    const STATUS_PENDENTE = 'pending';
    const STATUS_PROPOSTA = 'proposal';
    const STATUS_APROVADO = 'approved';
    const STATUS_FINALIZADO = 'completed';

    protected $casts = [
        'cortesia' => 'array',
    ];

    protected $fillable = [
        'client_id',
        'date',
        'time',
        'clients',
        'local',
        'opc_id',
        'liner_id',
        'closer_id',
        'mkt_id',
        'opc',
        'closer',
        'qualification',
        'status',
        'tem_conjuge',
        'nome_conjuge',
        'data_nascimento_conjuge',
        'idade_conjuge',
        'profissao_conjuge',
        'quantidade_filhos',
        'tempo_juntos',
        'renda_familiar',
        'cortesia',
        'observacoes',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal()
    {
        return $this->hasOne(Proposal::class, 'sales_service_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'sales_service_id');
    }

    public function opcUser()
    {
        return $this->belongsTo(User::class, 'opc_id');
    }

    public function linerUser()
    {
        return $this->belongsTo(User::class, 'liner_id');
    }

    public function closerUser()
    {
        return $this->belongsTo(User::class, 'closer_id');
    }

    public function mktUser()
    {
        return $this->belongsTo(User::class, 'mkt_id');
    }
}
