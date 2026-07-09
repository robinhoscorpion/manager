<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

import Modal from '@/Components/Modal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';


const viewMode = ref('list');
const expandedClients = ref([]);

const toggleClientExpanded = (clientId) => {
    if (expandedClients.value.includes(clientId)) {
        expandedClients.value = expandedClients.value.filter(id => id !== clientId);
    } else {
        expandedClients.value.push(clientId);
    }
};

const groupedReceivables = computed(() => {
    const groups = {};
    props.receivables.data.forEach(bill => {
        const clientId = bill.client?.id || 'unknown';
        if (!groups[clientId]) {
            groups[clientId] = {
                client: bill.client,
                total_amount: 0,
                bills: [],
                pendingCount: 0
            };
        }
        groups[clientId].bills.push(bill);
        groups[clientId].total_amount += (Number(bill.amount) + Number(bill.interest_amount || 0));
        if (bill.status !== 'paid' && bill.status !== 'cancelled') {
            groups[clientId].pendingCount++;
        }
    });
    return Object.values(groups);
});

const isClientFullySelected = (group) => {
    const pendings = group.bills.filter(b => b.status !== 'paid' && b.status !== 'cancelled');
    if (pendings.length === 0) return false;
    return pendings.every(b => selectedBills.value.includes(b.id));
};

const toggleClientSelection = (group, e) => {
    const pendings = group.bills.filter(b => b.status !== 'paid' && b.status !== 'cancelled').map(b => b.id);
    if (e.target.checked) {
        pendings.forEach(id => {
            if (!selectedBills.value.includes(id)) selectedBills.value.push(id);
        });
    } else {
        selectedBills.value = selectedBills.value.filter(id => !pendings.includes(id));
    }
};

// Original selectedBills logic...
const selectedBills = ref([]);
const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedBills.value = props.receivables.data.filter(b => b.status !== 'paid' && b.status !== 'cancelled').map(b => b.id);
    } else {
        selectedBills.value = [];
    }
};

const totalSelectedAmount = computed(() => {
    return props.receivables.data
        .filter(b => selectedBills.value.includes(b.id))
        .reduce((sum, b) => sum + Number(b.amount) + Number(b.interest_amount || 0), 0);
});

// Bulk Pay Mask Logic
const maskCurrency = (value) => {
    if (!value) return '0,00';
    let val = value.toString().replace(/\D/g, '');
    val = (val / 100).toFixed(2) + '';
    val = val.replace('.', ',');
    val = val.replace(/(\d)(\d{3})(\d{3}),/g, '$1.$2.$3,');
    val = val.replace(/(\d)(\d{3}),/g, '$1.$2,');
    return val;
};

const onBulkPayInput = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (!val) {
        bulkPayForm.global_paid_amount = 0;
        return;
    }
    bulkPayForm.global_paid_amount = (parseInt(val) / 100).toFixed(2);
};

// Bulk Pay
const showBulkPayModal = ref(false);
const bulkPayForm = useForm({
    bill_ids: [],
    mode: 'single',
    paid_at: new Date().toISOString().split('T')[0],
    payment_method: 'PIX',
    bill_dates: {},
    global_paid_amount: 0
});
const openBulkPay = () => {
    bulkPayForm.bill_ids = selectedBills.value;
    bulkPayForm.global_paid_amount = totalSelectedAmount.value.toFixed(2);
    showBulkPayModal.value = true;
};
const submitBulkPay = () => {
    bulkPayForm.post(route('finance.receivables.bulk-pay'), {
        preserveScroll: true,
        onSuccess: () => {
            showBulkPayModal.value = false;
            selectedBills.value = [];
        }
    });
};

// Pay Individual
const showPayModal = ref(false);
const activeBill = ref(null);
const payForm = useForm({
    due_date: '',
    paid_at: new Date().toISOString().split('T')[0],
    amount: '',
    interest_amount: 0,
    paid_amount: '',
    payment_method: 'PIX',
    status: 'paid',
    observations: ''
});

const openPayModal = (bill) => {
    activeBill.value = bill;
    payForm.due_date = bill.due_date.split('T')[0];
    payForm.amount = bill.amount;
    payForm.paid_amount = Number(bill.amount) + Number(bill.interest_amount || 0);
    showPayModal.value = true;
};

