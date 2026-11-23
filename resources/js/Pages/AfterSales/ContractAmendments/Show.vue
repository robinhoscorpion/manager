<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    amendment: Object,
    history: Array,
});

const can = (permission) => {
    const auth = usePage().props.auth;
    return auth.permissions.includes(permission) || auth.roles.includes('admin');
};

const categoryLabels = {
    entrada: 'Entrada',
    saldo: 'Saldo',
    taxa_contrato: 'Taxa de Contrato',
    taxa_manutencao: 'Taxa de Manutenção',
    credito_contrato_anterior: 'Valor Pago no Contrato Anterior',
};
const statusLabels = { paid: 'Paga', pending: 'Pendente', overdue: 'Vencida', cancelled: 'Cancelada', partially_paid: 'Parcial' };
const responsibleLabels = { employee: 'Funcionário', client: 'Cliente', undefined: 'A definir' };
const reversalStatusLabels = { pending: 'Pendente', resolved: 'Resolvido', waived: 'Dispensado' };

const formatCurrency = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
const formatDate = (d) => d ? new Date(d.length === 10 ? d + 'T00:00:00' : d).toLocaleDateString('pt-BR') : '-';

const snap = computed(() => props.amendment.snapshot || {});
const replacedIds = computed(() => new Set((props.amendment.replaced_bills || []).map((b) => b.id)));

