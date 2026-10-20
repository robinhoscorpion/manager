<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Profession extends Model
{
    protected $fillable = [
        'name',
        'active',
        'reviewed',
    ];

    protected $casts = [
        'active' => 'boolean',
        'reviewed' => 'boolean',
    ];

    /**
     * Colunas onde a profissão é gravada como texto.
     */
    public const USAGES = [
        ['clients', 'profissao'],
        ['sales_services', 'profissao_conjuge'],
        ['schedules', 'profissao'],
        ['schedules', 'spouse_profissao'],
    ];

    /**
     * Padroniza o texto: sem espaços sobrando e com a primeira letra maiúscula.
     */
    public static function normalize(?string $name): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
        if ($name === '') {
            return null;
        }

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /**
     * Garante que a profissão exista no cadastro e devolve o nome oficial.
     * Se já existir (ignorando maiúsculas/acentos), usa o nome cadastrado;
     * se não, cria como "a revisar" para o administrador conferir em Configurações.
     */
    public static function register(?string $name): ?string
    {
        $name = static::normalize($name);
        if (!$name) {
            return null;
        }

        $existing = static::where('name', $name)->first();
        if ($existing) {
            return $existing->name;
        }

        return static::create(['name' => $name, 'active' => true, 'reviewed' => false])->name;
    }

    /**
     * Nomes das profissões ativas para os formulários.
     */
    public static function activeNames(): array
    {
        return static::where('active', true)->orderBy('name')->pluck('name')->all();
    }

    /**
     * Quantidade de registros (clientes, cônjuges, agendamentos) que usam cada nome.
     */
    public static function usageCounts(): array
    {
        $counts = [];
        foreach (static::USAGES as [$table, $column]) {
            DB::table($table)->whereNotNull($column)
                ->select($column . ' as name', DB::raw('count(*) as total'))
                ->groupBy($column)
                ->get()
                ->each(function ($row) use (&$counts) {
                    $key = mb_strtolower($row->name);
                    $counts[$key] = ($counts[$key] ?? 0) + $row->total;
                });
        }

        return $counts;
    }

    /**
     * Troca o nome em todos os registros que usam a profissão (renomear ou unificar).
     */
    public static function replaceEverywhere(string $from, string $to): void
    {
        foreach (static::USAGES as [$table, $column]) {
            DB::table($table)->where($column, $from)->update([$column => $to]);
        }
    }
}
