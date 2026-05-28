<script setup>
import { computed, ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    service: Object
});

const client = computed(() => props.service.client);
const proposal = computed(() => props.service.proposal);
const product = computed(() => proposal.value?.product);
const payments = computed(() => proposal.value?.payments || []);
const bills = computed(() => proposal.value?.bills || []);

// --- Payment Methods Logic ---
const paymentMethods = ref([]);
const fetchPaymentMethods = async () => {
    try {
        const response = await fetch(route('api.payment-methods'));
        paymentMethods.value = await response.json();
    } catch (error) {
        console.error('Erro ao buscar formas de pagamento:', error);
    }
};

onMounted(() => {
    fetchPaymentMethods();
});

// --- View Installment Logic ---
const isViewModalOpen = ref(false);
const viewBill = ref(null);

const openViewModal = (bill) => {
    viewBill.value = bill;
    isViewModalOpen.value = true;
};

const closeViewModal = () => {
    isViewModalOpen.value = false;
    viewBill.value = null;
};

// --- Create Installment Logic ---
const isCreateModalOpen = ref(false);
const createForm = useForm({
    category: 'saldo',
    due_date: new Date().toISOString().split('T')[0],
    paid_at: '',
    amount: 0,
    interest_amount: 0,
    paid_amount: 0,
    payment_method: 'PIX',
    status: 'pending',
    observations: '',
});

const openCreateModal = () => {
    createForm.reset();
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

// --- Renegotiation Logic ---
const selectedBills = ref([]);
const isRenegotiateModalOpen = ref(false);
const renegotiateTotal = ref(0);
const renegotiateForm = useForm({
    bill_ids: [],
    due_date: new Date().toISOString().split('T')[0],
    payment_method: 'PIX',
});

const openRenegotiateModal = () => {
    renegotiateForm.bill_ids = selectedBills.value;
    renegotiateForm.due_date = new Date().toISOString().split('T')[0];
    renegotiateForm.payment_method = 'PIX';
    
    // Calculate total open amount of selected bills
    let total = 0;
    bills.value.forEach(b => {
        if (selectedBills.value.includes(b.id)) {
            total += (parseFloat(b.amount) + parseFloat(b.interest_amount || 0) - parseFloat(b.paid_amount || 0));
        }
    });
    renegotiateTotal.value = total;
    
    isRenegotiateModalOpen.value = true;
};

const closeRenegotiateModal = () => {
    isRenegotiateModalOpen.value = false;
    renegotiateForm.reset();
};

const submitRenegotiate = () => {
    renegotiateForm.post(route('bills.renegotiate', proposal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedBills.value = [];
            closeRenegotiateModal();
        }
    });
};

// --- Bulk Pay Logic ---
const isBulkPayModalOpen = ref(false);
const bulkPayTotal = ref(0);
const bulkPayForm = useForm({
    bill_ids: [],
    paid_at: new Date().toISOString().split('T')[0],
    payment_method: 'PIX',
    observations: '',
    mode: 'single', // 'single' or 'individual'
    bill_dates: {},
});

const openBulkPayModal = () => {
    bulkPayForm.bill_ids = selectedBills.value;
    bulkPayForm.paid_at = new Date().toISOString().split('T')[0];
    bulkPayForm.payment_method = 'PIX';
    bulkPayForm.mode = 'single';
    bulkPayForm.bill_dates = {};
    
    let total = 0;
    bills.value.forEach(b => {
        if (selectedBills.value.includes(b.id)) {
            total += (parseFloat(b.amount) + parseFloat(b.interest_amount || 0) - parseFloat(b.paid_amount || 0));
            bulkPayForm.bill_dates[b.id] = new Date().toISOString().split('T')[0];
        }
    });
    bulkPayTotal.value = total;
    isBulkPayModalOpen.value = true;
};

const closeBulkPayModal = () => {
    isBulkPayModalOpen.value = false;
    bulkPayForm.reset();
};

const submitBulkPay = () => {
    bulkPayForm.post(route('bills.bulk-pay', proposal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedBills.value = [];
            closeBulkPayModal();
        }
    });
};

const submitCreate = () => {
    createForm.post(route('bills.store', proposal.value.id), {
        onSuccess: () => closeCreateModal(),
        preserveScroll: true,
    });
};

// --- Edit Installment Logic ---
const isEditModalOpen = ref(false);
const editingBill = ref(null);
const editForm = useForm({
    id: null,
    due_date: '',
    paid_at: '',
    amount: 0,
    interest_amount: 0,
    paid_amount: 0,
    payment_method: '',
    status: 'pending',
    observations: '',
});

const openEditModal = (bill) => {
    editingBill.value = bill;
    editForm.id = bill.id;
    editForm.due_date = bill.due_date;
    editForm.paid_at = bill.paid_at || '';
    editForm.amount = bill.amount;
    editForm.interest_amount = bill.interest_amount || 0;
    editForm.paid_amount = bill.paid_amount || 0;
    editForm.payment_method = bill.payment_method;
    editForm.status = bill.status;
    editForm.observations = bill.observations || '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route('bills.update', editForm.id), {
        onSuccess: () => closeEditModal(),
        preserveScroll: true,
    });
};

// --- Premium Delete Modal Logic ---
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);
const billToDelete = ref(null);