const updateReversal = (r, field, value) => {
    router.patch(route('after-sales.contract-amendments.reversals.update', r.id), {
        responsible: field === 'responsible' ? value : r.responsible,
        status: field === 'status' ? value : r.status,
        notes: r.notes,
    }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Aditivo v${amendment.to_version} · Contrato ${amendment.proposal?.contract_number}`" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 space-y-6">

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-sm px-6 py-5 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('after-sales.contract-amendments.index')" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        </Link>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white leading-none mb-1">
                                Contrato #{{ amendment.proposal?.contract_number }}
                                <span class="ml-2 px-2 py-1 rounded-md text-[11px] font-black uppercase align-middle"
                                    :class="amendment.type === 'upgrade' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ amendment.type_label }}</span>
                            </h2>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">{{ amendment.proposal?.client?.nome }} · versão {{ amendment.from_version }} → {{ amendment.to_version }} · {{ formatDate(amendment.created_at) }} por {{ amendment.user?.name || '-' }}</p>
                        </div>
                    </div>
                    <div class="text-xs text-slate-600 dark:text-slate-400 max-w-md">
                        <b>Motivo:</b> {{ amendment.reason }}<span v-if="amendment.details"> — {{ amendment.details }}</span>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div v-for="item in [
                        ['Valor anterior', amendment.old_total_value, ''],
                        ['Novo valor', amendment.new_total_value, 'text-indigo-600 dark:text-cyan-400'],
                        ['Diferença', amendment.difference_amount, Number(amendment.difference_amount) >= 0 ? 'text-emerald-600' : 'text-amber-600'],
                        ['Pago que entrou no novo', amendment.credit_applied, 'text-emerald-600'],
                        ['Saldo credor (sobra)', amendment.credit_balance, 'text-amber-600'],
                        ['Restante negociado', amendment.remaining_amount, ''],
                    ]" :key="item[0]" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3">
                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">{{ item[0] }}</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white" :class="item[2]">{{ formatCurrency(item[1]) }}</p>
                    </div>
                </div>

                <!-- Espelho: anterior x nova -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-5 space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Espelho · versão {{ amendment.from_version }} (anterior)</p>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ snap.product?.name || amendment.from_product?.name }}</h3>
                            <p class="text-xs text-slate-500">Valor {{ formatCurrency(snap.proposal?.total_value) }} · Taxas {{ formatCurrency(snap.proposal?.taxes) }} · Pontuação {{ snap.proposal?.quantity ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Negociação</p>
                            <ul class="text-xs text-slate-700 dark:text-slate-300 space-y-0.5">
                                <li v-for="p in snap.payments || []" :key="p.id">{{ categoryLabels[p.category] || p.category }}: {{ p.installments }}x {{ formatCurrency(p.installment_value) }} ({{ p.payment_method }}) = <b>{{ formatCurrency(p.total_value) }}</b></li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Parcelas no momento do aditivo</p>
                            <div class="max-h-80 overflow-y-auto">
                                <table class="w-full text-[11px]">
                                    <tbody>
                                        <tr v-for="b in snap.bills || []" :key="b.id" class="border-t border-slate-100 dark:border-white/5" :class="replacedIds.has(b.id) ? 'opacity-60 line-through' : ''">
                                            <td class="py-1 pr-2">{{ categoryLabels[b.category] || b.category }} {{ b.installment_number }}/{{ b.total_installments }}</td>
                                            <td class="py-1 pr-2">{{ formatDate(b.due_date) }}</td>
                                            <td class="py-1 pr-2 text-right">{{ formatCurrency(b.amount) }}</td>
                                            <td class="py-1 text-right">{{ statusLabels[b.status] || b.status }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Riscadas: parcelas em aberto substituídas por este aditivo.</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-indigo-200 dark:border-cyan-500/30 rounded-[20px] p-5 space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-indigo-500 dark:text-cyan-400 uppercase tracking-widest">Versão {{ amendment.to_version }} (gerada por este aditivo)</p>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ amendment.to_product?.name }}</h3>
                            <p class="text-xs text-slate-500">Valor {{ formatCurrency(amendment.new_total_value) }} · Pontuação {{ amendment.new_quantity ?? '-' }} · Nova taxa de contrato: {{ amendment.charge_contract_fee ? formatCurrency(amendment.contract_fee_amount) : 'não cobrada' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Negociação</p>
                            <ul class="text-xs text-slate-700 dark:text-slate-300 space-y-0.5">
                                <li v-for="p in amendment.payments" :key="p.id">{{ categoryLabels[p.category] || p.category }}: {{ p.installments }}x {{ formatCurrency(p.installment_value) }} ({{ p.payment_method }}) = <b>{{ formatCurrency(p.total_value) }}</b></li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Parcelas geradas</p>
                            <div class="max-h-80 overflow-y-auto">
                                <table class="w-full text-[11px]">
                                    <tbody>
                                        <tr v-for="b in amendment.bills" :key="b.id" class="border-t border-slate-100 dark:border-white/5">
                                            <td class="py-1 pr-2">{{ categoryLabels[b.category] || b.category }} {{ b.installment_number }}/{{ b.total_installments }}</td>
                                            <td class="py-1 pr-2">{{ formatDate(b.due_date) }}</td>
                                            <td class="py-1 pr-2 text-right">{{ formatCurrency(b.amount) }}</td>
                                            <td class="py-1 text-right">{{ statusLabels[b.status] || b.status }}</td>
                                        </tr>
                                        <tr v-if="!amendment.bills.length"><td class="py-2 text-slate-400">Nenhuma parcela nova (valor já coberto pelo que foi pago).</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comissões -->
                <div v-if="amendment.commissions.length || amendment.commission_reversals.length" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-5 space-y-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Comissões</h3>
                    <ul v-if="amendment.commissions.length" class="text-xs text-slate-700 dark:text-slate-300">
                        <li v-for="c in amendment.commissions" :key="c.id">Comissão sobre a diferença · {{ c.role }} · {{ c.user?.name }}: <b>{{ formatCurrency(c.total_amount) }}</b></li>
                    </ul>
                    <table v-if="amendment.commission_reversals.length" class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-[10px] uppercase text-slate-500">
                                <th class="py-1">Funcionário</th><th class="py-1 text-right">Estorno</th><th class="py-1 text-right">Abatido de pendentes</th><th class="py-1 text-right">A recuperar</th><th class="py-1">Responsável</th><th class="py-1">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in amendment.commission_reversals" :key="r.id" class="border-t border-slate-100 dark:border-white/5 text-slate-700 dark:text-slate-300">
                                <td class="py-1.5">{{ r.user?.name }}</td>
                                <td class="py-1.5 text-right">{{ formatCurrency(r.reversal_amount) }}</td>
                                <td class="py-1.5 text-right">{{ formatCurrency(r.pending_reduced_amount) }}</td>
                                <td class="py-1.5 text-right font-bold" :class="Number(r.to_recover_amount) > 0 ? 'text-red-500' : ''">{{ formatCurrency(r.to_recover_amount) }}</td>
                                <td class="py-1.5">
                                    <select v-if="can('pos_venda.alteracao_contrato.gerenciar')" :value="r.responsible" @change="updateReversal(r, 'responsible', $event.target.value)" class="text-xs rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 py-1">
                                        <option v-for="(label, key) in responsibleLabels" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <span v-else>{{ responsibleLabels[r.responsible] }}</span>
                                </td>
                                <td class="py-1.5">
                                    <select v-if="can('pos_venda.alteracao_contrato.gerenciar')" :value="r.status" @change="updateReversal(r, 'status', $event.target.value)" class="text-xs rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 py-1">
                                        <option v-for="(label, key) in reversalStatusLabels" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <span v-else>{{ reversalStatusLabels[r.status] }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Histórico -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">Histórico de versões do contrato</h3>
                    <ol class="space-y-2 text-xs">
                        <li v-for="h in history" :key="h.id" class="flex flex-wrap items-center gap-2" :class="h.id === amendment.id ? 'font-bold text-indigo-600 dark:text-cyan-400' : 'text-slate-600 dark:text-slate-400'">
                            <span>v{{ h.from_version }} → v{{ h.to_version }}</span>
                            <span>{{ h.type_label }}</span>
                            <span>{{ h.from_product?.name }} → {{ h.to_product?.name }}</span>
                            <span>{{ formatCurrency(h.old_total_value) }} → {{ formatCurrency(h.new_total_value) }}</span>
                            <span>{{ formatDate(h.created_at) }}</span>
                            <Link v-if="h.id !== amendment.id" :href="route('after-sales.contract-amendments.show', h.id)" class="underline">abrir</Link>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
