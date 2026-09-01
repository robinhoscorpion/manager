<script setup>
import { ref, computed } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
    proposals: Object,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const expandedRows = ref([]);

const filtersForm = useForm({
    only_discrepancies: props.filters?.only_discrepancies === 'true' || false,
});

const performSearch = debounce(() => {
    router.get(route('finance.sales-control.index'), {
        search: search.value,
        only_discrepancies: filtersForm.only_discrepancies,
    }, {
        preserveState: true,
        preserveScroll: true
    });
}, 500);

const conciliationForms = ref({});

const toggleRow = (proposal) => {
    const id = proposal.id;
    if (expandedRows.value.includes(id)) {
        expandedRows.value = expandedRows.value.filter(rowId => rowId !== id);
    } else {
        expandedRows.value.push(id);
        if (!conciliationForms.value[id]) {
            // Calcular valores esperados baseados nos pagamentos da proposta
            let expectedDown = 0;
            let expectedTaxes = 0;
            let expectedBalance = 0;
            
            if (proposal.payments) {
                expectedDown = proposal.payments.filter(p => p.category === 'entrada').reduce((sum, p) => sum + parseFloat(p.total_value || 0), 0);
                expectedTaxes = proposal.payments.filter(p => p.category === 'taxa_contrato').reduce((sum, p) => sum + parseFloat(p.total_value || 0), 0);
                expectedBalance = proposal.payments.filter(p => p.category === 'saldo').reduce((sum, p) => sum + parseFloat(p.total_value || 0), 0);
            }

            conciliationForms.value[id] = useForm({
                received_down_payment: proposal.received_down_payment !== null ? proposal.received_down_payment : (expectedDown || ''),
                received_taxes: proposal.received_taxes !== null ? proposal.received_taxes : (expectedTaxes || ''),
                received_balance: proposal.received_balance !== null ? proposal.received_balance : (expectedBalance || '')
            });
        }
    }
};

const saveConciliation = (proposalId) => {
    conciliationForms.value[proposalId].patch(route('finance.sales-control.conciliation', proposalId), {
        preserveScroll: true
    });
};

const onConciliationInput = (e, proposalId, field) => {
    let val = e.target.value.replace(/\D/g, '');
    conciliationForms.value[proposalId][field] = parseFloat(val) / 100;
};

const auditForm = useForm({
    audit_status: '',
    audit_reason: ''
});

const activeAuditId = ref(null);
const showRejectModal = ref(false);

const approveAudit = (proposal) => {
    auditForm.audit_status = 'approved';
    auditForm.audit_reason = '';
    auditForm.patch(route('finance.sales-control.audit', proposal.id), {
        preserveScroll: true
    });
};

const openRejectModal = (proposal) => {
    activeAuditId.value = proposal.id;
    auditForm.audit_status = 'rejected';
    auditForm.audit_reason = proposal.audit_reason || '';
    showRejectModal.value = true;
};

