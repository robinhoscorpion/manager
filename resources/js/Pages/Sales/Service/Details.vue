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
        <!-- ============================================================ -->
        <!--  PAGE WRAPPER — Full width, padding lateral generoso          -->
        <!-- ============================================================ -->
        <div class="min-h-screen bg-slate-50 dark:bg-[#0d1117]">
            <div class="px-6 lg:px-10 py-8 space-y-6 max-w-[1600px] mx-auto animate-in">

                <!-- =========================================================== -->
                <!--  HERO HEADER                                                  -->
                <!-- =========================================================== -->
                <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 dark:from-[#0d1117] dark:via-indigo-950/50 dark:to-[#0d1117] border border-indigo-500/20 shadow-2xl shadow-indigo-500/10">
                    <!-- Ambient glow -->
                    <div class="absolute top-0 left-0 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px] pointer-events-none"></div>
                    <div class="absolute bottom-0 right-0 w-64 h-64 bg-cyan-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                    
                    <div class="relative z-10 p-8 lg:p-10">
                        <!-- Breadcrumb -->
                        <nav class="flex items-center gap-2 text-[10px] font-bold text-indigo-300/60 uppercase tracking-widest mb-6">
                            <Link :href="route('sales.atendimentos')" class="hover:text-indigo-300 transition-colors">Sala de Vendas</Link>
                            <span class="text-white/20">/</span>
                            <span class="text-indigo-200/40">Contrato #{{ proposal?.contract_number || 'S/N' }}</span>
                        </nav>

                        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-8">
                            <!-- Identity -->
                            <div class="space-y-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                        :class="proposal?.contract_number
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                            : 'bg-amber-500/10 text-amber-400 border-amber-500/30'">
                                        {{ proposal?.contract_number ? '● Contrato Ativo' : '○ Sem Contrato' }}
                                    </span>
                                    <span class="text-white/30 text-xs">|</span>
                                    <span class="text-indigo-200/50 text-[10px] font-bold uppercase tracking-widest">{{ formatDate(service.date) }} às {{ service.time }}</span>
                                </div>

                                <h1 class="text-3xl lg:text-4xl font-black text-white uppercase tracking-tight leading-none">
                                    {{ client?.nome }}
                                </h1>

                                <div class="flex flex-wrap items-center gap-4 text-[11px] font-bold text-indigo-200/50 uppercase tracking-widest">
                                    <span v-if="client?.cpf">CPF: <span class="text-white/70 font-mono">{{ client.cpf }}</span></span>
                                    <span v-if="product">Produto: <span class="text-white/70">{{ product.name }}</span></span>
                                    <span v-if="proposal?.contract_number">Contrato: <span class="text-cyan-400 font-mono font-black">{{ proposal.contract_number }}</span></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3 shrink-0">
                                <button @click="printDocument('sales.atendimentos.contrato.pdf')"
                                    class="flex items-center gap-2.5 bg-white text-slate-900 hover:bg-indigo-500 hover:text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-black/20 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Contrato
                                </button>
                                <button @click="printDocument('sales.atendimentos.proposta.pdf')"
                                    class="flex items-center gap-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Proposta
                                </button>
                                <button @click="printDocument('sales.atendimentos.checklist.pdf')"
                                    class="flex items-center gap-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Checklist
                                </button>
                                <button @click="printDocument('sales.atendimentos.ficha.pdf')"
                                    class="flex items-center gap-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Ficha
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  METRICS ROW — 4 cards financeiros premium                    -->
                <!-- =========================================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                    <!-- Valor Total -->
                    <div class="relative bg-white dark:bg-[#141925] border border-slate-200 dark:border-white/10 rounded-2xl p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <!-- Decorative icon -->
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Valor do Contrato</p>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white font-mono leading-none">{{ formatCurrency(totalValue) }}</p>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[9px] font-black uppercase tracking-widest">{{ totalPoints.toLocaleString() }} pts</span>
                                <span class="text-[10px] text-slate-400 dark:text-gray-600 font-medium">{{ product?.duration || 'S/D' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Valor Pago -->
                    <div class="relative bg-white dark:bg-[#141925] border border-slate-200 dark:border-white/10 rounded-2xl p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-500/80 uppercase tracking-widest">Total Pago</p>
                            </div>
                            <p class="text-3xl font-black text-emerald-700 dark:text-emerald-400 font-mono leading-none">{{ formatCurrency(totalPaid) }}</p>
                            <div class="mt-3 space-y-1.5">
                                <div class="w-full h-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full overflow-hidden">
                                    <div :style="{ width: percentPaid + '%' }"
                                        class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(16,185,129,0.4)]">
                                    </div>
                                </div>
                                <p class="text-[10px] text-emerald-600/70 dark:text-emerald-500/60 font-bold">{{ percentPaid }}% quitado</p>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo em Aberto -->
                    <div class="relative bg-white dark:bg-[#141925] border border-slate-200 dark:border-white/10 rounded-2xl p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-orange-500/10 dark:bg-orange-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-orange-600 dark:text-orange-500/80 uppercase tracking-widest">Saldo em Aberto</p>
                            </div>
                            <p class="text-3xl font-black text-orange-700 dark:text-orange-400 font-mono leading-none">{{ formatCurrency(amountOpen) }}</p>
                            <div class="mt-3">
                                <span class="px-2 py-0.5 rounded-md bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 text-[9px] font-black uppercase tracking-widest">{{ (100 - parseFloat(percentPaid)).toFixed(1) }}% pendente</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pontos Disponíveis -->
                    <div class="relative bg-white dark:bg-[#141925] border border-slate-200 dark:border-white/10 rounded-2xl p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-amber-600 dark:text-amber-500/80 uppercase tracking-widest">Pontos Disponíveis</p>
                            </div>
                            <p class="text-3xl font-black text-amber-700 dark:text-amber-400 leading-none">
                                {{ availablePoints.toLocaleString() }} <span class="text-lg font-bold opacity-50">pts</span>
                            </p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[9px] font-black uppercase tracking-widest">{{ releasedPoints.toLocaleString() }} liberados</span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-gray-500 text-[9px] font-black uppercase tracking-widest">{{ utilizedPoints.toLocaleString() }} usados</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  INFO ROW — Vigência + Cliente em 2 colunas                   -->
                <!-- =========================================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Vigência -->
                    <div class="bg-white dark:bg-[#141925] border border-slate-200/80 dark:border-white/10 rounded-2xl p-6 hover:shadow-lg transition-all duration-300">
                        <h3 class="text-[10px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest mb-5 flex items-center gap-2.5">
                            <span class="w-5 h-5 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            Vigência do Contrato
                        </h3>
                        <div class="grid grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Duração</p>
                                <p class="text-lg font-black text-slate-900 dark:text-white leading-none">{{ product?.duration || 'N/A' }}</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Expira em</p>
                                <p class="text-lg font-black text-slate-900 dark:text-white leading-none">{{ usableUntilStr }}</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Restam</p>
                                <p class="text-lg font-black text-indigo-600 dark:text-cyan-400 leading-none">{{ yearsRemainingVal }} <span class="text-sm font-bold opacity-60">anos</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Dados do Titular -->
                    <div class="bg-white dark:bg-[#141925] border border-slate-200/80 dark:border-white/10 rounded-2xl p-6 hover:shadow-lg transition-all duration-300">
                        <h3 class="text-[10px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest mb-5 flex items-center gap-2.5">
                            <span class="w-5 h-5 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            Dados do Titular
                        </h3>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Celular</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ client?.celular1 || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Estado Civil</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white capitalize">{{ client?.estado_civil || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Cidade / UF</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ [client?.address?.cidade, client?.address?.estado].filter(Boolean).join(' / ') || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">E-mail</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ client?.email || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  EXTRATO FINANCEIRO — Tabela Principal                        -->
                <!-- =========================================================== -->
                <div class="bg-white dark:bg-[#141925] border border-slate-200 dark:border-white/5 rounded-2xl hover:shadow-lg transition-all duration-300">
                    <!-- Header da tabela -->
                    <div class="px-6 py-5 border-b border-slate-200/80 dark:border-white/5 flex items-center justify-between flex-wrap gap-4 bg-slate-50/50 dark:bg-white/[0.01] rounded-t-2xl">
                        <div class="flex items-center gap-4">
                            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Extrato Financeiro
                            </h3>
                            <!-- Legenda de Status -->
                            <div class="hidden sm:flex items-center gap-3 border-l border-slate-200 dark:border-white/10 pl-4">
                                <div v-for="(cfg, key) in { pending: { label: 'A Receber', color: 'bg-blue-500' }, paid: { label: 'Baixado', color: 'bg-emerald-500' }, overdue: { label: 'Inadimplente', color: 'bg-orange-500' }, cancelled: { label: 'Cancelado', color: 'bg-red-500' } }" :key="key" class="flex items-center gap-1.5">
                                    <div :class="cfg.color" class="w-2 h-2 rounded-full"></div>
                                    <span class="text-[9px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">{{ cfg.label }}</span>
                                </div>
                            </div>
                        </div>
                        <button v-if="proposal" @click="openCreateModal"
                            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md shadow-indigo-500/20 active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Nova Parcela
                        </button>
                    </div>

                    <div class="p-0">
                        <div v-for="group in groupedBills" :key="group.key" class="border-b border-slate-200/80 dark:border-white/5 last:border-0">
                            <!-- Cabeçalho do grupo -->
                            <div class="px-6 py-3.5 bg-slate-100/50 dark:bg-white/[0.02] border-b border-slate-200/80 dark:border-white/5 flex items-center gap-3">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                <h4 class="text-[10px] font-black text-slate-700 dark:text-gray-300 uppercase tracking-widest">{{ group.label }}</h4>
                                <span class="ml-auto text-[9px] px-2 py-0.5 rounded bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">{{ group.items.length }} parcela{{ group.items.length > 1 ? 's' : '' }}</span>
                            </div>

                            <!-- Tabela -->
                            <div>
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-white/5 bg-slate-50/30 dark:bg-transparent">
                                            <th class="px-6 py-4 w-10 text-center">
                                                <span class="sr-only">Seleção</span>
                                            </th>
                                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Parcela</th>
                                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Vencimento</th>
                                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest hidden md:table-cell">Data Pagto</th>
                                            <th class="px-4 py-4 text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest hidden lg:table-cell">Forma</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Valor</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest hidden xl:table-cell">Juros</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Pago</th>
                                            <th class="px-4 py-4 text-center text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Status</th>
                                            <th class="px-6 py-4 text-center text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.02]">
                                        <tr v-for="bill in group.items" :key="bill.id"
                                            class="hover:bg-slate-50 dark:hover:bg-white/[0.01] transition-colors group/row">
                                            <!-- Checkbox -->
                                            <td class="px-6 py-4.5 text-center">
                                                <input
                                                    v-if="['pending', 'overdue'].includes(bill.status)"
                                                    type="checkbox"
                                                    :value="bill.id"
                                                    v-model="selectedBills"
                                                    class="w-4 h-4 rounded border-slate-300 dark:border-white/20 bg-transparent text-indigo-600 focus:ring-indigo-500/30 cursor-pointer transition-colors"
                                                />
                                            </td>

                                            <!-- Parcela # -->
                                            <td class="px-4 py-4.5">
                                                <span class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                                    {{ bill.installment_number }} / {{ bill.total_installments }}
                                                </span>
                                            </td>

                                            <!-- Vencimento -->
                                            <td class="px-4 py-4.5">
                                                <span class="text-sm font-mono font-bold text-slate-700 dark:text-gray-300">{{ formatDate(bill.due_date) }}</span>
                                            </td>

                                            <!-- Data Pagamento -->
                                            <td class="px-4 py-4.5 hidden md:table-cell">
                                                <span class="text-sm font-mono font-medium text-slate-500 dark:text-gray-500">{{ formatDate(bill.paid_at) || '—' }}</span>
                                            </td>

                                            <!-- Forma Pgto -->
                                            <td class="px-4 py-4.5 hidden lg:table-cell">
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-[9px] font-black uppercase tracking-widest text-slate-600 dark:text-gray-400">
                                                    {{ bill.payment_method }}
                                                </span>
                                            </td>

                                            <!-- Valor -->
                                            <td class="px-4 py-4.5 text-right">
                                                <span class="text-[15px] font-black text-slate-900 dark:text-white font-mono">{{ formatCurrency(bill.amount) }}</span>
                                            </td>

                                            <!-- Juros -->
                                            <td class="px-4 py-4.5 text-right hidden xl:table-cell">
                                                <span class="text-sm font-mono font-bold" :class="bill.interest_amount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-gray-600'">
                                                    {{ bill.interest_amount > 0 ? formatCurrency(bill.interest_amount) : '—' }}
                                                </span>
                                            </td>

                                            <!-- Valor Pago -->
                                            <td class="px-4 py-4.5 text-right">
                                                <span class="text-[15px] font-black font-mono" :class="bill.paid_amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-gray-600'">
                                                    {{ bill.paid_amount > 0 ? formatCurrency(bill.paid_amount) : '—' }}
                                                </span>
                                            </td>

                                            <!-- Status Badge -->
                                            <td class="px-4 py-4.5 text-center">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded border text-[9px] font-black uppercase tracking-widest"
                                                    :class="{
                                                        'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20': bill.status === 'pending',
                                                        'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20': bill.status === 'paid',
                                                        'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20': bill.status === 'overdue',
                                                        'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20': bill.status === 'cancelled',
                                                    }">
                                                    <span class="w-1.5 h-1.5 rounded-full"
                                                        :class="{
                                                            'bg-blue-500': bill.status === 'pending',
                                                            'bg-emerald-500': bill.status === 'paid',
                                                            'bg-orange-500': bill.status === 'overdue',
                                                            'bg-red-500': bill.status === 'cancelled',
                                                        }"></span>
                                                    {{ bill.status === 'pending' ? 'A Receber' : bill.status === 'paid' ? 'Baixado' : bill.status === 'overdue' ? 'Atrasado' : 'Cancelado' }}
                                                </span>
                                            </td>

                                            <!-- Ações Kebab -->
                                            <td class="px-6 py-4.5 text-center">
                                                <div class="relative group/kebab flex justify-center">
                                                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-sm text-slate-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-200 dark:hover:border-indigo-500/30 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                                    </button>
                                                    <div class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-[#1a202c] border border-slate-200 dark:border-white/10 rounded-xl shadow-xl py-1.5 z-50 opacity-0 invisible group-hover/kebab:opacity-100 group-hover/kebab:visible transition-all duration-200 translate-y-1 group-hover/kebab:translate-y-0">
                                                        <button @click="openViewModal(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-cyan-400 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            Visualizar
                                                        </button>
                                                        <button @click="openEditModal(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-white/5 transition-colors border-t border-slate-100 dark:border-white/5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                            Editar
                                                        </button>
                                                        <button @click="deleteBill(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-black uppercase tracking-widest text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors border-t border-slate-100 dark:border-white/5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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

                        <!-- Empty state -->
                        <div v-if="groupedBills.length === 0" class="py-24 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-slate-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Nenhum extrato gerado</p>
                            <p class="text-xs text-slate-400 dark:text-gray-600 mt-2 font-medium">Crie a proposta para gerar as parcelas automaticamente</p>
                        </div>
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