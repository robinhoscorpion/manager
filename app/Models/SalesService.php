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
        'tipo_relacionamento',
        'nome_conjuge',
        'cpf_conjuge',
        'rg_conjuge',
        'nacionalidade_conjuge',
        'estado_civil_conjuge',
        'data_nascimento_conjuge',
        'idade_conjuge',
        'profissao_conjuge',
        'quantidade_filhos',
        'tempo_juntos',
        'renda_familiar',
        'cortesia',
        'observacoes',
        'welcome_status',
        'welcome_sent_at',
        'welcome_sent_by',
        'contract_delivery_status',
        'contract_delivery_method',
        'contract_delivered_at',
        'contract_delivered_by',
        'contract_signature_status',
        'contract_signed_at',
        'contract_file_path',
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

    public function protocols()
    {
        return $this->hasMany(Protocol::class, 'sales_service_id');
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
