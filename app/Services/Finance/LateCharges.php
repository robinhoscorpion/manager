<?php

namespace App\Services\Finance;

use App\Models\Bill;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Encargos por atraso (multa + juros de mora) e status "vencido" das parcelas.
 * Configuração em Financeiro → Inadimplência (chave "receivables_charges").
 */
class LateCharges
{
    public const SETTING_KEY = 'receivables_charges';

    public static function defaults(): array
    {
        return [
            'auto_apply' => true,          // calcula encargos automaticamente na baixa
            'fine_percent' => 2.0,         // multa única sobre o valor da parcela (CDC: máx. 2%)
            'interest_monthly_percent' => 1.0, // juros de mora ao mês, cobrados por dia (pro rata)
            'grace_days' => 0,             // dias após o vencimento sem encargos
        ];
    }

    public static function settings(): array
    {
        $value = Setting::where('key', self::SETTING_KEY)->value('value');

        return array_merge(self::defaults(), is_array($value) ? $value : []);
    }

    public static function saveSettings(array $data): void
    {
        Setting::updateOrCreate(['key' => self::SETTING_KEY], ['value' => array_merge(self::settings(), $data)]);
    }

    /**
     * Encargos de uma parcela numa data (padrão: hoje).
     *
     * @return array{days_late: int, fine: float, interest: float, total: float, updated_amount: float}
     */
    public static function for(Bill $bill, Carbon|string|null $date = null, ?array $settings = null): array
    {
        $settings ??= self::settings();
        $amount = (float) $bill->amount;
        $empty = ['days_late' => 0, 'fine' => 0.0, 'interest' => 0.0, 'total' => 0.0, 'updated_amount' => round($amount, 2)];

        if (!$bill->due_date || in_array($bill->status, ['paid', 'cancelled'], true)) {
            return $empty;
        }

        $due = Carbon::parse($bill->due_date)->startOfDay();
        $reference = ($date ? Carbon::parse($date) : now())->startOfDay();
        $daysLate = (int) $due->diffInDays($reference, false);

        if ($daysLate <= 0) {
            return $empty;
        }

        // Dentro da carência: está atrasada, mas sem encargos
        if ($daysLate <= (int) $settings['grace_days']) {
            return array_merge($empty, ['days_late' => $daysLate]);
        }

        $fine = round($amount * ((float) $settings['fine_percent'] / 100), 2);
        $interest = round($amount * ((float) $settings['interest_monthly_percent'] / 100 / 30) * $daysLate, 2);
        $total = round($fine + $interest, 2);

        return [
            'days_late' => $daysLate,
            'fine' => $fine,
            'interest' => $interest,
            'total' => $total,
            'updated_amount' => round($amount + $total, 2),
        ];
    }

    /**
     * Pendentes com vencimento passado viram "vencido"; vencidos com vencimento futuro
     * (ex.: renegociados/prorrogados) voltam para "pendente". Seguro para rodar a qualquer momento.
     *
     * @return array{overdue: int, back_to_pending: int}
     */
    public static function refreshStatuses(): array
    {
        $today = now()->toDateString();

        return DB::transaction(fn () => [
            'overdue' => Bill::where('status', 'pending')->whereDate('due_date', '<', $today)->update(['status' => 'overdue', 'updated_at' => now()]),
            'back_to_pending' => Bill::where('status', 'overdue')->whereDate('due_date', '>=', $today)->update(['status' => 'pending', 'updated_at' => now()]),
        ]);
    }

    /**
     * Faixa de atraso (aging) para relatórios.
     */
    public static function agingBucket(int $daysLate): string
    {
        return match (true) {
            $daysLate <= 30 => '0-30',
            $daysLate <= 60 => '31-60',
            $daysLate <= 90 => '61-90',
            default => '90+',
        };
    }
}
