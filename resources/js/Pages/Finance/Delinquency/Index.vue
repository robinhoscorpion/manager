<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    summary: Object,
    buckets: Array,
    clients: Array,
    settings: Object,
    filters: Object,
});

const can = (permission) => usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
const canManage = computed(() => can('recebiveis.gerenciar'));

const formatCurrency = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
const formatDate = (d) => {
    if (!d) return '-';
    const [y, m, day] = String(d).split(' ')[0].split('-');
    return `${day}/${m}/${y}`;
};
const formatPercent = (v) => new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(v || 0) + '%';

// Busca (servidor)
const search = ref(props.filters?.search || '');
let searchTimer = null;
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => router.get(route('finance.delinquency.index'), { search: value || undefined }, { preserveState: true, replace: true }), 400);
});

// Filtro por faixa de atraso (tela)
const bucketFilter = ref(null);
const filteredClients = computed(() => bucketFilter.value ? props.clients.filter(c => c.bucket === bucketFilter.value) : props.clients);

const expanded = ref(new Set());
const toggle = (id) => {
    const next = new Set(expanded.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expanded.value = next;
};

const bucketLabels = { '0-30': 'Até 30 dias', '31-60': '31 a 60 dias', '61-90': '61 a 90 dias', '90+': 'Mais de 90 dias' };
const bucketStyles = {
    '0-30': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
    '31-60': 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:border-orange-500/30',
    '61-90': 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/30',
    '90+': 'bg-red-100 text-red-800 border-red-300 dark:bg-red-500/20 dark:text-red-200 dark:border-red-500/40',
};
const categoryLabels = { entrada: 'Entrada', saldo: 'Saldo', taxa_contrato: 'Taxa de contrato', taxa_manutencao: 'Manutenção' };

const whatsappLink = (client) => {
    const digits = (client.phone || '').replace(/\D/g, '');
    if (digits.length < 10) return null;
    const phone = digits.length <= 11 ? '55' + digits : digits;
    const text = `Olá, ${client.name.split(' ')[0]}! Identificamos ${client.count} parcela(s) em aberto no valor atualizado de ${formatCurrency(client.updated_amount)}. Podemos ajudar com o pagamento?`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
};

// Regras de multa e juros
const showSettings = ref(false);
const settingsForm = useForm({
    auto_apply: !!props.settings.auto_apply,
    fine_percent: props.settings.fine_percent,
    interest_monthly_percent: props.settings.interest_monthly_percent,
    grace_days: props.settings.grace_days,
});
const saveSettings = () => settingsForm.put(route('finance.delinquency.settings'), { preserveScroll: true, onSuccess: () => { showSettings.value = false; } });

const kpis = computed(() => [
    { label: 'Clientes inadimplentes', value: props.summary.clients, hint: `${props.summary.bills} parcela(s) vencida(s)` },
    { label: 'Valor vencido', value: formatCurrency(props.summary.amount), hint: 'Valor original das parcelas' },
    { label: 'Valor atualizado', value: formatCurrency(props.summary.updated_amount), hint: 'Com multa e juros até hoje', highlight: true },
    { label: 'Taxa de inadimplência', value: formatPercent(props.summary.rate), hint: 'Vencido / carteira em aberto' },
    { label: 'Atraso médio', value: `${props.summary.avg_days_late} dias`, hint: 'Por parcela vencida' },
]);
</script>

<template>
    <Head title="Inadimplência" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8 pb-12">

                <!-- Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <div class="w-12 h-12 rounded-[14px] bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Inadimplência</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    Contas a receber vencidas
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input v-model="search" type="text" placeholder="Buscar cliente ou CPF..." class="w-full sm:w-64 h-10 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green">
                            <button @click="showSettings = !showSettings" class="h-10 px-4 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shrink-0" title="Regras de multa e juros">
                                Multa e juros
                            </button>
                        </div>
                    </div>

                    <!-- Regras de encargos -->
                    <form v-if="showSettings" @submit.prevent="saveSettings" class="px-6 py-5 bg-slate-50/60 dark:bg-slate-800/20 border-b border-slate-200/50 dark:border-slate-800/50 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="flex items-center gap-2 col-span-2 md:col-span-1 cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.auto_apply" :disabled="!canManage" class="rounded text-brand-green focus:ring-brand-green">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Calcular automaticamente na baixa</span>
                            </label>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Multa (%)</label>
                                <input v-model="settingsForm.fine_percent" type="number" step="0.01" min="0" :disabled="!canManage" class="w-full h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Juros ao mês (%)</label>
                                <input v-model="settingsForm.interest_monthly_percent" type="number" step="0.01" min="0" :disabled="!canManage" class="w-full h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Carência (dias)</label>
                                <input v-model="settingsForm.grace_days" type="number" step="1" min="0" :disabled="!canManage" class="w-full h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500">Multa cobrada uma vez sobre o valor da parcela; juros proporcionais por dia de atraso (juros ao mês ÷ 30). Durante a carência a parcela aparece como vencida, mas sem encargos. Pelo Código de Defesa do Consumidor, a multa não pode passar de 2%.</p>
                        <div v-if="Object.keys(settingsForm.errors).length" class="text-xs text-red-600">{{ Object.values(settingsForm.errors).join(' ') }}</div>
                        <div v-if="canManage" class="flex justify-end">
                            <button type="submit" :disabled="settingsForm.processing" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-green hover:bg-[#485638] disabled:opacity-50">Salvar regras</button>
                        </div>
                    </form>

                    <!-- Indicadores -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-slate-200/60 dark:bg-slate-800/60 rounded-b-[20px] overflow-hidden">
                        <div v-for="kpi in kpis" :key="kpi.label" class="px-5 py-4 min-w-0" :class="kpi.highlight ? 'bg-red-50 dark:bg-red-500/10' : 'bg-white dark:bg-slate-900'">
                            <div class="text-[10px] font-bold uppercase tracking-widest truncate" :class="kpi.highlight ? 'text-red-600' : 'text-slate-400'">{{ kpi.label }}</div>
                            <div class="mt-1 text-xl font-bold tracking-tight tabular-nums truncate" :class="kpi.highlight ? 'text-red-600' : 'text-slate-900 dark:text-white'">{{ kpi.value }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5 truncate">{{ kpi.hint }}</div>
                        </div>
                    </div>
                </div>

                <!-- Faixas de atraso -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                    <button
                        v-for="b in buckets"
                        :key="b.bucket"
                        @click="bucketFilter = bucketFilter === b.bucket ? null : b.bucket"
                        class="text-left p-4 rounded-2xl border transition-all"
                        :class="[bucketStyles[b.bucket], bucketFilter === b.bucket ? 'ring-2 ring-offset-1 ring-current' : 'opacity-90 hover:opacity-100']"
                    >
                        <div class="text-[10px] font-black uppercase tracking-widest">{{ bucketLabels[b.bucket] }}</div>
                        <div class="text-lg font-bold tabular-nums mt-1">{{ formatCurrency(b.updated_amount) }}</div>
                        <div class="text-[11px] mt-0.5">{{ b.count }} parcela(s) · original {{ formatCurrency(b.amount) }}</div>
                    </button>
                </div>

                <!-- Clientes -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none overflow-hidden">
                    <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                            {{ filteredClients.length }} cliente(s)<template v-if="bucketFilter"> · {{ bucketLabels[bucketFilter] }}</template>
                        </span>
                        <button v-if="bucketFilter" @click="bucketFilter = null" class="text-xs font-bold text-brand-green hover:underline">Limpar filtro</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] text-left">
                            <thead>
                                <tr class="bg-slate-50/70 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Cliente</th>
                                    <th class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Parcelas</th>
                                    <th class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vencida desde</th>
                                    <th class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Atraso</th>
                                    <th class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Original</th>
                                    <th class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Atualizado</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template v-for="client in filteredClients" :key="client.client_id">
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 cursor-pointer" @click="toggle(client.client_id)">
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform shrink-0" :class="{ 'rotate-90': expanded.has(client.client_id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">{{ client.name }}</div>
                                                    <div class="text-[11px] text-slate-500 truncate">
                                                        {{ client.cpf || 'Sem CPF' }}<template v-if="client.phone"> · {{ client.phone }}</template><template v-if="client.contracts.length"> · Contrato {{ client.contracts.join(', ') }}</template>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-center text-sm tabular-nums">{{ client.count }}</td>
                                        <td class="px-3 py-3 text-sm tabular-nums">{{ formatDate(client.oldest_due) }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="inline-flex px-2 py-0.5 rounded-md border text-[11px] font-bold" :class="bucketStyles[client.bucket]">{{ client.max_days_late }} dias</span>
                                        </td>
                                        <td class="px-3 py-3 text-right text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ formatCurrency(client.amount) }}</td>
                                        <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-red-600">{{ formatCurrency(client.updated_amount) }}</td>
                                        <td class="px-6 py-3" @click.stop>
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a v-if="whatsappLink(client)" :href="whatsappLink(client)" target="_blank" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300" title="Abrir conversa no WhatsApp com a mensagem de cobrança">WhatsApp</a>
                                                <Link :href="route('finance.receivables.index', { search: client.cpf || client.name, status: 'overdue' })" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200" title="Ver e dar baixa em Recebíveis">Dar baixa</Link>
                                                <Link v-if="client.sales_service_id" :href="route('sales.atendimentos.show', client.sales_service_id)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Atendimento</Link>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="expanded.has(client.client_id)" class="bg-slate-50/50 dark:bg-slate-800/20">
                                        <td colspan="7" class="px-6 py-3">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-[10px] uppercase tracking-widest text-slate-400">
                                                        <th class="py-1 text-left font-bold">Parcela</th>
                                                        <th class="py-1 text-left font-bold">Vencimento</th>
                                                        <th class="py-1 text-center font-bold">Atraso</th>
                                                        <th class="py-1 text-right font-bold">Valor</th>
                                                        <th class="py-1 text-right font-bold">Multa</th>
                                                        <th class="py-1 text-right font-bold">Juros</th>
                                                        <th class="py-1 text-right font-bold">Atualizado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="bill in client.bills" :key="bill.id" class="border-t border-slate-200/60 dark:border-slate-700/60">
                                                        <td class="py-1.5 text-slate-700 dark:text-slate-300">
                                                            {{ categoryLabels[bill.category] || bill.category }}<template v-if="bill.installment"> {{ bill.installment }}</template>
                                                            <span v-if="bill.description" class="text-slate-400"> — {{ bill.description }}</span>
                                                        </td>
                                                        <td class="py-1.5 tabular-nums">{{ formatDate(bill.due_date) }}</td>
                                                        <td class="py-1.5 text-center tabular-nums">{{ bill.days_late }}d</td>
                                                        <td class="py-1.5 text-right tabular-nums">{{ formatCurrency(bill.amount) }}</td>
                                                        <td class="py-1.5 text-right tabular-nums text-slate-500">{{ formatCurrency(bill.fine) }}</td>
                                                        <td class="py-1.5 text-right tabular-nums text-slate-500">{{ formatCurrency(bill.interest) }}</td>
                                                        <td class="py-1.5 text-right tabular-nums font-bold text-red-600">{{ formatCurrency(bill.updated_amount) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="!filteredClients.length">
                                    <td colspan="7" class="px-6 py-14 text-center text-sm text-slate-400">
                                        {{ summary.bills ? 'Nenhum cliente nesta faixa.' : 'Nenhuma parcela vencida. 🎉' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