watch(() => payForm.paid_amount, (newVal) => {
    const paid = Number(newVal) || 0;
    const amount = Number(payForm.amount) || 0;
    if (paid > amount) {
        payForm.interest_amount = (paid - amount).toFixed(2);
    } else {
        payForm.interest_amount = 0;
    }
});

const submitPay = () => {
    payForm.put(route('bills.update', activeBill.value.id), {
        preserveScroll: true,
        onSuccess: () => showPayModal.value = false
    });
};

// Renegotiate
const showRenegotiateModal = ref(false);
const renegotiateForm = useForm({
    original_bill_id: '',
    new_installments: 1,
    new_total_amount: '',
    first_due_date: '',
    payment_method: 'PIX',
    add_interest: false,
    interest_amount: 0
});
const openRenegotiateModal = (bill) => {
    activeBill.value = bill;
    renegotiateForm.original_bill_id = bill.id;
    renegotiateForm.new_total_amount = bill.amount;
    showRenegotiateModal.value = true;
};
const submitRenegotiate = () => {
    renegotiateForm.post(route('bills.renegotiate', activeBill.value.proposal_id), {
        preserveScroll: true,
        onSuccess: () => showRenegotiateModal.value = false
    });
};

// Cancel
const showCancelModal = ref(false);
const cancelForm = useForm({
    due_date: '',
    amount: '',
    payment_method: '',
    status: 'cancelled',
});
const openCancelModal = (bill) => {
    activeBill.value = bill;
    cancelForm.due_date = bill.due_date.split('T')[0];
    cancelForm.amount = bill.amount;
    cancelForm.payment_method = bill.payment_method;
    showCancelModal.value = true;
};
const submitCancel = () => {
    cancelForm.put(route('bills.update', activeBill.value.id), {
        preserveScroll: true,
        onSuccess: () => showCancelModal.value = false
    });
};


const props = defineProps({
    receivables: Object,
    filters: Object,
    kpis: Object
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

import { useForm } from '@inertiajs/vue3';

const showFilters = ref(false);
const today = new Date().toISOString().split('T')[0];
const advancedFilters = useForm({
    due_date_start: props.filters.due_date_start !== undefined ? props.filters.due_date_start : today,
    due_date_end: props.filters.due_date_end !== undefined ? props.filters.due_date_end : today,
    paid_at_start: props.filters.paid_at_start || '',
    paid_at_end: props.filters.paid_at_end || '',
    payment_method: props.filters.payment_method || '',
    min_amount: props.filters.min_amount || '',
    max_amount: props.filters.max_amount || '',
    sales_service_id: props.filters.sales_service_id || ''
});

const applyFilters = () => {
    advancedFilters.get(route('finance.receivables.index'), {
        preserveState: true,
        replace: true,
        data: { search: search.value, status: statusFilter.value }
    });
    showFilters.value = false;
};


const setDateRange = (range) => {
    const t = new Date();
    if (range === 'hoje') {
        advancedFilters.due_date_start = t.toISOString().split('T')[0];
        advancedFilters.due_date_end = t.toISOString().split('T')[0];
    } else if (range === '7dias') {
        const past = new Date();
        past.setDate(t.getDate() - 7);
        advancedFilters.due_date_start = past.toISOString().split('T')[0];
        advancedFilters.due_date_end = t.toISOString().split('T')[0];
    } else if (range === 'mes') {
        const firstDay = new Date(t.getFullYear(), t.getMonth(), 1);
        const lastDay = new Date(t.getFullYear(), t.getMonth() + 1, 0);
        advancedFilters.due_date_start = firstDay.toISOString().split('T')[0];
        advancedFilters.due_date_end = lastDay.toISOString().split('T')[0];
    }
};

const clearFilters = () => {
    advancedFilters.reset();
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return Object.values(advancedFilters.data()).some(val => val !== '');
});


const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString + 'T00:00:00'); // Trata como UTC-3 local
    return new Intl.DateTimeFormat('pt-BR').format(date);
};

