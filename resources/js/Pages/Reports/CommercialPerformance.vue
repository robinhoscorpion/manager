<script setup>
import { ref, computed, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';

const props = defineProps({
    period: String,
    totals: Object,
    byProfile: Array,
    byPoint: Array,
    professionals: Object,
});

const params = new URLSearchParams(window.location.search);
const parseParam = (key, fallback) => {
    const value = params.get(key);
    if (!value) return fallback;
    const [y, m, d] = value.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const today = new Date();
const selectedDateRange = ref({
    start: parseParam('start_date', new Date(today.getFullYear(), today.getMonth(), 1)),
    end: parseParam('end_date', new Date(today.getFullYear(), today.getMonth() + 1, 0)),
    preset: params.get('start_date') ? null : 'Este Mês'
});

const toIsoDate = (date) => new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().split('T')[0];

watch(selectedDateRange, (range) => {
    if (range?.start && range?.end) {
        router.get(route('reports.commercial-performance'), {
            start_date: toIsoDate(range.start),
            end_date: toIsoDate(range.end),
        }, { preserveState: true, replace: true });
    }
}, { deep: true });

const professionalTabs = [
    { label: 'OPC', value: 'opc' },
    { label: 'Liner', value: 'liner' },
    { label: 'Closer', value: 'closer' },
    { label: 'MKT', value: 'mkt' },
];
const activeProfessional = ref('closer');

const sections = computed(() => [
    { key: 'profile', title: 'Por Perfil', subtitle: 'Qualificação do casal', column: 'Perfil', rows: props.byProfile },
    { key: 'point', title: 'Por Ponto de Captação', subtitle: 'Onde o casal foi captado', column: 'Ponto', rows: props.byPoint },
    { key: 'professional', title: 'Por Profissional', subtitle: 'Função exercida no atendimento', column: 'Profissional', rows: props.professionals?.[activeProfessional.value] ?? [], tabs: true },
]);

const formatCurrency = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
const formatPercent = (value) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value || 0) + '%';
const formatNumber = (value) => new Intl.NumberFormat('pt-BR').format(value || 0);

const vgvShare = (vgv) => props.totals?.vgv > 0 ? (vgv / props.totals.vgv) * 100 : 0;

const kpis = computed(() => [
    { label: 'Casais atendidos', value: formatNumber(props.totals?.atendidos), hint: 'Atendimentos no período' },
    { label: 'Vendas', value: formatNumber(props.totals?.vendas), hint: 'Contratos aprovados/finalizados' },
    { label: 'Conversão', value: formatPercent(props.totals?.conversao), hint: 'Vendas / casais atendidos' },
    { label: 'VGV', value: formatCurrency(props.totals?.vgv), hint: 'Valor geral de vendas', highlight: true },
    { label: 'Ticket médio', value: formatCurrency(props.totals?.ticket_medio), hint: 'VGV / vendas' },
]);
</script>

<template>
    <Head title="Desempenho Comercial" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8 pb-12">

                <!-- Header & Toolbar (mesmo padrão de /atendimentos) -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-8 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Desempenho Comercial</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Relatórios · {{ period }}
                                </p>
                            </div>
                        </div>
                        <div class="w-full sm:w-[280px]">
                            <DateRangePicker v-model="selectedDateRange" class="w-full" />
                        </div>
                    </div>

                    <!-- KPIs: faixa única dividida, dentro do cabeçalho -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-slate-200/60 dark:bg-slate-800/60 rounded-b-[20px] overflow-hidden">
                        <div
                            v-for="kpi in kpis"
                            :key="kpi.label"
                            class="px-5 py-4 min-w-0"
                            :class="kpi.highlight
                                ? 'col-span-2 md:col-span-1 bg-[#f4f6f1] dark:bg-[#161d17]'
                                : 'bg-white dark:bg-slate-900'"
                        >
                            <div class="flex items-center gap-1.5">
                                <span v-if="kpi.highlight" class="w-1.5 h-1.5 rounded-full bg-brand-green"></span>
                                <span class="ledger-th truncate" :class="{ '!text-brand-green': kpi.highlight }">{{ kpi.label }}</span>
                            </div>
                            <div class="mt-1 text-xl xl:text-2xl font-bold tracking-tight tabular-nums truncate" :class="kpi.highlight ? 'text-brand-green' : 'text-slate-900 dark:text-white'" :title="kpi.value">{{ kpi.value }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5 truncate">{{ kpi.hint }}</div>
                        </div>
                    </div>
                </div>

                <!-- Sections -->
                <div class="space-y-8">
                    <div v-for="section in sections" :key="section.key">
                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-3 px-1">
                            <div class="flex items-center gap-2.5">
                                <span class="w-1.5 h-6 bg-brand-green rounded-full"></span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">{{ section.title }}</h3>
                                    <p class="text-[11px] text-slate-500 font-medium uppercase tracking-wider">{{ section.subtitle }}</p>
                                </div>
                            </div>
                            <div v-if="section.tabs" class="flex gap-1 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 p-1 rounded-[12px]">
                                <button
                                    v-for="tab in professionalTabs"
                                    :key="tab.value"
                                    @click="activeProfessional = tab.value"
                                    class="px-4 py-1.5 rounded-[9px] text-xs font-semibold transition-all"
                                    :class="activeProfessional === tab.value ? 'bg-brand-green text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                                >{{ tab.label }}</button>
                            </div>
                        </div>

                        <div class="ledger-card flex flex-col overflow-hidden bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                            <div class="w-full overflow-x-auto">
                                <div class="min-w-[760px]">
                                    <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                                        <div class="flex-1 ledger-th !text-black dark:!text-slate-400">{{ section.column }}</div>
                                        <div class="w-32 text-right ledger-th !text-black dark:!text-slate-400">Casais atendidos</div>
                                        <div class="w-20 text-right ledger-th !text-black dark:!text-slate-400">Vendas</div>
                                        <div class="w-44 ledger-th !text-black dark:!text-slate-400">Conversão</div>
                                        <div class="w-32 text-right ledger-th !text-black dark:!text-slate-400">Ticket médio</div>
                                        <div class="w-36 text-right ledger-th !text-black dark:!text-slate-400">VGV</div>
                                    </div>

                                    <div
                                        v-for="row in section.rows"
                                        :key="row.label"
                                        class="ledger-row flex items-center gap-3 px-6 py-3 relative"
                                    >
                                        <div class="ledger-row-accent"></div>
                                        <div class="flex-1 min-w-0 truncate ledger-client-name">{{ row.label }}</div>
                                        <div class="w-32 text-right tabular-nums text-sm text-slate-600 dark:text-slate-300">{{ formatNumber(row.atendidos) }}</div>
                                        <div class="w-20 text-right">
                                            <span class="ledger-time-tag tabular-nums">{{ formatNumber(row.vendas) }}</span>
                                        </div>
                                        <div class="w-44 flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full bg-brand-green" :style="{ width: Math.min(row.conversao, 100) + '%' }"></div>
                                            </div>
                                            <span class="w-12 text-right tabular-nums text-xs font-semibold text-slate-700 dark:text-slate-300">{{ formatPercent(row.conversao) }}</span>
                                        </div>
                                        <div class="w-32 text-right tabular-nums text-sm text-slate-600 dark:text-slate-300">{{ formatCurrency(row.ticket_medio) }}</div>
                                        <div class="w-36 text-right">
                                            <div class="tabular-nums text-sm font-bold text-slate-900 dark:text-white">{{ formatCurrency(row.vgv) }}</div>
                                            <div class="tabular-nums text-[10px] ledger-cell-muted">{{ formatPercent(vgvShare(row.vgv)) }} do VGV</div>
                                        </div>
                                    </div>

                                    <div v-if="!section.rows.length" class="px-6 py-12 text-center text-sm text-slate-400">
                                        Nenhum atendimento no período.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ─── LEDGER TABLE (mesmo estilo de /atendimentos) ─── */
.ledger-card {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}
.dark .ledger-card {
    background: #0d1117;
    border-color: rgba(255, 255, 255, 0.05);
}
html:not(.dark) .ledger-card {
    background: #ffffff;
    border-color: rgba(0, 0, 0, 0.07);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.ledger-header-row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(0, 0, 0, 0.25);
}
html:not(.dark) .ledger-header-row {
    border-bottom: 1px solid rgba(0, 0, 0, 0.07);
    background: rgba(0, 0, 0, 0.025);
}

.ledger-th {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #475569;
}
html:not(.dark) .ledger-th {
    color: #94a3b8;
}

.ledger-row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    transition: background 0.15s;
}
html:not(.dark) .ledger-row {
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}
.ledger-row:last-child {
    border-bottom: none;
}
.ledger-row:hover {
    background: rgba(99, 102, 241, 0.04);
}
html:not(.dark) .ledger-row:hover {
    background: rgba(99, 102, 241, 0.03);
}

.ledger-row-accent {
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 2px;
    border-radius: 2px;
    background: #6366f1;
    opacity: 0;
    transition: opacity 0.15s;
}
.ledger-row:hover .ledger-row-accent {
    opacity: 1;
}

.ledger-cell-muted {
    font-family: 'JetBrains Mono', monospace;
    color: #334155;
}
html:not(.dark) .ledger-cell-muted {
    color: #94a3b8;
}

.ledger-time-tag {
    display: inline-block;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 20px;
    color: #818cf8;
    letter-spacing: 0.5px;
}
html:not(.dark) .ledger-time-tag {
    background: rgba(99, 102, 241, 0.06);
    border-color: rgba(99, 102, 241, 0.2);
    color: #6366f1;
}

.ledger-client-name {
    font-size: 0.82rem;
    font-weight: 500;
    color: #e2e8f0;
    letter-spacing: 0.1px;
}
html:not(.dark) .ledger-client-name {
    color: #1e293b;
}
</style>
