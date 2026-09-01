<script setup>
import { ref, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';

const props = defineProps({
    rankingData: {
        type: Object,
        required: true
    }
});

const today = new Date();
const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

const selectedDateRange = ref({
    start: firstDayOfMonth,
    end: lastDayOfMonth,
    preset: 'Este Mês'
});

watch(selectedDateRange, (newRange) => {
    if (newRange && newRange.start && newRange.end) {
        // Obter timezone local para enviar a data correta
        const start_date = new Date(newRange.start.getTime() - (newRange.start.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        const end_date = new Date(newRange.end.getTime() - (newRange.end.getTimezoneOffset() * 60000)).toISOString().split('T')[0];

        router.get(route('reports.sales-ranking'), { start_date, end_date }, {
            preserveState: true,
            replace: true
        });
    }
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

const formatPercent = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value) + '%';
};

const getRankClass = (index) => {
    if (index === 0) return 'bg-gradient-to-br from-amber-200 to-amber-400 text-amber-900 shadow-sm shadow-amber-500/20 border border-amber-300';
    if (index === 1) return 'bg-gradient-to-br from-slate-200 to-slate-400 text-slate-800 shadow-sm shadow-slate-500/20 border border-slate-300';
    if (index === 2) return 'bg-gradient-to-br from-orange-200 to-orange-400 text-orange-900 shadow-sm shadow-orange-500/20 border border-orange-300';
    return 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
};

const printReport = () => {
    const start_date = new Date(selectedDateRange.value.start.getTime() - (selectedDateRange.value.start.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    const end_date = new Date(selectedDateRange.value.end.getTime() - (selectedDateRange.value.end.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    
    window.open(route('reports.sales-ranking.pdf', { start_date, end_date }), '_blank');
};
</script>

<style>
@media print {
    /* Oculta tudo na página por padrão no momento da impressão */
    body * {
        visibility: hidden;
    }
    
    /* Remove fundo e margens indesejadas */
    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Torna visível apenas a área de impressão e seus filhos */
    #printable-area, #printable-area * {
        visibility: visible;
    }

    /* Posiciona a área de impressão no topo esquerdo da página, ocupando 100% da largura */
    #printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Utilitário Tailwind manual para ocultar itens específicos dentro da área de impressão (como botões) */
    .print\:hidden {
        display: none !important;
    }

    /* Força os estilos e cores a serem impressos (importante para gráficos e tabelas coloridas) */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<template>
    <Head title="Ranking de Vendas" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div id="printable-area" class="w-full h-auto pt-8 pb-12">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20 mx-4 sm:mx-6 lg:mx-8 max-w-7xl lg:max-w-none transition-all duration-300 hover:shadow-md">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-gradient-to-br from-brand-green/20 to-brand-green/5 border border-brand-green/30 flex items-center justify-center shadow-inner">
                                <svg class="w-6 h-6 text-brand-green drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">Ranking de Vendas</h2>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest flex items-center justify-center sm:justify-start gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-green animate-pulse shadow-[0_0_8px_rgba(var(--brand-green-rgb),0.6)]"></span>
                                    Relatórios Gerenciais
                                </p>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto text-center sm:text-right hidden sm:block">
                            <h3 class="text-lg font-black text-slate-700 dark:text-slate-300 tracking-tight">RESUMO MENSAL DA EQUIPE</h3>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-b-[20px] flex flex-col md:flex-row items-center justify-end gap-3 border-t border-white dark:border-slate-700/50 print:hidden">
                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <button @click="printReport" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-[14px] font-bold text-sm transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Gerar PDF
                            </button>
                            <div class="w-full sm:w-[280px]">
                                <DateRangePicker v-model="selectedDateRange" class="w-full shadow-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="mx-4 sm:mx-6 lg:mx-8 max-w-7xl lg:max-w-none space-y-10 pb-12">
                    
                    <!-- PROMOTORES Section -->
                    <div class="group/section">
                        <div class="flex items-center justify-between mb-5 px-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 p-[1px] shadow-lg shadow-cyan-500/20">
                                    <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[11px] flex items-center justify-center">
                                        <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" /></svg>
                                    </div>
                                </div>
                                <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight uppercase">Ranking de <span class="text-cyan-600 dark:text-cyan-400">Promotores</span></h3>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-cyan-500/30">
                            <div class="w-full overflow-x-auto custom-scrollbar">
                                <div class="min-w-[1000px]">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Promotor</span>
                                                </th>
                                                <th :colspan="(rankingData.active_qualifications?.length || 0) + 1" class="px-6 py-3 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Qualificação</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-emerald-50/50 dark:bg-emerald-900/20 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Vendidos</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">% Aprov.</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-700/80 align-middle text-right">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Volume Total</span>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Show</span>
                                                </th>
                                                <th v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center" :title="qual.name">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ qual.code }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-for="(item, index) in rankingData.opcs" :key="index" class="group bg-white dark:bg-slate-900 hover:bg-cyan-50/30 dark:hover:bg-cyan-900/10 transition-colors duration-200">
                                                <td class="px-6 py-4 border-r border-slate-100 dark:border-slate-800">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center justify-center w-7 h-7 rounded-full font-black text-[11px] transition-transform duration-300 group-hover:scale-110" :class="getRankClass(index)">
                                                            {{ index + 1 }}
                                                        </div>
                                                        <div class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-cyan-700 dark:group-hover:text-cyan-400 transition-colors">{{ item.name }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">{{ item.qualificacao.show }}</td>
                                                <td v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">
                                                    {{ item.qualificacao[qual.code] ?? 0 }}
                                                </td>
                                                
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-900/10 group-hover:bg-emerald-100/50 dark:group-hover:bg-emerald-900/30 transition-colors">
                                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-black text-sm">
                                                        {{ item.vendidos }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800">
                                                    <div class="font-bold text-slate-700 dark:text-slate-300">{{ formatPercent(item.aproveitamento) }}</div>
                                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2 overflow-hidden">
                                                        <div class="bg-cyan-500 h-1.5 rounded-full" :style="{ width: Math.min(item.aproveitamento, 100) + '%' }"></div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="font-black text-slate-900 dark:text-white text-base tracking-tight">{{ formatCurrency(item.total) }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONSULTORES Section -->
                    <div class="group/section">
                        <div class="flex items-center justify-between mb-5 px-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-green to-emerald-600 p-[1px] shadow-lg shadow-emerald-500/20">
                                    <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[11px] flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </div>
                                </div>
                                <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight uppercase">Ranking de <span class="text-emerald-600 dark:text-emerald-400">Consultores</span></h3>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-emerald-500/30">
                            <div class="w-full overflow-x-auto custom-scrollbar">
                                <div class="min-w-[1000px]">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Consultor</span>
                                                </th>
                                                <th :colspan="(rankingData.active_qualifications?.length || 0) + 1" class="px-6 py-3 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Qualificação</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-emerald-50/50 dark:bg-emerald-900/20 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Vendidos</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">% Aprov.</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-700/80 align-middle text-right">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Volume Total</span>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Show</span>
                                                </th>
                                                <th v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center" :title="qual.name">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ qual.code }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-for="(item, index) in rankingData.liners" :key="index" class="group bg-white dark:bg-slate-900 hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors duration-200">
                                                <td class="px-6 py-4 border-r border-slate-100 dark:border-slate-800">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center justify-center w-7 h-7 rounded-full font-black text-[11px] transition-transform duration-300 group-hover:scale-110" :class="getRankClass(index)">
                                                            {{ index + 1 }}
                                                        </div>
                                                        <div class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">{{ item.name }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">{{ item.qualificacao.show }}</td>
                                                <td v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">
                                                    {{ item.qualificacao[qual.code] ?? 0 }}
                                                </td>
                                                
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-900/10 group-hover:bg-emerald-100/50 dark:group-hover:bg-emerald-900/30 transition-colors">
                                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-black text-sm">
                                                        {{ item.vendidos }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800">
                                                    <div class="font-bold text-slate-700 dark:text-slate-300">{{ formatPercent(item.aproveitamento) }}</div>
                                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2 overflow-hidden">
                                                        <div class="bg-emerald-500 h-1.5 rounded-full" :style="{ width: Math.min(item.aproveitamento, 100) + '%' }"></div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="font-black text-slate-900 dark:text-white text-base tracking-tight">{{ formatCurrency(item.total) }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUPERVISORES Section -->
                    <div class="group/section">
                        <div class="flex items-center justify-between mb-5 px-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-400 to-rose-600 p-[1px] shadow-lg shadow-pink-500/20">
                                    <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[11px] flex items-center justify-center">
                                        <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 11V9a2 2 0 00-2-2m2 4v4a2 2 0 104 0v-1m-4-3H9m2 0h4m6 1a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                </div>
                                <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight uppercase">Ranking de <span class="text-pink-600 dark:text-pink-400">Supervisores</span></h3>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-pink-500/30">
                            <div class="w-full overflow-x-auto custom-scrollbar">
                                <div class="min-w-[1000px]">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Supervisor</span>
                                                </th>
                                                <th :colspan="(rankingData.active_qualifications?.length || 0) + 1" class="px-6 py-3 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Qualificação</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-emerald-50/50 dark:bg-emerald-900/20 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Vendidos</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-r border-slate-200/80 dark:border-slate-700/80 align-middle text-center">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">% Aprov.</span>
                                                </th>
                                                <th rowspan="2" class="px-6 py-5 bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-700/80 align-middle text-right">
                                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Volume Total</span>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Show</span>
                                                </th>
                                                <th v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-2.5 bg-white dark:bg-slate-900/50 border-b border-r border-slate-200/80 dark:border-slate-700/80 text-center" :title="qual.name">
                                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ qual.code }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-for="(item, index) in rankingData.closers" :key="index" class="group bg-white dark:bg-slate-900 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors duration-200">
                                                <td class="px-6 py-4 border-r border-slate-100 dark:border-slate-800">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center justify-center w-7 h-7 rounded-full font-black text-[11px] transition-transform duration-300 group-hover:scale-110" :class="getRankClass(index)">
                                                            {{ index + 1 }}
                                                        </div>
                                                        <div class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">{{ item.name }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">{{ item.qualificacao.show }}</td>
                                                <td v-for="qual in rankingData.active_qualifications" :key="qual.code" class="px-3 py-4 text-center border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-600 dark:text-slate-400">
                                                    {{ item.qualificacao[qual.code] ?? 0 }}
                                                </td>
                                                
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-900/10 group-hover:bg-emerald-100/50 dark:group-hover:bg-emerald-900/30 transition-colors">
                                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-black text-sm">
                                                        {{ item.vendidos }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-center border-r border-slate-100 dark:border-slate-800">
                                                    <div class="font-bold text-slate-700 dark:text-slate-300">{{ formatPercent(item.aproveitamento) }}</div>
                                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2 overflow-hidden">
                                                        <div class="bg-pink-500 h-1.5 rounded-full" :style="{ width: Math.min(item.aproveitamento, 100) + '%' }"></div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="font-black text-slate-900 dark:text-white text-base tracking-tight">{{ formatCurrency(item.total) }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
