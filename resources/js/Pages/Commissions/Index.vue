<script setup>
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';

const props = defineProps({
    proposals: Object,
    metrics: Object,
    filters: Object,
});

const today = new Date();
const selectedDateRange = ref({
    start: props.filters?.start_date ? new Date(props.filters.start_date) : today,
    end: props.filters?.end_date ? new Date(props.filters.end_date) : today,
    preset: props.filters?.start_date ? 'Personalizado' : 'Hoje'
});

watch(() => selectedDateRange.value, (newRange) => {
    if (newRange && newRange.start && newRange.end) {
        // Formatar datas considerando o fuso local (evita que UTC subtraia 1 dia)
        const formatLocal = (d) => {
            const date = new Date(d);
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        };
        
        const startStr = formatLocal(newRange.start);
        const endStr = formatLocal(newRange.end);
        
        router.get(route('commissions.index'), {
            start_date: startStr,
            end_date: endStr
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '--';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

const formatShortName = (fullName) => {
    if (!fullName) return '';
    const parts = fullName.trim().split(/\s+/);
    if (parts.length > 1) {
        return `${parts[0]} ${parts[1]}`.toUpperCase();
    }
    return parts[0].toUpperCase();
};

const getInitials = (name) => {
    if (!name) return '';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const getCommission = (proposal, roleName) => {
    if (!proposal.commissions) return null;
    return proposal.commissions.find(c => c.role.toLowerCase() === roleName.toLowerCase());
};

const getStatusMetadata = (status) => {
    const statuses = {
        'approved': { label: 'Aprovado', color: 'bg-emerald-100 border-emerald-200 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400' },
        'pending': { label: 'Pendente', color: 'bg-amber-100 border-amber-200 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400' },
        'cancelled': { label: 'Cancelado', color: 'bg-red-100 border-red-200 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400' },
    };
    return statuses[status?.toLowerCase()] || { label: status || '--', color: 'bg-slate-100 border-slate-200 text-slate-700 dark:border-slate-500/20 dark:bg-slate-500/10 dark:text-slate-400' };
};

</script>

<template>
    <Head title="Grade de Comissões" />

    <AuthenticatedLayout>
        <template #header>
            <div class="hidden"></div>
        </template>

        <div class="w-full h-auto pt-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Comissões</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Grade por Contrato
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-6 py-4 bg-slate-50/40 dark:bg-slate-800/20 rounded-b-[20px] flex flex-col md:flex-row items-center justify-end gap-3">
                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <div class="w-full sm:w-[280px]">
                                <DateRangePicker v-model="selectedDateRange" class="w-full" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Metrics -->
                <div v-if="metrics" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total VGV -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">VGV Total</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(metrics.total_vgv) }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center border border-blue-100 dark:border-blue-500/20 text-blue-600 dark:text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Commissions -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-brand-green/30 dark:border-brand-green/30 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-brand-green/10 rounded-full blur-2xl group-hover:bg-brand-green/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-brand-green/80 uppercase tracking-wider mb-1">Total Comissões</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-brand-green tracking-tight">{{ formatCurrency(metrics.total_commissions) }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-brand-green/10 flex items-center justify-center border border-brand-green/20 text-brand-green">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Contracts -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Contratos Gerados</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ metrics.total_contracts }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center border border-purple-100 dark:border-purple-500/20 text-purple-600 dark:text-purple-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    <div class="w-full overflow-x-auto">
                        <div class="min-w-[1024px]">
                            <!-- Table Header -->
                            <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                                <div class="w-40 text-left ledger-th !text-black">Contrato / Produto</div>
                                <div class="w-28 text-center ledger-th !text-black">Data / Status</div>
                                <div class="flex-1 min-w-[150px] text-left ledger-th !text-black">Cliente</div>
                                <div class="w-28 text-right ledger-th !text-black">Valores (Base / VGV)</div>
                                <div class="flex gap-4">
                                    <div class="w-44 text-left ledger-th !text-black">Promotor (OPC)</div>
                                    <div class="w-44 text-left ledger-th !text-black">Consultor (Liner)</div>
                                    <div class="w-44 text-left ledger-th !text-black">Supervisor (Closer)</div>
                                </div>
                            </div>

                            <!-- Table Body -->
                            <div class="w-full">
                                <template v-if="proposals.data.length > 0">
                                    <div 
                                        v-for="proposal in proposals.data" 
                                        :key="proposal.id"
                                        class="ledger-row flex items-center gap-3 px-6 py-4 cursor-default group relative hover:z-50"
                                    >
                                        <!-- Accent bar on hover -->
                                        <div class="ledger-row-accent"></div>

                                        <div class="w-40 text-left flex flex-col justify-center">
                                            <span class="text-xs font-bold text-brand-green">#{{ proposal.contract_number || '--' }}</span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate w-full" :title="proposal.product?.name || '--'">
                                                {{ proposal.product?.name || '--' }}
                                            </span>
                                        </div>
                                        
                                        <div class="w-28 text-center flex flex-col items-center justify-center gap-1">
                                            <span class="font-mono text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ formatDate(proposal.created_at) }}</span>
                                            <span :class="['text-[9px] px-1.5 py-0.5 rounded-md font-semibold border uppercase tracking-wider', getStatusMetadata(proposal.status).color]">
                                                {{ getStatusMetadata(proposal.status).label }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex-1 min-w-[150px] text-left truncate flex flex-col justify-center">
                                            <span class="ledger-client-name group-hover:text-brand-green transition-colors">{{ formatShortName(proposal.client?.nome) }}</span>
                                        </div>
                                        
                                        <div class="w-28 text-right flex flex-col justify-center">
                                            <span class="text-xs font-bold text-slate-900 dark:text-white" title="VGV">{{ formatCurrency(proposal.total_value) }}</span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5" title="Valor Base">B: {{ formatCurrency(proposal.base_value) }}</span>
                                        </div>
                                        
                                        <div class="flex gap-4">
                                            <!-- Promotor -->
                                            <div class="w-44 flex items-center gap-3 group/avatar">
                                                <template v-if="getCommission(proposal, 'Promotor')">
                                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-400 shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm group-hover/avatar:border-brand-green group-hover/avatar:text-brand-green transition-colors">
                                                        {{ getInitials(getCommission(proposal, 'Promotor')?.user?.name) }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-semibold text-slate-900 dark:text-white truncate" style="max-width: 120px;" :title="getCommission(proposal, 'Promotor')?.user?.name">
                                                            {{ getCommission(proposal, 'Promotor')?.user?.name }}
                                                        </span>
                                                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                            {{ formatCurrency(getCommission(proposal, 'Promotor')?.total_amount) }} 
                                                            <span class="font-medium text-[9px] text-slate-400 ml-0.5 px-1 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10">{{ getCommission(proposal, 'Promotor')?.base_commission_percentage }}%</span>
                                                        </span>
                                                    </div>
                                                </template>
                                                <span v-else class="text-xs text-slate-400 italic">--</span>
                                            </div>
                                            
                                            <!-- Consultor -->
                                            <div class="w-44 flex items-center gap-3 group/avatar">
                                                <template v-if="getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor')">
                                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-400 shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm group-hover/avatar:border-brand-green group-hover/avatar:text-brand-green transition-colors">
                                                        {{ getInitials((getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor'))?.user?.name) }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-semibold text-slate-900 dark:text-white truncate" style="max-width: 120px;" :title="(getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor'))?.user?.name">
                                                            {{ (getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor'))?.user?.name }}
                                                        </span>
                                                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                            {{ formatCurrency((getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor'))?.total_amount) }} 
                                                            <span class="font-medium text-[9px] text-slate-400 ml-0.5 px-1 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10">{{ (getCommission(proposal, 'Liner') || getCommission(proposal, 'Consultor'))?.base_commission_percentage }}%</span>
                                                        </span>
                                                    </div>
                                                </template>
                                                <span v-else class="text-xs text-slate-400 italic">--</span>
                                            </div>
                                            
                                            <!-- Supervisor -->
                                            <div class="w-44 flex items-center gap-3 group/avatar">
                                                <template v-if="getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor')">
                                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-400 shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm group-hover/avatar:border-brand-green group-hover/avatar:text-brand-green transition-colors">
                                                        {{ getInitials((getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor'))?.user?.name) }}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-semibold text-slate-900 dark:text-white truncate" style="max-width: 120px;" :title="(getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor'))?.user?.name">
                                                            {{ (getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor'))?.user?.name }}
                                                        </span>
                                                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                            {{ formatCurrency((getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor'))?.total_amount) }} 
                                                            <span class="font-medium text-[9px] text-slate-400 ml-0.5 px-1 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10">{{ (getCommission(proposal, 'Closer') || getCommission(proposal, 'Supervisor'))?.base_commission_percentage }}%</span>
                                                        </span>
                                                    </div>
                                                </template>
                                                <span v-else class="text-xs text-slate-400 italic">--</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="ledger-empty">
                                    <div class="ledger-empty-ring">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="ledger-empty-title">Nenhuma comissão gerada.</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="proposals.data.length > 0" class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-white/[0.02]">
                        <Pagination :links="proposals.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Ledger Table Styling */
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

/* Row */
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

/* Left accent bar */
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

/* Client name */
.ledger-client-name {
    font-size: 0.82rem;
    font-weight: 500;
    color: #e2e8f0;
    letter-spacing: 0.1px;
}

html:not(.dark) .ledger-client-name {
    color: #1e293b;
}

/* Empty state */
.ledger-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 80px 20px;
    text-align: center;
}

.ledger-empty-ring {
    width: 72px;
    height: 72px;
    border: 2px dashed rgba(255, 255, 255, 0.07);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1f2937;
    animation: spin 12s linear infinite;
}

html:not(.dark) .ledger-empty-ring {
    border-color: rgba(0, 0, 0, 0.08);
    color: #cbd5e1;
}

.ledger-empty-title {
    font-size: 1rem;
    font-weight: 600;
    color: #f1f5f9;
    margin: 0;
}

html:not(.dark) .ledger-empty-title {
    color: #1e293b;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