watch(search, debounce(function (value) {
    router.get(route('finance.receivables.index'), { search: value, status: statusFilter.value }, { preserveState: true, replace: true });
}, 300));

watch(statusFilter, (value) => {
    router.get(route('finance.receivables.index'), { search: search.value, status: value }, { preserveState: true, replace: true });
});

const getStatusBadge = (status) => {
    const config = {
        pending: { label: 'A Receber', color: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20' },
        paid: { label: 'Pago', color: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
        overdue: { label: 'Atrasado', color: 'bg-red-500/10 text-red-500 border-red-500/20' },
        cancelled: { label: 'Cancelado', color: 'bg-red-500/10 text-red-500 border-red-500/20' }
    };
    return config[status] || { label: 'Desconhecido', color: 'bg-slate-500/10 text-slate-500 border-slate-500/20' };
};
</script>

<template>
    <Head title="Recebíveis" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-3">
                        <div class="p-2 bg-brand-green/20 rounded-lg">
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        Gestão de Recebíveis
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Acompanhamento geral de todas as parcelas de clientes</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-[#0f1219] p-6 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-brand-green/30 transition-colors">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-brand-green/5 rounded-bl-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="p-3 bg-brand-green/10 text-brand-green rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recebido (Mês Atual)</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ formatCurrency(kpis.total_paid) }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#0f1219] p-6 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-yellow-500/30 transition-colors">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/5 rounded-bl-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="p-3 bg-yellow-500/10 text-yellow-500 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">A Receber (Mês Atual)</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ formatCurrency(kpis.total_pending) }}</h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#0f1219] p-6 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-red-500/30 transition-colors">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-bl-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="p-3 bg-red-500/10 text-red-500 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Atrasados (Geral)</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ formatCurrency(kpis.total_overdue) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela e Filtros -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    
                    <div class="relative w-full flex-1 max-w-3xl flex gap-2">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Buscar por cliente ou descrição..." class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-brand-green/20 placeholder-slate-400">
                        </div>
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-lg shrink-0">
                            <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all uppercase tracking-widest">Lista</button>
                            <button @click="viewMode = 'grouped'" :class="viewMode === 'grouped' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all uppercase tracking-widest">Clientes</button>
                        </div>
                        <button @click="showFilters = !showFilters" :class="hasActiveFilters ? 'bg-brand-green text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filtros <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full bg-white ml-1"></span>
                        </button>
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0 hide-scrollbar">
                        <button @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Todos
                        </button>
                        <button @click="statusFilter = 'pending'" :class="statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            A Receber
                        </button>
                        <button @click="statusFilter = 'paid'" :class="statusFilter === 'paid' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Pagos
                        </button>
                        <button @click="statusFilter = 'overdue'" :class="statusFilter === 'overdue' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Atrasados
                        </button>
                    </div>
                </div>

                
                
                <!-- Modal de Filtros Profissional (Slide-Over) -->
                <Teleport to="body">
                    <div v-show="showFilters" class="fixed inset-0 z-[100] flex justify-end">
                        <div @click="showFilters = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
                        <div class="relative w-full max-w-md bg-white dark:bg-[#0f1219] h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800 animate-in slide-in-from-right duration-300">
                            
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                    Filtros Avançados
                                </h3>
                                <button @click="showFilters = false" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-6 hide-scrollbar">
                                <!-- Botões Rápidos -->
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Períodos Rápidos (Vencimento)</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button @click="setDateRange('hoje')" type="button" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-brand-green/10 hover:text-brand-green text-slate-600 dark:text-slate-300 rounded-lg text-xs font-bold transition-colors">Hoje</button>
                                        <button @click="setDateRange('7dias')" type="button" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-brand-green/10 hover:text-brand-green text-slate-600 dark:text-slate-300 rounded-lg text-xs font-bold transition-colors">Últimos 7 dias</button>
                                        <button @click="setDateRange('mes')" type="button" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-brand-green/10 hover:text-brand-green text-slate-600 dark:text-slate-300 rounded-lg text-xs font-bold transition-colors">Este Mês</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vencimento Início</label>
                                        <input v-model="advancedFilters.due_date_start" type="date" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vencimento Fim</label>
                                        <input v-model="advancedFilters.due_date_end" type="date" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pagamento Início</label>
                                        <input v-model="advancedFilters.paid_at_start" type="date" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pagamento Fim</label>
                                        <input v-model="advancedFilters.paid_at_end" type="date" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Forma de Pagamento</label>
                                    <select v-model="advancedFilters.payment_method" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                        <option value="">Qualquer Forma</option>
                                        <option value="PIX">PIX</option>
                                        <option value="Boleto">Boleto</option>
                                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                                        <option value="Cartão de Débito">Cartão de Débito</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Transferência">Transferência</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Mín (R$)</label>
                                        <input v-model="advancedFilters.min_amount" type="number" step="0.01" placeholder="0,00" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Máx (R$)</label>
                                        <input v-model="advancedFilters.max_amount" type="number" step="0.01" placeholder="0,00" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                    </div>
                                </div>
                                
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">ID do Contrato</label>
                                    <input v-model="advancedFilters.sales_service_id" type="number" placeholder="Ex: 30" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                </div>
                            </div>

                            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-between gap-3 bg-slate-50/50 dark:bg-white/[0.02]">
                                <button @click="clearFilters" class="px-4 py-3 w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                    Limpar
                                </button>
                                <button @click="applyFilters" class="px-4 py-3 w-full bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-colors shadow-sm">
                                    Aplicar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
                
<div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-brand-green focus:ring-brand-green bg-white dark:bg-slate-900" @change="toggleSelectAll" :checked="selectedBills.length === receivables.data.filter(b => b.status !== 'paid' && b.status !== 'cancelled').length && receivables.data.length > 0">
                                </th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cliente</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Descrição</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Vencimento</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Forma</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Valor Bruto</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Juros/Mora</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Pago</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Ações</th>
                            </tr>
                        </thead>
                        
                        <tbody v-if="viewMode === 'list'" class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            
                            <tr v-for="bill in receivables.data" :key="bill.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 text-center">
                                    <input v-if="bill.status !== 'paid' && bill.status !== 'cancelled'" type="checkbox" :value="bill.id" v-model="selectedBills" class="rounded border-slate-300 text-brand-green focus:ring-brand-green bg-white dark:bg-slate-900">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-xs">
                                            {{ bill.client?.nome?.charAt(0) || 'C' }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ bill.client?.nome || 'Cliente Removido' }}</p>
                                            <p class="text-[10px] text-slate-500">{{ bill.client?.cpf || 'Sem documento' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ bill.description }}</span>
                                        <span v-if="bill.installment_number" class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-md text-slate-500">{{ bill.installment_number }}/{{ bill.total_installments }}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Serviço #{{ bill.sales_service_id || '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatDate(bill.due_date) }}</span>
                                        <span v-if="bill.paid_at" class="text-[10px] text-emerald-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Pago em {{ formatDate(bill.paid_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg whitespace-nowrap">{{ bill.payment_method || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-900 dark:text-white">{{ formatCurrency(bill.amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-red-500">{{ formatCurrency(bill.interest_amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-emerald-500">{{ bill.paid_amount ? formatCurrency(bill.paid_amount) : '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="getStatusBadge(bill.status).color" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border text-[10px] font-bold uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(bill.status).color.replace('bg-', 'bg-opacity-100 bg-').split(' ')[0]"></span>
                                        {{ getStatusBadge(bill.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link v-if="bill.sales_service_id" :href="route('sales.atendimentos.show', bill.sales_service_id)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-brand-green hover:bg-brand-green/10 transition-colors" title="Ver Contrato">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <Dropdown align="right" width="48" v-if="bill.status !== 'paid' && bill.status !== 'cancelled'">
                                            <template #trigger>
                                                <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <button @click="openPayModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Baixar Parcela</button>
                                                <button @click="openRenegotiateModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Renegociar</button>
                                                <button @click="openCancelModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-red-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Cancelar</button>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>
                            
                        
                            <tr v-if="receivables.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                    Nenhuma parcela encontrada para os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
                        
                        <tbody v-else-if="viewMode === 'grouped'" class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <template v-for="group in groupedReceivables" :key="group.client?.id || 'unknown'">
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors bg-slate-50/50 dark:bg-slate-800/30 cursor-pointer" @click="toggleClientExpanded(group.client?.id || 'unknown')">
                                    <td class="px-6 py-4 text-center" @click.stop>
                                        <input v-if="group.pendingCount > 0" type="checkbox" :checked="isClientFullySelected(group)" @change="toggleClientSelection(group, $event)" class="rounded border-slate-300 text-brand-green focus:ring-brand-green bg-white dark:bg-slate-900">
                                    </td>
                                    <td class="px-6 py-4" colspan="2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-brand-green/20 text-brand-green flex items-center justify-center font-black text-sm shadow-sm">
                                                {{ group.client?.nome?.charAt(0) || 'C' }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ group.client?.nome || 'Cliente Removido' }}</p>
                                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">{{ group.bills.length }} faturas / {{ group.pendingCount }} em aberto</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4" colspan="3">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-0.5">Total Consolidado</span>
                                            <span class="text-base font-black text-slate-900 dark:text-white">{{ formatCurrency(group.total_amount) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right" colspan="4">
                                        <button class="p-2 text-slate-400 hover:text-brand-green transition-colors">
                                            <svg :class="expandedClients.includes(group.client?.id || 'unknown') ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                                
                                <template v-if="expandedClients.includes(group.client?.id || 'unknown')">
                                    
                            <tr v-for="bill in group.bills" :key="bill.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 text-center">
                                    <input v-if="bill.status !== 'paid' && bill.status !== 'cancelled'" type="checkbox" :value="bill.id" v-model="selectedBills" class="rounded border-slate-300 text-brand-green focus:ring-brand-green bg-white dark:bg-slate-900">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-xs">
                                            {{ bill.client?.nome?.charAt(0) || 'C' }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ bill.client?.nome || 'Cliente Removido' }}</p>
                                            <p class="text-[10px] text-slate-500">{{ bill.client?.cpf || 'Sem documento' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ bill.description }}</span>
                                        <span v-if="bill.installment_number" class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-md text-slate-500">{{ bill.installment_number }}/{{ bill.total_installments }}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Serviço #{{ bill.sales_service_id || '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatDate(bill.due_date) }}</span>
                                        <span v-if="bill.paid_at" class="text-[10px] text-emerald-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Pago em {{ formatDate(bill.paid_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg whitespace-nowrap">{{ bill.payment_method || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-900 dark:text-white">{{ formatCurrency(bill.amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-red-500">{{ formatCurrency(bill.interest_amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-emerald-500">{{ bill.paid_amount ? formatCurrency(bill.paid_amount) : '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="getStatusBadge(bill.status).color" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border text-[10px] font-bold uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(bill.status).color.replace('bg-', 'bg-opacity-100 bg-').split(' ')[0]"></span>
                                        {{ getStatusBadge(bill.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link v-if="bill.sales_service_id" :href="route('sales.atendimentos.show', bill.sales_service_id)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-brand-green hover:bg-brand-green/10 transition-colors" title="Ver Contrato">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <Dropdown align="right" width="48" v-if="bill.status !== 'paid' && bill.status !== 'cancelled'">
                                            <template #trigger>
                                                <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <button @click="openPayModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Baixar Parcela</button>
                                                <button @click="openRenegotiateModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Renegociar</button>
                                                <button @click="openCancelModal(bill)" class="block w-full text-left px-4 py-2 text-sm leading-5 text-red-600 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out font-medium">Cancelar</button>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>
                            
                        
                                </template>
                            </template>
                            <tr v-if="groupedReceivables.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                    Nenhuma parcela encontrada para os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
    
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-white/[0.02]">
                    <Pagination :links="receivables.links" />
                </div>
            </div>
        </div>

        <!-- Floating Bulk Actions Bar -->
        <div v-show="selectedBills.length > 0" class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-[#0f1219] border-t border-slate-200 dark:border-slate-800 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transform transition-transform duration-300" :class="selectedBills.length > 0 ? 'translate-y-0' : 'translate-y-full'">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-green/10 text-brand-green font-bold text-sm">{{ selectedBills.length }}</span>
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Parcelas Selecionadas</p>
                        <p class="text-xs text-slate-500">Total: <span class="font-bold text-emerald-500">{{ formatCurrency(totalSelectedAmount) }}</span></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="selectedBills = []" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">Cancelar</button>
                    <button @click="openBulkPay" class="px-6 py-2 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Baixar Lote
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk Pay Modal -->
        <Modal :show="showBulkPayModal" @close="showBulkPayModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4 uppercase tracking-widest flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    Baixa em Lote
                </h2>
                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-600 dark:text-slate-400">Você está prestes a liquidar <strong>{{ selectedBills.length }}</strong> parcelas.</p>
                        <div class="mt-2 relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-lg font-black text-emerald-500">R$</span>
                            <input :value="maskCurrency(bulkPayForm.global_paid_amount)" @input="onBulkPayInput" type="text" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-700 rounded-xl text-emerald-500 font-black text-lg pl-10 focus:ring-brand-green/20">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data do Pagamento Global</label>
                        <input v-model="bulkPayForm.paid_at" type="date" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl focus:ring-brand-green/20">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Forma de Pagamento</label>
                        <select v-model="bulkPayForm.payment_method" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl focus:ring-brand-green/20">
                            <option value="PIX">PIX</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Transferência">Transferência</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showBulkPayModal = false" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Cancelar</button>
                    <button @click="submitBulkPay" class="px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-sm">Confirmar Pagamento</button>
                </div>
            </div>
        </Modal>

        <!-- Pay Individual Modal -->
        <Modal :show="showPayModal" @close="showPayModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4 uppercase tracking-widest flex items-center gap-2">Baixar Parcela</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data do Pagamento</label>
                        <input v-model="payForm.paid_at" type="date" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Valor Parcela</label>
                            <input v-model="payForm.amount" type="number" step="0.01" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Juros/Mora</label>
                            <input v-model="payForm.interest_amount" type="number" step="0.01" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl text-red-500 font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Recebido</label>
                        <input v-model="payForm.paid_amount" type="number" step="0.01" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl text-emerald-500 font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Forma de Pagamento</label>
                        <select v-model="payForm.payment_method" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl">
                            <option value="PIX">PIX</option><option value="Boleto">Boleto</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showPayModal = false" class="px-4 py-2 text-sm font-bold text-slate-500">Cancelar</button>
                    <button @click="submitPay" class="px-6 py-2 bg-emerald-500 text-white rounded-xl font-bold">Baixar</button>
                </div>
            </div>
        </Modal>

        <!-- Cancel Modal -->
        <Modal :show="showCancelModal" @close="showCancelModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-black text-red-500 mb-4 uppercase tracking-widest flex items-center gap-2">Cancelar Parcela</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Tem certeza que deseja cancelar esta parcela? Essa ação removerá o valor das previsões de receita.</p>
                <div class="flex justify-end gap-3">
                    <button @click="showCancelModal = false" class="px-4 py-2 text-sm font-bold text-slate-500">Voltar</button>
                    <button @click="submitCancel" class="px-6 py-2 bg-red-500 text-white rounded-xl font-bold">Sim, Cancelar</button>
                </div>
            </div>
        </Modal>

        <!-- Renegotiate Modal -->
        <Modal :show="showRenegotiateModal" @close="showRenegotiateModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-black text-blue-500 mb-4 uppercase tracking-widest flex items-center gap-2">Renegociar Parcela</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Dividir em quantas vezes?</label>
                        <input v-model="renegotiateForm.new_installments" type="number" min="1" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Novo Vencimento Inicial</label>
                        <input v-model="renegotiateForm.first_due_date" type="date" class="w-full bg-white dark:bg-[#0f1219] border-slate-200 dark:border-slate-800 rounded-xl">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showRenegotiateModal = false" class="px-4 py-2 text-sm font-bold text-slate-500">Cancelar</button>
                    <button @click="submitRenegotiate" class="px-6 py-2 bg-blue-500 text-white rounded-xl font-bold">Gerar Novas Parcelas</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