const submitReject = () => {
    auditForm.patch(route('finance.sales-control.audit', activeAuditId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '--';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

const getInitials = (name) => {
    if (!name) return '';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const hasDiscrepancy = (proposal) => {
    const gross = parseFloat(proposal.gross_value || proposal.total_value || 0);
    const rec_down = parseFloat(proposal.received_down_payment || 0);
    const rec_tax = parseFloat(proposal.received_taxes || 0);
    const rec_bal = parseFloat(proposal.received_balance || 0);
    const total_received = rec_down + rec_tax + rec_bal;
    
    if (total_received === 0 && gross > 0) {
        const base = parseFloat(proposal.base_value || 0);
        const taxes = parseFloat(proposal.taxes || 0);
        if(base === 0 && taxes === 0) return true;
        return Math.abs(gross - (base + taxes)) > 1.00;
    }

    return Math.abs(gross - total_received) > 1.00;
};
</script>

<template>
    <Head title="Auditoria de Vendas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-3">
                        <div class="p-2 bg-brand-green/20 rounded-lg">
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        Auditoria de Vendas
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Conferência financeira, divergências e aprovação de contratos.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <!-- Modal Reprovar Auditoria -->
            <div v-if="showRejectModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#1a1f2e] w-full max-w-md rounded-2xl shadow-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-red-50 dark:bg-red-500/10">
                        <h3 class="text-sm font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Reprovar Auditoria
                        </h3>
                        <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Por favor, descreva o motivo da reprovação. Esta informação ficará visível no contrato.</p>
                        <textarea v-model="auditForm.audit_reason" rows="4" class="w-full bg-slate-50 dark:bg-[#0f1219] border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white p-3 focus:ring-red-500 focus:border-red-500" placeholder="Motivo da divergência..."></textarea>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-[#0f1219] border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
                        <button @click="showRejectModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            Cancelar
                        </button>
                        <button @click="submitReject" :disabled="auditForm.processing" class="px-4 py-2 text-xs font-bold bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                            Confirmar Reprovação
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Líquido -->
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-emerald-500/30 transition-colors flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 bg-emerald-500/10 text-emerald-500 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Líquido Recebido</p>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mt-auto">{{ formatCurrency(metrics.total_liquido) }}</h3>
                    </div>
                </div>

                <!-- Total Base -->
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-blue-500/30 transition-colors flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 bg-blue-500/10 text-blue-500 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Base</p>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mt-auto">{{ formatCurrency(metrics.total_base) }}</h3>
                    </div>
                </div>

                <!-- Taxas -->
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-orange-500/30 transition-colors flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-orange-500/5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 bg-orange-500/10 text-orange-500 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total de Taxas</p>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mt-auto">{{ formatCurrency(metrics.taxas) }}</h3>
                    </div>
                </div>

                <!-- Pendente Auditoria (Count) -->
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-purple-500/30 transition-colors flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 bg-purple-500/10 text-purple-500 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pendentes Audit.</p>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mt-auto">
                            {{ proposals.data.filter(p => p.audit_status === 'pending').length }} Contratos
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Table & Filters -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-2 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    <div class="relative w-full flex-1 max-w-xl">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input v-model="search" @input="performSearch" type="text" placeholder="Buscar por contrato ou nome..." class="w-full pl-8 pr-2 py-1.5 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-brand-green/20 placeholder-slate-400 h-8">
                    </div>
                    <div class="flex items-center gap-2" v-if="can('controle_vendas.gerenciar')">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-600 dark:text-slate-400 select-none">
                            <div class="relative">
                                <input type="checkbox" v-model="filtersForm.only_discrepancies" @change="performSearch" class="sr-only">
                                <div class="block w-8 h-5 bg-slate-200 dark:bg-slate-700 rounded-full transition-colors" :class="{'bg-red-500 dark:bg-red-500': filtersForm.only_discrepancies}"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform" :class="{'transform translate-x-3': filtersForm.only_discrepancies}"></div>
                            </div>
                            <span>Apenas Divergências</span>
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead class="text-[10px] font-bold text-slate-500 uppercase tracking-widest bg-slate-50/50 dark:bg-white/[0.02] border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 w-8"></th>
                                <th scope="col" class="px-4 py-3">Contrato</th>
                                <th scope="col" class="px-4 py-3">Data</th>
                                <th scope="col" class="px-4 py-3">Proprietários</th>
                                <th scope="col" class="px-4 py-3 text-right">Val. Bruto</th>
                                <th scope="col" class="px-4 py-3 text-center">Auditoria</th>
                                <th scope="col" class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="proposals.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                                    Nenhuma venda encontrada para exibir.
                                </td>
                            </tr>
                            <template v-for="proposal in proposals.data" :key="proposal.id">
                                <!-- Main Row -->
                                <tr :class="{'bg-slate-50 dark:bg-slate-800/30': expandedRows.includes(proposal.id), 'hover:bg-slate-50 dark:hover:bg-slate-800/50': !expandedRows.includes(proposal.id)}" class="border-b border-slate-100 dark:border-slate-800 transition-colors">
                                    <td class="px-4 py-3">
                                        <button @click="toggleRow(proposal)" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                            <svg class="w-4 h-4 transform transition-transform" :class="{'rotate-90': expandedRows.includes(proposal.id)}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-xs font-bold text-brand-green dark:text-brand-green flex items-center gap-2">
                                        #{{ proposal.contract_number || '--' }}
                                        <div v-if="hasDiscrepancy(proposal)" class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Divergência de Valores"></div>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-xs font-medium text-slate-900 dark:text-white">
                                        {{ formatDate(proposal.created_at) }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                {{ getInitials(proposal.client?.nome) }}
                                            </div>
                                            <span class="text-xs font-medium text-slate-900 dark:text-white">
                                                {{ proposal.client?.nome }}
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white text-right">
                                        {{ formatCurrency(proposal.gross_value || proposal.total_value) }}
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center">
                                        <div v-if="proposal.status === 'cancelled'" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-500/20">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            CANCELADO
                                        </div>
                                        <div v-else-if="proposal.audit_status === 'approved'" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            APROVADO
                                        </div>
                                        <div v-else-if="proposal.audit_status === 'rejected'" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-500/20" :title="proposal.audit_reason">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            REPROVADO
                                        </div>
                                        <div v-else class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            PENDENTE
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <template v-if="proposal.status !== 'cancelled'">
                                                <button @click="approveAudit(proposal)" title="Aprovar Auditoria" class="p-1.5 rounded-md text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                                <button @click="openRejectModal(proposal)" title="Reprovar Auditoria" class="p-1.5 rounded-md text-red-600 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-500/10 dark:hover:bg-red-500/20 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </template>
                                            <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                                            <!-- Botão PDF -->
                                            <a v-if="proposal.sales_service_id" :href="route('sales.atendimentos.contrato.pdf', proposal.sales_service_id)" target="_blank" title="Ver Contrato PDF" class="p-1.5 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1.5m1.5 0H15m-6 4h6m-6 4h6" /></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Expanded Row -->
                                <tr v-if="expandedRows.includes(proposal.id)" class="bg-slate-50/50 dark:bg-[#0c0e14] border-b border-slate-100 dark:border-slate-800">
                                    <td colspan="7" class="p-0">
                                        <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in slide-in-from-top-2 duration-200">
                                            
                                            <!-- Coluna 1: Check de Valores -->
                                            <div class="space-y-3">
                                                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    Conciliação de Valores
                                                </h4>
                                                
                                                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 space-y-2 text-xs">
                                                    <div class="flex justify-between items-center text-slate-600 dark:text-slate-400 mb-2">
                                                        <span>Valor Bruto Vendido (A)</span>
                                                        <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(proposal.gross_value || proposal.total_value) }}</span>
                                                    </div>
                                                    
                                                    <div v-if="conciliationForms[proposal.id]" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-600 dark:text-slate-400">Entrada Recebida</span>
                                                            <input type="text" :value="formatCurrency(conciliationForms[proposal.id].received_down_payment).replace('R$', '').trim()" @input="onConciliationInput($event, proposal.id, 'received_down_payment')" class="w-24 text-right bg-slate-50 dark:bg-[#0f1219] border border-slate-200 dark:border-slate-700 rounded-lg text-xs p-1 focus:ring-brand-green/20" placeholder="0,00">
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-600 dark:text-slate-400">Taxa Recebida</span>
                                                            <input type="text" :value="formatCurrency(conciliationForms[proposal.id].received_taxes).replace('R$', '').trim()" @input="onConciliationInput($event, proposal.id, 'received_taxes')" class="w-24 text-right bg-slate-50 dark:bg-[#0f1219] border border-slate-200 dark:border-slate-700 rounded-lg text-xs p-1 focus:ring-brand-green/20" placeholder="0,00">
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-600 dark:text-slate-400">Saldo Confirmado</span>
                                                            <input type="text" :value="formatCurrency(conciliationForms[proposal.id].received_balance).replace('R$', '').trim()" @input="onConciliationInput($event, proposal.id, 'received_balance')" class="w-24 text-right bg-slate-50 dark:bg-[#0f1219] border border-slate-200 dark:border-slate-700 rounded-lg text-xs p-1 focus:ring-brand-green/20" placeholder="0,00">
                                                        </div>
                                                        
                                                        <div class="flex justify-between items-center pt-2 mt-2 border-t border-slate-200 dark:border-slate-700">
                                                            <span class="font-bold text-slate-900 dark:text-white">Total Validado (B)</span>
                                                            <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency((parseFloat(conciliationForms[proposal.id].received_down_payment||0) + parseFloat(conciliationForms[proposal.id].received_taxes||0) + parseFloat(conciliationForms[proposal.id].received_balance||0))) }}</span>
                                                        </div>
                                                        
                                                        <button @click="saveConciliation(proposal.id)" :disabled="conciliationForms[proposal.id].processing" class="w-full mt-2 bg-brand-green hover:bg-brand-green/90 text-white font-bold py-1.5 rounded-lg text-xs transition-colors flex justify-center items-center gap-2">
                                                            <svg v-if="conciliationForms[proposal.id].processing" class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                            Salvar Valores
                                                        </button>
                                                    </div>

                                                    <div v-if="hasDiscrepancy(proposal)" class="mt-2 p-2 bg-red-50 dark:bg-red-500/10 rounded border border-red-100 dark:border-red-500/20 flex items-start gap-2">
                                                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                        <p class="text-[10px] text-red-600 dark:text-red-400 font-medium">Atenção: Valores validados são diferentes do Valor Bruto da Venda.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Coluna 2: Formas de Pagamento -->
                                            <div class="space-y-3">
                                                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                    Plano de Pagamento
                                                </h4>
                                                
                                                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 max-h-48 overflow-y-auto">
                                                    <div v-if="proposal.payments && proposal.payments.length > 0" class="space-y-2">
                                                        <div v-for="pay in proposal.payments" :key="pay.id" class="flex justify-between items-center text-xs p-2 bg-slate-50 dark:bg-[#0f1219] rounded border border-slate-100 dark:border-slate-800">
                                                            <div class="flex flex-col">
                                                                <span class="font-bold text-slate-900 dark:text-white uppercase">{{ pay.payment_method }}</span>
                                                                <span class="text-[10px] text-slate-500">{{ pay.category === 'entrada' ? 'Entrada' : 'Parcelamento' }}</span>
                                                            </div>
                                                            <div class="flex flex-col items-end">
                                                                <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(pay.total_value) }}</span>
                                                                <span class="text-[10px] text-slate-500">{{ pay.installments }}x de {{ formatCurrency(pay.installment_value) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p v-else class="text-xs text-slate-500 text-center py-4">Nenhum pagamento cadastrado.</p>
                                                </div>
                                            </div>

                                            <!-- Coluna 3: Equipe -->
                                            <div class="space-y-3">
                                                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    Equipe Responsável
                                                </h4>
                                                
                                                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 space-y-3">
                                                    <!-- OPC -->
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                                                            {{ getInitials(proposal.sales_service?.opc_user?.name || 'OP') }}
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ proposal.sales_service?.opc_user?.name || 'Não informado' }}</span>
                                                            <span class="text-[10px] text-slate-500 uppercase tracking-widest">OPC</span>
                                                        </div>
                                                    </div>
                                                    <!-- LINER -->
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                                                            {{ getInitials(proposal.sales_service?.liner_user?.name || 'LI') }}
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ proposal.sales_service?.liner_user?.name || 'Não informado' }}</span>
                                                            <span class="text-[10px] text-slate-500 uppercase tracking-widest">Liner</span>
                                                        </div>
                                                    </div>
                                                    <!-- CLOSER -->
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                                                            {{ getInitials(proposal.sales_service?.closer_user?.name || 'CL') }}
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ proposal.sales_service?.closer_user?.name || 'Não informado' }}</span>
                                                            <span class="text-[10px] text-slate-500 uppercase tracking-widest">Closer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-white/[0.02] flex items-center justify-between">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Página {{ proposals.current_page }} de {{ proposals.last_page }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Link v-if="proposals.prev_page_url" :href="proposals.prev_page_url" class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </Link>
                        <span v-else class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </span>
                        
                        <Link v-if="proposals.next_page_url" :href="proposals.next_page_url" class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </Link>
                        <span v-else class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>