const deleteBill = (bill) => {
    billToDelete.value = bill;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => { billToDelete.value = null; }, 300);
};

const executeDelete = () => {
    if (!billToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('bills.destroy', billToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleting.value = false;
            closeDeleteModal();
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const parseDateText = (dateStr) => {
    if (!dateStr) return null;
    if (typeof dateStr === 'string' && dateStr.includes('/')) {
        const [day, month, year] = dateStr.split('/');
        return new Date(`${year}-${month}-${day}T00:00:00`);
    }
    return new Date(dateStr);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const date = parseDateText(dateStr);
        if (!date || isNaN(date.getTime())) return dateStr;
        return new Intl.DateTimeFormat('pt-BR').format(date);
    } catch (e) {
        return dateStr;
    }
};

const formatCurrencyInput = (value) => {
    if (value === null || value === undefined || value === '') return '';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(value) || 0);
};

const updateCurrencyInput = (e, formObj, field) => {
    let val = e.target.value.replace(/\D/g, '');
    let numericValue = val ? parseInt(val) / 100 : 0;
    formObj[field] = numericValue;
    e.target.value = formatCurrencyInput(numericValue);
};

const printDocument = (route_name) => {
    // @ts-ignore
    window.open(route(route_name, props.service.id), '_blank');
};

// --- Financial Logic ---
const totalValue = computed(() => proposal.value?.total_value || 0);
const totalPaid = computed(() => {
    return bills.value
        .filter(b => ['entrada', 'saldo'].includes(b.category) && b.status === 'paid')
        .reduce((acc, b) => acc + parseFloat(b.paid_amount || b.amount), 0);
});
const percentPaid = computed(() => totalValue.value > 0 ? ((totalPaid.value / totalValue.value) * 100).toFixed(2) : '0.00');
const amountOpen = computed(() => Math.max(0, totalValue.value - totalPaid.value));

// --- Points Metrics ---
const totalPoints = computed(() => proposal.value?.quantity || 0);
const valuePerPoint = computed(() => totalPoints.value > 0 ? totalValue.value / totalPoints.value : 0);

// Pontos liberados proporcionalmente ao valor pago (considerando apenas entrada e saldo)
const releasedPoints = computed(() => {
    if (totalValue.value <= 0) return 0;
    const ratio = totalPaid.value / totalValue.value;
    return Math.floor(totalPoints.value * ratio);
});

const utilizedPoints = computed(() => proposal.value?.used_points || 0);
const availablePoints = computed(() => Math.max(0, releasedPoints.value - utilizedPoints.value));

// --- Duration & Usage Metrics ---
const contractDateObj = computed(() => parseDateText(props.service.date));
const startYear = computed(() => contractDateObj.value ? contractDateObj.value.getFullYear() : '-');
const durationYearsRaw = computed(() => {
    if (!product.value?.duration) return 0;
    const match = String(product.value.duration).match(/\d+/);
    return match ? parseInt(match[0]) : 0;
});
const usableUntilStr = computed(() => {
    if (!contractDateObj.value || !durationYearsRaw.value) return '-';
    const d = new Date(contractDateObj.value);
    d.setFullYear(d.getFullYear() + durationYearsRaw.value);
    return new Intl.DateTimeFormat('pt-BR').format(d);
});
const yearsRemainingVal = computed(() => {
    if (!contractDateObj.value || !durationYearsRaw.value) return 0;
    const end = new Date(contractDateObj.value);
    end.setFullYear(end.getFullYear() + durationYearsRaw.value);
    const today = new Date();
    const diff = end.getFullYear() - today.getFullYear();
    return diff > 0 ? diff : 0;
});

// --- Iteration 5: Grouped Bills Logic ---
const categories = {
    'taxa_contrato': 'Taxa de Contrato',
    'entrada': 'Entrada',
    'saldo': 'Parcelas do Saldo',
    'taxa_manutencao': 'Taxa de Manutenção',
};

const groupedBills = computed(() => {
    const groups = {};
    bills.value.forEach(bill => {
        if (!groups[bill.category]) groups[bill.category] = [];
        groups[bill.category].push(bill);
    });

    const order = ['taxa_contrato', 'entrada', 'saldo'];
    
    return Object.keys(groups)
        .sort((a, b) => {
            if (a === 'taxa_manutencao') return 1;
            if (b === 'taxa_manutencao') return -1;
            const indexA = order.indexOf(a);
            const indexB = order.indexOf(b);
            if (indexA !== -1 && indexB !== -1) return indexA - indexB;
            if (indexA !== -1) return -1;
            if (indexB !== -1) return 1;
            return a.localeCompare(b);
        })
        .map(key => ({
            key,
            label: categories[key] || 'Pagamento',
            items: groups[key].sort((a, b) => {
                const dateA = new Date(a.due_date).getTime();
                const dateB = new Date(b.due_date).getTime();
                if (dateA !== dateB) return dateA - dateB;
                return a.installment_number - b.installment_number;
            })
        }));
});

const getStatusColor = (status) => {
    switch (status) {
        case 'paid': return 'bg-green-500';
        case 'pending': return 'bg-blue-500';
        case 'overdue': return 'bg-yellow-500';
        case 'cancelled': return 'bg-red-500';
        default: return 'bg-gray-500';
    }
};

</script>

<template>
    <Head :title="'Contrato #' + (proposal?.contract_number || service.id)" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6 animate-in fade-in duration-500">
            
            <!-- Professional Header -->
            <div class="bg-white dark:bg-white/[0.02] border border-slate-200 dark:border-white/10 rounded-2xl p-6 relative overflow-hidden backdrop-blur-md shadow-sm dark:shadow-none">
                <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                    <svg class="w-32 h-32 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                </div>

                <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <nav class="flex items-center gap-2 text-[9px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">
                            <Link :href="route('sales.atendimentos')" class="hover:text-indigo-600 dark:hover:text-cyan-400">Sala de Vendas</Link>
                            <span class="text-slate-300 dark:text-white/20">/</span>
                            <span class="text-slate-400 dark:text-white/40">Contrato #{{ proposal?.contract_number || 'S/N' }}</span>
                        </nav>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                            {{ client?.nome }}
                        </h1>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded-md text-[8px] font-black uppercase tracking-widest">
                                Status: {{ proposal?.contract_number ? 'Ativo' : 'Pendente' }}
                            </span>
                            <span class="text-[9px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest border-l border-slate-200 dark:border-white/10 pl-2">
                                {{ formatDate(service.date) }} às {{ service.time }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Hub -->
                    <div class="flex flex-wrap gap-2">
                        <button @click="printDocument('sales.atendimentos.contrato.pdf')" 
                                class="flex items-center gap-2 bg-slate-900 dark:bg-white text-white dark:text-black hover:bg-indigo-600 dark:hover:bg-cyan-400 px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all shadow-lg">
                            <span>📄</span> Contrato
                        </button>
                        <button @click="printDocument('sales.atendimentos.proposta.pdf')" 
                                class="flex items-center gap-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                            <span>💰</span> Proposta
                        </button>
                        <button @click="printDocument('sales.atendimentos.checklist.pdf')" 
                                class="flex items-center gap-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                            <span>✅</span> Checklist
                        </button>
                        <button @click="printDocument('sales.atendimentos.pdf')" 
                                class="flex items-center gap-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                            <span>📋</span> Ficha
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section 1: Vigência -->
            <div class="space-y-3">
                <div class="flex items-center gap-3 px-1">
                    <span class="text-indigo-500 font-black text-xs">#</span>
                    <h3 class="text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-[0.2em]">Vigência do Contrato</h3>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div v-for="(val, label) in { 'Duração': {v: product?.duration || 'S/N', i: '⏳'}, 'Expira': {v: usableUntilStr, i: '📅'}, 'Restam': {v: yearsRemainingVal + ' Anos', i: '⏳'} }" :key="label" class="flex-1 min-w-[160px] bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/5 rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all hover:bg-slate-50 dark:hover:bg-white/10 shadow-sm dark:shadow-none">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
                        <span class="text-xs opacity-50 mb-2">{{ val.i }}</span>
                        <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">{{ label }}</p>
                        <p class="text-sm font-black text-slate-900 dark:text-white uppercase font-mono">{{ val.v }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Financeiro -->
            <div class="space-y-3 pt-4">
                <div class="flex items-center gap-3 px-1">
                    <span class="text-emerald-500 font-black text-xs">#</span>
                    <h3 class="text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-[0.2em]">Status Financeiro</h3>
                </div>
                <div class="flex flex-wrap gap-4">
                    <!-- Valor Contrato -->
                    <div class="flex-1 min-w-[180px] bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/5 rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all hover:bg-slate-50 dark:hover:bg-white/10 shadow-sm dark:shadow-none">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
                        <span class="text-xs opacity-50 mb-2">💰</span>
                        <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">Valor Contrato</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ formatCurrency(totalValue) }}</p>
                    </div>

                    <!-- Valor Pago -->
                    <div class="flex-1 min-w-[180px] bg-emerald-500/[0.02] dark:bg-emerald-500/[0.02] border border-emerald-200 dark:border-emerald-500/20 rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all hover:bg-emerald-500/[0.05] shadow-sm dark:shadow-none">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
                        <span class="text-xs opacity-60 mb-2 text-emerald-500">✅</span>
                        <p class="text-[8px] font-black text-emerald-700 dark:text-emerald-500 uppercase tracking-widest leading-none mb-1.5">Valor Pago</p>
                        <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ formatCurrency(totalPaid) }}</p>
                        <div class="w-full mt-3 h-1 bg-slate-200 dark:bg-white/10 rounded-full overflow-hidden">
                            <div :style="{ width: percentPaid + '%' }" class="h-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                        </div>
                    </div>

                    <!-- % Pago -->
                    <div class="flex-1 min-w-[120px] bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/5 rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all hover:bg-slate-50 dark:hover:bg-white/10 shadow-sm dark:shadow-none">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-400"></div>
                        <span class="text-xs opacity-50 mb-2">📈</span>
                        <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">% Pago</p>
                        <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ percentPaid }}%</p>
                    </div>

                    <!-- Saldo Aberto -->
                    <div class="flex-1 min-w-[180px] bg-orange-500/[0.02] dark:bg-orange-500/[0.02] border border-orange-200 dark:border-orange-500/20 rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all hover:bg-orange-500/[0.05] shadow-sm dark:shadow-none">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-500"></div>
                        <span class="text-xs opacity-60 mb-2 text-orange-500">⚠️</span>
                        <p class="text-[8px] font-black text-orange-700 dark:text-orange-500 uppercase tracking-widest leading-none mb-1.5">Saldo em Aberto</p>
                        <p class="text-lg font-black text-orange-600 dark:text-orange-400 font-mono">{{ formatCurrency(amountOpen) }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pontuação -->
            <div class="space-y-3 pt-4">
                <div class="flex items-center gap-3 px-1">
                    <span class="text-amber-500 font-black text-xs">#</span>
                    <h3 class="text-[10px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-[0.2em]">Controle de Pontos</h3>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div v-for="(val, label) in { 'Pontuação': {v: totalPoints.toLocaleString() + ' pts', i: '⭐️', c: 'amber'}, 'Disponível': {v: availablePoints.toLocaleString() + ' pts', i: '🔓', c: 'amber'}, 'Utilizado': {v: utilizedPoints.toLocaleString() + ' pts', i: '🏷️', c: 'slate'} }" :key="label" :class="label === 'Disponível' ? 'bg-amber-500/[0.03] border-amber-200 dark:border-amber-500/20' : 'bg-white dark:bg-white/[0.03] border-slate-200 dark:border-white/5'" class="flex-1 min-w-[160px] border rounded-2xl p-4 flex flex-col items-center text-center relative overflow-hidden group hover:scale-[1.02] transition-all shadow-sm dark:shadow-none">
                        <div :class="label === 'Disponível' ? 'bg-amber-500' : (label === 'Utilizado' ? 'bg-slate-400' : 'bg-amber-500/50')" class="absolute top-0 left-0 w-1.5 h-full"></div>
                        <span class="text-xs opacity-50 mb-2">{{ val.i }}</span>
                        <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">{{ label }}</p>
                        <p :class="label === 'Disponível' ? 'text-amber-600 dark:text-amber-500' : 'text-slate-900 dark:text-white'" class="text-base font-black uppercase">{{ val.v }}</p>
                    </div>
                </div>
            </div>

            <!-- Installments Section -->
            <div class="bg-white dark:bg-white/[0.02] border border-slate-200 dark:border-white/5 rounded-2xl shadow-sm dark:shadow-none">
                <div class="px-6 py-4 bg-slate-50 dark:bg-white/[0.01] border-b border-slate-100 dark:border-white/5 flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div v-for="(color, lbl) in { 'A Receber': 'bg-blue-500', 'Baixado': 'bg-green-500', 'Inadimplente': 'bg-yellow-500', 'Cancelado': 'bg-red-500' }" :key="lbl" class="flex items-center gap-2">
                             <div :class="color" class="w-2 h-2 rounded-full"></div>
                             <span class="text-[8px] font-black text-slate-500 dark:text-gray-400 uppercase tracking-widest">{{ lbl }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[9px] font-black text-slate-900 dark:text-white uppercase tracking-widest hidden sm:block">Extrato Financeiro</span>
                        <button v-if="proposal" @click="openCreateModal" class="px-3 py-1.5 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all shadow-md active:scale-95 flex items-center gap-1.5 shrink-0">
                            <span>+</span> Nova Parcela
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-10">
                    <div v-for="group in groupedBills" :key="group.key" class="space-y-4">
                        <div class="flex items-center gap-4">
                            <h3 class="text-[10px] font-black text-indigo-500 dark:text-cyan-400 uppercase tracking-[0.2em] whitespace-nowrap">{{ group.label }}</h3>
                            <div class="h-px w-full bg-gradient-to-r from-indigo-500/20 to-transparent"></div>
                        </div>

                        <div class="overflow-visible rounded-xl border border-slate-100 dark:border-white/5 shadow-sm dark:shadow-none">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/[0.02] text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-3 py-3 w-8 text-center">
                                            <svg class="w-3 h-3 text-gray-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </th>
                                        <th class="px-4 py-3">Parcela</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Vencimento</th>
                                        <th class="px-4 py-3">Data de Pgto</th>
                                        <th class="px-4 py-3">Forma</th>
                                        <th class="px-4 py-3">Valor</th>
                                        <th class="px-4 py-3">Juros/Mora</th>
                                        <th class="px-4 py-3">Valor Pago</th>
                                        <th class="px-4 py-3 text-right">Status</th>
                                        <th class="px-4 py-3 text-right w-10">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="bill in group.items" :key="bill.id" class="text-[10px] hover:bg-white/[0.01] transition-colors group">
                                        <td class="px-3 py-3 text-center">
                                            <input 
                                                v-if="['pending', 'overdue'].includes(bill.status)" 
                                                type="checkbox" 
                                                :value="bill.id" 
                                                v-model="selectedBills" 
                                                class="rounded bg-white dark:bg-black border-slate-300 dark:border-white/20 text-indigo-500 focus:ring-indigo-500/30 w-3.5 h-3.5 cursor-pointer"
                                            >
                                        </td>
                                        <td class="px-4 py-3 font-bold text-gray-400">{{ bill.installment_number }}/{{ bill.total_installments }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white uppercase text-[9px]">
                                            <div class="flex items-center gap-2">
                                                <span>{{ group.label }}</span>
                                                <!-- Ícone de Observação -->
                                                <span v-if="bill.observations" title="Possui Observação" class="px-1.5 py-0.5 bg-yellow-500/10 text-yellow-500 rounded text-[7px] font-black uppercase tracking-widest border border-yellow-500/20 flex items-center gap-1 cursor-help">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                                    OBS
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-400 font-mono">{{ formatDate(bill.due_date) }}</td>
                                        <td class="px-4 py-3 text-gray-400 font-mono">{{ formatDate(bill.paid_at) }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white uppercase text-[9px]">{{ bill.payment_method }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(bill.amount) }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-400 dark:text-gray-500 font-mono">{{ formatCurrency(bill.interest_amount) }}</td>
                                        <td class="px-4 py-3 font-black text-indigo-600 dark:text-cyan-400 font-mono">{{ formatCurrency(bill.paid_amount || 0) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div :class="getStatusColor(bill.status)" class="w-3 h-3 rounded-full inline-block shadow-[0_0_8px_rgba(34,197,94,0.2)]"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <!-- Kebab Menu for Actions -->
                                            <div class="relative group/kebab">
                                                <button class="p-2 text-slate-500 dark:text-gray-500 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 rounded-lg transition-colors flex items-center justify-center relative overflow-hidden shadow-sm dark:shadow-none">
                                                    <div class="absolute inset-0 bg-slate-200 dark:bg-white/5 opacity-0 group-hover/kebab:opacity-100 transition-opacity"></div>
                                                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                                </button>
                                                
                                                <!-- Dropdown Menu -->
                                                <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#1f2937] border border-slate-200 dark:border-white/10 rounded-xl shadow-2xl py-1 z-50 opacity-0 invisible group-hover/kebab:opacity-100 group-hover/kebab:visible transition-all duration-200 transform origin-top-right translate-y-2 group-hover/kebab:translate-y-0">
                                                    <button @click="openViewModal(bill)" class="w-full px-4 py-2.5 text-left text-[11px] font-bold text-slate-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-cyan-400 hover:bg-slate-50 dark:hover:bg-white/5 flex items-center gap-3 transition-colors border-b border-slate-100 dark:border-white/5 last:border-0">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                        Visualizar
                                                    </button>
                                                    <button @click="openEditModal(bill)" class="w-full px-4 py-2.5 text-left text-[11px] font-bold text-slate-600 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-white/5 flex items-center gap-3 transition-colors border-b border-slate-100 dark:border-white/5 last:border-0">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        Editar Parcela
                                                    </button>
                                                    <button @click="deleteBill(bill)" class="w-full px-4 py-2.5 text-left text-[11px] font-bold text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-500/10 flex items-center gap-3 transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        Excluir
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="groupedBills.length === 0" class="py-20 text-center space-y-4 text-gray-600">
                        <p class="text-[9px] font-black uppercase tracking-[0.3em]">Nenhum extrato gerado</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Installment Modal -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-white/10 p-6 space-y-6 rounded-2xl shadow-xl dark:shadow-none">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-4">
                    <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Adicionar Nova Parcela</h3>
                    <button @click="closeCreateModal" class="text-slate-400 hover:text-slate-900 dark:text-gray-500 dark:hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Categoria</label>
                            <select v-model="createForm.category" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                                <option value="taxa_contrato" class="bg-white dark:bg-black">Taxa de Contrato</option>
                                <option value="entrada" class="bg-white dark:bg-black">Entrada</option>
                                <option value="saldo" class="bg-white dark:bg-black">Parcelas do Saldo</option>
                                <option value="taxa_manutencao" class="bg-white dark:bg-black">Taxa de Manutenção</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Forma de Pgto</label>
                            <select v-model="createForm.payment_method" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value" class="bg-black">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Valor Original</label>
                            <input :value="formatCurrencyInput(createForm.amount)" @input="e => updateCurrencyInput(e, createForm, 'amount')" type="text" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Vencimento</label>
                            <input v-model="createForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Status</label>
                            <select v-model="createForm.status" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                                <option value="pending" class="bg-black">A Receber</option>
                                <option value="paid" class="bg-black">Baixado</option>
                                <option value="overdue" class="bg-black">Inadimplente</option>
                                <option value="cancelled" class="bg-black">Cancelado</option>
                            </select>
                        </div>
                        <div class="space-y-1" v-if="createForm.status === 'paid'">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Data Pago</label>
                            <input v-model="createForm.paid_at" type="date" class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20">
                        </div>
                        <div class="space-y-1" v-if="createForm.status === 'paid'">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Valor Pago</label>
                            <input :value="formatCurrencyInput(createForm.paid_amount)" @input="e => updateCurrencyInput(e, createForm, 'paid_amount')" type="text" class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Observações Internas (Opcional)</label>
                        <textarea v-model="createForm.observations" rows="2" placeholder="Ex: Acordo feito via WhatsApp no dia 15/04 com a cliente..." class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20 resize-none placeholder:text-gray-700"></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-white/10 rounded-lg text-[9px] font-black uppercase tracking-widest text-gray-400 hover:bg-white/5 transition-all">Cancelar</button>
                        <button type="submit" :disabled="createForm.processing" class="px-6 py-2 bg-white text-black rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all disabled:opacity-50">
                            {{ createForm.processing ? 'Adicionando...' : 'Adicionar Parcela' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Installment Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-white/10 p-6 space-y-6 rounded-2xl shadow-xl dark:shadow-none">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-4">
                    <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Editar Parcela</h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-slate-900 dark:text-gray-500 dark:hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Vencimento</label>
                            <input v-model="editForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Data de Pago</label>
                            <input v-model="editForm.paid_at" type="date" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Valor Original</label>
                            <input :value="formatCurrencyInput(editForm.amount)" @input="e => updateCurrencyInput(e, editForm, 'amount')" type="text" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Forma de Pgto</label>
                            <select v-model="editForm.payment_method" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value" class="bg-white dark:bg-black">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Juros/Mora</label>
                            <input :value="formatCurrencyInput(editForm.interest_amount)" @input="e => updateCurrencyInput(e, editForm, 'interest_amount')" type="text" class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20">
                        </div>
                        <div class="space-y-1" v-if="editForm.status === 'paid' || editForm.paid_amount > 0">
                            <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Valor Pago</label>
                            <input :value="formatCurrencyInput(editForm.paid_amount)" @input="e => updateCurrencyInput(e, editForm, 'paid_amount')" type="text" class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Observações Internas</label>
                        <textarea v-model="editForm.observations" rows="3" placeholder="Insira detalhes ou histórico importante desta parcela..." class="w-full bg-white/[0.02] border-white/10 rounded-lg text-[10px] text-white focus:ring-cyan-500/20 resize-none placeholder:text-gray-700"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Status</label>
                        <select v-model="editForm.status" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-cyan-500/20">
                            <option value="pending" class="bg-white dark:bg-black">A Receber</option>
                            <option value="paid" class="bg-white dark:bg-black">Baixado</option>
                            <option value="overdue" class="bg-white dark:bg-black">Inadimplente</option>
                            <option value="cancelled" class="bg-white dark:bg-black">Cancelado</option>
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 border border-white/10 rounded-lg text-[9px] font-black uppercase tracking-widest text-gray-400 hover:bg-white/5 transition-all">Cancelar</button>
                        <button type="submit" :disabled="editForm.processing" class="px-6 py-2 bg-white text-black rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-cyan-400 transition-all disabled:opacity-50">
                            {{ editForm.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Renegotiation/BulkPay Floating Action Bar -->
        <div v-show="selectedBills.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white/80 dark:bg-black/60 backdrop-blur-xl border border-slate-200 dark:border-white/20 px-6 py-3 rounded-2xl shadow-[0_0_40px_rgba(99,102,241,0.15)] dark:shadow-[0_0_40px_rgba(6,182,212,0.15)] z-40 flex items-center gap-6 animate-in slide-in-from-bottom-5">
            <span class="text-[10px] font-black text-indigo-500 dark:text-cyan-400 uppercase tracking-widest">{{ selectedBills.length }} Selecionadas</span>
            <div class="flex gap-2">
                <button v-if="selectedBills.length > 1" @click="openRenegotiateModal" class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-red-500/20 transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Renegociar
                </button>
                <button @click="openBulkPayModal" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-green-500/20 transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Baixar Selecionadas
                </button>
            </div>
        </div>

        <!-- View Installment Modal -->
        <Modal :show="isViewModalOpen" @close="closeViewModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-white/10 p-6 space-y-6 relative overflow-hidden rounded-2xl shadow-xl dark:shadow-none">
                <!-- Decorative glow -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/5 rounded-full blur-[60px] pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-4 mb-6">
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-indigo-500 dark:bg-cyan-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)] dark:shadow-[0_0_10px_rgba(6,182,212,0.5)]"></span>
                            Visualizar Parcela
                        </h3>
                        <button @click="closeViewModal" class="text-slate-400 hover:text-slate-900 dark:text-gray-500 dark:hover:text-white transition-colors">✕</button>
                    </div>

                    <div v-if="viewBill" class="space-y-6">
                        <!-- Head -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/[0.02] border border-white/5 p-4 rounded-xl">
                                <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Categoria</p>
                                <p class="text-xs font-bold text-white uppercase tracking-wider">{{ viewBill.category?.replace('_', ' ') }}</p>
                            </div>
                            <div class="bg-white/[0.02] border border-white/5 p-4 rounded-xl">
                                <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Forma de Pgto.</p>
                                <p class="text-xs font-bold text-white uppercase tracking-wider">{{ viewBill.payment_method }}</p>
                            </div>
                        </div>

                        <!-- Financial -->
                        <div class="bg-indigo-500/5 dark:bg-white/[0.02] border border-indigo-500/10 dark:border-white/5 p-4 rounded-xl grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Valor Original</p>
                                <p class="text-xs font-bold text-indigo-600 dark:text-cyan-400">R$ {{ Number(viewBill.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                            <div v-if="viewBill.interest_amount > 0">
                                <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Juros / Mora</p>
                                <p class="text-xs font-bold text-yellow-400">+ R$ {{ Number(viewBill.interest_amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                            <div v-if="viewBill.paid_amount > 0 || viewBill.status === 'paid'">
                                <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Valor Pago</p>
                                <p class="text-xs font-bold text-green-400">R$ {{ Number(viewBill.paid_amount || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                        </div>

                        <!-- Dates & Status -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-slate-50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 p-3 rounded-xl border-l-2 border-l-blue-500/50">
                                <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest mb-1">Vencimento</p>
                                <p class="text-[10px] font-bold text-slate-900 dark:text-white">{{ formatDate(viewBill.due_date) }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 p-3 rounded-xl border-l-2 border-l-green-500/50" v-if="viewBill.paid_at">
                                <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest mb-1">Data Pagamento</p>
                                <p class="text-[10px] font-bold text-slate-900 dark:text-white">{{ formatDate(viewBill.paid_at) }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 p-3 rounded-xl">
                                <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest mb-1">Status</p>
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 mt-0.5 rounded-full border border-slate-200 dark:border-white/10" 
                                    :class="{
                                        'bg-blue-500/10 text-blue-600 dark:text-blue-400': viewBill.status === 'pending',
                                        'bg-green-500/10 text-green-600 dark:text-green-400': viewBill.status === 'paid',
                                        'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400': viewBill.status === 'overdue',
                                        'bg-red-500/10 text-red-600 dark:text-red-400': viewBill.status === 'cancelled'
                                    }">
                                    <span class="w-1.5 h-1.5 rounded-full" 
                                        :class="{
                                            'bg-blue-500': viewBill.status === 'pending',
                                            'bg-green-500': viewBill.status === 'paid',
                                            'bg-yellow-500': viewBill.status === 'overdue',
                                            'bg-red-500': viewBill.status === 'cancelled'
                                        }"></span>
                                    <span class="text-[8px] font-black uppercase tracking-widest">
                                        {{ viewBill.status === 'pending' ? 'A Receber' : viewBill.status === 'paid' ? 'Baixado' : viewBill.status === 'overdue' ? 'Inadimplente' : 'Cancelado' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Observations -->
                        <div class="bg-slate-50 dark:bg-[#161b22] border border-slate-200 dark:border-white/5 p-4 rounded-xl shadow-inner relative">
                            <svg class="w-4 h-4 text-slate-400 dark:text-gray-600 absolute top-4 right-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            <p class="text-[9px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-[0.2em] mb-3">Observações</p>
                            <p class="text-xs text-slate-700 dark:text-gray-300 leading-relaxed font-medium whitespace-pre-wrap">{{ viewBill.observations || 'Nenhuma observação registrada.' }}</p>
                        </div>

                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 flex justify-end flex-wrap gap-3">
                        <button @click="closeViewModal" class="px-5 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 dark:text-gray-300 transition-all">
                            Fechar Visão
                        </button>
                        <button v-if="viewBill" @click="openEditModal(viewBill); isViewModalOpen = false;" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-cyan-500/20 transition-all flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Editar Parcela
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Premium Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div v-if="billToDelete" class="bg-white dark:bg-[#161b22] border border-slate-200 dark:border-red-500/30 p-8 text-center relative overflow-hidden shadow-2xl dark:shadow-[0_0_100px_rgba(239,68,68,0.05)] rounded-2xl">
                <!-- Decorative background glow -->
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-48 bg-red-500/10 blur-[80px] pointer-events-none"></div>

                <div class="relative w-20 h-20 bg-red-500/10 border border-red-500/20 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-red-500/10 rotate-3 transition-transform hover:rotate-0">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-2 relative z-10">Excluir Parcela?</h3>
                <p class="text-slate-500 dark:text-gray-400 text-xs font-medium leading-relaxed mb-8 px-2 relative z-10">
                    Você está prestes a excluir a parcela <strong class="text-slate-900 dark:text-white">#{{ billToDelete.installment_number }}</strong> no valor de <strong class="text-slate-900 dark:text-white">{{ formatCurrency(billToDelete.amount) }}</strong>. <br>O status financeiro do contrato será recalculado automaticamente. Esta ação é <span class="text-red-600 dark:text-red-400 font-bold">definitiva e irreversível</span>.
                </p>

                <div class="flex flex-col gap-3 relative z-10">
                    <button @click="executeDelete" :disabled="isDeleting" class="w-full py-4 bg-red-600 hover:bg-red-500 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.3em] shadow-xl shadow-red-500/20 transition-all active:scale-95 flex items-center justify-center gap-3 border border-red-400/20 disabled:opacity-50">
                        <svg v-if="isDeleting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>{{ isDeleting ? 'Excluindo Lote...' : 'SIM, EXCLUIR AGORA' }}</span>
                    </button>
                    
                    <button @click="closeDeleteModal" :disabled="isDeleting" class="w-full py-4 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-500 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white rounded-2xl font-bold uppercase text-[10px] tracking-widest transition-all">
                        Cancelar Operação
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Renegotiate Modal -->
        <Modal :show="isRenegotiateModalOpen" @close="closeRenegotiateModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-white/10 p-6 space-y-6 relative overflow-hidden rounded-2xl shadow-xl dark:shadow-none">
                <div class="absolute top-0 right-0 w-40 h-40 bg-orange-500/10 rounded-full blur-[80px] pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-4 mb-6">
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-orange-500 rounded-full shadow-[0_0_10px_rgba(249,115,22,0.5)]"></span>
                            Renegociar Parcelas
                        </h3>
                        <button @click="closeRenegotiateModal" class="text-slate-400 hover:text-slate-900 dark:text-gray-500 dark:hover:text-white transition-colors">✕</button>
                    </div>

                    <form @submit.prevent="submitRenegotiate" class="space-y-6">
                        <div class="bg-orange-500/5 border border-orange-500/20 p-4 rounded-xl text-center">
                            <p class="text-[9px] font-black text-orange-400/80 uppercase tracking-widest mb-1">Valor Total Agrupado</p>
                            <p class="text-2xl font-black text-orange-400">R$ {{ Number(renegotiateTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                            <p class="text-[8px] font-bold text-gray-500 mt-2">Corresponde a {{ selectedBills.length }} parcelas selecionadas</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Nova Data de Vencimento</label>
                                <input v-model="renegotiateForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-orange-500/20" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Nova Forma de Pgto</label>
                                <select v-model="renegotiateForm.payment_method" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-orange-500/20" required>
                                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value" class="bg-white dark:bg-black">{{ m.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end gap-3">
                            <button type="button" @click="closeRenegotiateModal" class="px-5 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 dark:text-gray-300 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="renegotiateForm.processing" class="px-5 py-2.5 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-orange-500/20 transition-all flex items-center gap-2">
                                Confirmar Renegociação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

        <!-- Bulk Pay Modal -->
        <Modal :show="isBulkPayModalOpen" @close="closeBulkPayModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-white/10 p-6 space-y-6 relative overflow-hidden rounded-2xl shadow-xl dark:shadow-none">
                <div class="absolute top-0 right-0 w-40 h-40 bg-green-500/10 rounded-full blur-[80px] pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-4 mb-6">
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-green-500 rounded-full shadow-[0_0_10px_rgba(34,197,94,0.5)]"></span>
                            Baixa em Lote
                        </h3>
                        <button @click="closeBulkPayModal" class="text-slate-400 hover:text-slate-900 dark:text-gray-500 dark:hover:text-white transition-colors">✕</button>
                    </div>

                    <form @submit.prevent="submitBulkPay" class="space-y-6">
                        <div class="bg-green-500/5 border border-green-500/20 p-4 rounded-xl text-center">
                            <p class="text-[9px] font-black text-green-400/80 uppercase tracking-widest mb-1">Valor Total a Baixar</p>
                            <p class="text-2xl font-black text-green-400">R$ {{ Number(bulkPayTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                            <p class="text-[8px] font-bold text-gray-500 mt-2">Corresponde a {{ selectedBills.length }} parcelas selecionadas</p>
                        </div>

                        <!-- Toggle Mode -->
                        <div class="bg-slate-100 dark:bg-white/5 p-1 rounded-xl flex border border-slate-200 dark:border-white/10">
                            <button type="button" @click="bulkPayForm.mode = 'single'" :class="bulkPayForm.mode === 'single' ? 'bg-green-500 text-white shadow-lg' : 'text-slate-500 dark:text-gray-500 hover:text-slate-900 dark:hover:text-white'" class="flex-1 py-2 text-[8px] font-black uppercase tracking-widest rounded-lg transition-all">
                                Mesma Data
                            </button>
                            <button type="button" @click="bulkPayForm.mode = 'individual'" :class="bulkPayForm.mode === 'individual' ? 'bg-green-500 text-white shadow-lg' : 'text-slate-500 dark:text-gray-500 hover:text-slate-900 dark:hover:text-white'" class="flex-1 py-2 text-[8px] font-black uppercase tracking-widest rounded-lg transition-all">
                                Datas Individuais
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4" v-if="bulkPayForm.mode === 'single'">
                            <div class="space-y-1">
                                <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Data do Pagamento</label>
                                <input v-model="bulkPayForm.paid_at" type="date" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-green-500/20" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Forma de Pagamento</label>
                                <select v-model="bulkPayForm.payment_method" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-green-500/20" required>
                                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value" class="bg-white dark:bg-black">{{ m.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4" v-else>
                            <div class="space-y-1">
                                <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Forma de Pagamento (Geral)</label>
                                <select v-model="bulkPayForm.payment_method" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-green-500/20" required>
                                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value" class="bg-white dark:bg-black">{{ m.label }}</option>
                                </select>
                            </div>
                            
                            <div class="max-h-[200px] overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                                <div v-for="billId in selectedBills" :key="billId" class="bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/5 p-3 rounded-lg flex items-center justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase truncate">
                                            {{ bills.find(b => b.id === billId)?.category?.replace('_', ' ') }}
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-900 dark:text-white truncate">
                                            Parcela #{{ bills.find(b => b.id === billId)?.installment_number }}
                                        </p>
                                    </div>
                                    <input v-model="bulkPayForm.bill_dates[billId]" type="date" class="bg-slate-100 dark:bg-black border border-slate-200 dark:border-white/10 rounded text-[9px] text-slate-900 dark:text-white w-28 focus:ring-green-500/20 h-7 px-1">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Observação (Opcional)</label>
                            <textarea v-model="bulkPayForm.observations" rows="2" class="w-full bg-slate-50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-green-500/20 placeholder:text-slate-400 dark:placeholder:text-white/5" placeholder="Ex: Recebido via transferência bancária..."></textarea>
                        </div>

                        <div class="pt-4 flex justify-end gap-3">
                            <button type="button" @click="closeBulkPayModal" class="px-5 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] text-slate-600 dark:text-gray-300 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="bulkPayForm.processing" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-lg shadow-green-500/20 transition-all flex items-center gap-2">
                                Confirmar Baixa em Lote
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-in { animation: fade-in 0.5s ease-out both; }
input, select { color-scheme: dark; }
</style>
