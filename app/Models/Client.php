<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasAuditLogs;

class Client extends Authenticatable
{
    use HasApiTokens, Notifiable, HasAuditLogs;

    protected $table = 'clients';

    protected $fillable = [
        'nome', 
        'cpf', 
        'rg', 
        'nacionalidade', 
        'data_nascimento', 
        'idade', 
        'profissao', 
        'estado_civil', 
        'celular1', 
        'celular2', 
        'email', 
        'password', 
        'password_set_at'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'password_set_at' => 'datetime',
        'data_nascimento' => 'date',
    ];

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function services()
    {
        return $this->hasMany(SalesService::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
}
