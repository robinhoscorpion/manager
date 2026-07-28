<script setup>
import { computed, ref, onMounted } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ProposalFormModal from '@/Components/Sales/ProposalFormModal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

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
const isEditModalOpen = ref(false);
const isEditProposalModalOpen = ref(false);

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

// --- Protocol Logic ---
const isProtocolModalOpen = ref(false);
const activeProtocolTab = ref('edit');
const protocolAttachments = ref([]);

const protocolForm = useForm({
    subject: 'Dúvidas Gerais',
    priority: 'media',
    message: '',
    attachments: []
});

const handleProtocolFileChange = (e) => {
    const files = Array.from(e.target.files);
    // Append instead of replace to allow multiple selections
    protocolAttachments.value = [...protocolAttachments.value, ...files];
    protocolForm.attachments = protocolAttachments.value;
};

const removeProtocolAttachment = (index) => {
    protocolAttachments.value.splice(index, 1);
    protocolForm.attachments = protocolAttachments.value;
};

const openProtocolModal = () => {
    protocolForm.reset();
    protocolAttachments.value = [];
    protocolForm.attachments = [];
    activeProtocolTab.value = 'edit';
    isProtocolModalOpen.value = true;
};

const closeProtocolModal = () => {
    isProtocolModalOpen.value = false;
    protocolForm.reset();
    protocolAttachments.value = [];
    protocolForm.attachments = [];
    activeProtocolTab.value = 'edit';
};

const generatedProtocolNumber = ref(null);
const copied = ref(false);

const copyProtocol = () => {
    if (generatedProtocolNumber.value) {
        navigator.clipboard.writeText(generatedProtocolNumber.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const submitProtocol = () => {
    // Muda para a tela de carregamento animada
    activeProtocolTab.value = 'loading';
    protocolForm.attachments = protocolAttachments.value;
    
    protocolForm.post(route('sales.atendimentos.protocols.store', props.service.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash && page.props.flash.generated_protocol_number) {
                generatedProtocolNumber.value = page.props.flash.generated_protocol_number;
            }
            // Delay intencional de 1.5s para exibir a animação premium de "Gerando..."
            setTimeout(() => {
                activeProtocolTab.value = 'success';
            }, 1500);
        },
        onError: (errors) => {
            console.error("Errors when saving protocol:", errors);
            activeProtocolTab.value = 'edit'; // Volta se houver erro
        }
    });
};

// --- Edit Installment Logic ---
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
        case 'pending': return 'bg-brand-green';
        case 'overdue': return 'bg-yellow-500';
        case 'cancelled': return 'bg-red-500';
    }
};

// --- Protocol Accordion Logic ---
const expandedProtocols = ref([]);
const isTimelineExpanded = ref(false); // Controls the main Timeline visibility

// --- Protocol Replies Logic ---
const activeReplyProtocolId = ref(null);
const protocolReplyForm = useForm({
    message: '',
    attachments: [],
    status: ''
});

const openReplyForm = (protocolId) => {
    activeReplyProtocolId.value = activeReplyProtocolId.value === protocolId ? null : protocolId;
    protocolReplyForm.reset();
};

const handleReplyAttachmentChange = (e) => {
    protocolReplyForm.attachments = Array.from(e.target.files);
};

const submitProtocolReply = (protocolId) => {
    protocolReplyForm.post(route('sales.protocols.replies.store', protocolId), {
        preserveScroll: true,
        onSuccess: () => {
            activeReplyProtocolId.value = null;
            protocolReplyForm.reset();
        }
    });
};

const toggleProtocol = (id) => {
    const index = expandedProtocols.value.indexOf(id);
    if (index === -1) {
        expandedProtocols.value.push(id);
    } else {
        expandedProtocols.value.splice(index, 1);
    }
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date);
};

const getPriorityLabel = (priority) => {
    const labels = {
        'baixa': 'Baixa',
        'media': 'Média',
        'alta': 'Alta',
        'urgente': 'Urgente'
    };
    return labels[priority] || priority;
};

const getPriorityColor = (priority) => {
    const colors = {
        'baixa': 'border-green-200 text-green-600 bg-green-50 dark:border-green-500/30 dark:text-green-400 dark:bg-green-500/10',
        'media': 'border-brand-green text-brand-green bg-brand-green dark:border-brand-green/30 dark:text-brand-green dark:bg-brand-green/10',
        'alta': 'border-orange-200 text-orange-600 bg-orange-50 dark:border-orange-500/30 dark:text-orange-400 dark:bg-orange-500/10',
        'urgente': 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30'
    };
    return colors[priority] || 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-white/5 dark:text-gray-400 dark:border-slate-700';
};

const getProtocolStatusLabel = (status) => {
    const labels = {
        'aberto': 'Aberto',
        'em_andamento': 'Em Andamento',
        'fechado': 'Fechado'
    };
    return labels[status] || status;
};

const getProtocolStatusColor = (status) => {
    const colors = {
        'aberto': 'bg-brand-green text-brand-green border-brand-green dark:bg-brand-green/10 dark:text-brand-green dark:border-brand-green/30',
        'em_andamento': 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/30',
        'fechado': 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'
    };
    return colors[status] || 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-white/5 dark:text-gray-400 dark:border-slate-700';
};

const updateProtocolStatus = (protocolId, status) => {
    router.patch(route('sales.protocols.status.update', protocolId), {
        status: status
    }, {
        preserveScroll: true
    });
};

</script>

<template>
    <Head :title="'Contrato #' + (proposal?.contract_number || service.id)" />

    <AuthenticatedLayout>
        <!-- ============================================================ -->
        <!--  PAGE WRAPPER — Full width, padding lateral generoso          -->
        <!-- ============================================================ -->
        <div class="min-h-screen bg-slate-50 dark:bg-[#0f1219]">
            <div class="px-6 lg:px-10 py-8 space-y-6 max-w-[1600px] mx-auto animate-in">

                <!-- =========================================================== -->
                <!--  HERO HEADER                                                  -->
                <!-- =========================================================== -->
                <div class="relative rounded-[24px] bg-gradient-to-br from-slate-900 via-slate-900 to-slate-900 dark:from-[#0d1117] dark:via-slate-900/50 dark:to-[#0d1117] border border-brand-green/20 shadow-2xl shadow-brand-green/10">
                    <!-- Ambient glow -->
                    <div class="absolute inset-0 overflow-hidden rounded-[24px] pointer-events-none">
                        <div class="absolute top-0 left-0 w-96 h-96 bg-brand-green/10 rounded-full blur-[120px]"></div>
                        <div class="absolute bottom-0 right-0 w-64 h-64 bg-brand-green/10 rounded-full blur-[80px]"></div>
                    </div>
                    
                    <div class="relative z-20 p-8 lg:p-10">
                        <!-- Breadcrumb -->
                        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400/60 uppercase tracking-widest mb-6">
                            <Link :href="route('sales.atendimentos')" class="hover:text-slate-400 transition-colors">Sala de Vendas</Link>
                            <span class="text-white/20">/</span>
                            <span class="text-slate-400">Contrato #{{ proposal?.contract_number || 'S/N' }}</span>
                        </nav>

                        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-8">
                            <!-- Identity -->
                            <div class="space-y-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border"
                                        :class="proposal?.contract_number
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30'
                                            : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30'">
                                        {{ proposal?.contract_number ? '● Contrato Ativo' : '○ Sem Contrato' }}
                                    </span>
                                    <span class="text-white/30 text-xs">|</span>
                                    <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">{{ formatDate(service.date) }} às {{ service.time }}</span>
                                </div>

                                <h1 class="text-3xl lg:text-4xl font-bold text-white uppercase tracking-tight leading-none">
                                    {{ client?.nome }}
                                </h1>

                                <div class="flex flex-wrap items-center gap-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                                    <span v-if="client?.cpf">CPF: <span class="text-white/70 font-mono">{{ client.cpf }}</span></span>
                                    <span v-if="product">Produto: <span class="text-white/70">{{ product.name }}</span></span>
                                    <span v-if="proposal?.contract_number">Contrato: <span class="text-brand-green font-mono font-bold">{{ proposal.contract_number }}</span></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3 shrink-0">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="flex items-center gap-2.5 bg-brand-green hover:bg-brand-green/90 text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all shadow-lg shadow-brand-green/20 active:scale-95">
                                            Opções
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <button class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                            Visualizar Dados
                                        </button>
                                        <button v-if="can('pos_venda.protocolos.criar')" @click="openProtocolModal" class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                            Gerar Protocolo
                                        </button>
                                        <button class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                            Renegociar Contrato
                                        </button>
                                        <button class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                            Mudar Vencimento
                                        </button>
                                        <button class="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:bg-red-50 focus:outline-none dark:text-red-400 dark:hover:bg-red-900/20 dark:focus:bg-red-900/20 border-t border-gray-100 dark:border-gray-700">
                                            Cancelar Contrato
                                        </button>
                                    </template>
                                </Dropdown>
                                
                                <button @click="printDocument('sales.atendimentos.contrato.pdf')"
                                    class="flex items-center gap-2.5 bg-white text-slate-900 hover:bg-brand-green hover:text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all shadow-xl shadow-black/20 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Contrato
                                </button>
                                <button @click="printDocument('sales.atendimentos.proposta.pdf')"
                                    class="flex items-center gap-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border-transparent dark:border-slate-700 text-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Proposta
                                </button>
                                <button @click="printDocument('sales.atendimentos.checklist.pdf')"
                                    class="flex items-center gap-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border-transparent dark:border-slate-700 text-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Checklist
                                </button>
                                <button @click="printDocument('sales.atendimentos.ficha.pdf')"
                                    class="flex items-center gap-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border-transparent dark:border-slate-700 text-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all active:scale-95">
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
                    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[20px] p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <!-- Decorative icon -->
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-brand-green/10 dark:bg-brand-green/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-brand-green dark:text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">Valor do Contrato</p>
                            </div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white font-mono leading-none">{{ formatCurrency(totalValue) }}</p>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md bg-brand-green dark:bg-brand-green/10 text-brand-green dark:text-brand-green text-[9px] font-bold uppercase tracking-widest">{{ totalPoints.toLocaleString() }} pts</span>
                                <span class="text-[10px] text-slate-500 font-medium">{{ product?.duration || 'S/D' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Valor Pago -->
                    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[20px] p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-500/80 uppercase tracking-widest">Total Pago</p>
                            </div>
                            <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-400 font-mono leading-none">{{ formatCurrency(totalPaid) }}</p>
                            <div class="mt-3 space-y-1.5">
                                <div class="w-full h-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-full overflow-hidden">
                                    <div :style="{ width: percentPaid + '%' }"
                                        class="h-full bg-brand-green rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(16,185,129,0.4)]">
                                    </div>
                                </div>
                                <p class="text-[10px] text-emerald-600/70 dark:text-emerald-500/60 font-bold">{{ percentPaid }}% quitado</p>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo em Aberto -->
                    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[20px] p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-orange-500/10 dark:bg-orange-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-[10px] font-bold text-orange-600 dark:text-orange-500/80 uppercase tracking-widest">Saldo em Aberto</p>
                            </div>
                            <p class="text-3xl font-bold text-orange-700 dark:text-orange-400 font-mono leading-none">{{ formatCurrency(amountOpen) }}</p>
                            <div class="mt-3">
                                <span class="px-2 py-0.5 rounded-md bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 text-[9px] font-bold uppercase tracking-widest">{{ (100 - parseFloat(percentPaid)).toFixed(1) }}% pendente</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pontos Disponíveis -->
                    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[20px] p-6 overflow-hidden group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 text-slate-100 dark:text-white/5 pointer-events-none">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                </div>
                                <p class="text-[10px] font-bold text-amber-600 dark:text-amber-500/80 uppercase tracking-widest">Pontos Disponíveis</p>
                            </div>
                            <p class="text-3xl font-bold text-amber-700 dark:text-amber-400 leading-none">
                                {{ availablePoints.toLocaleString() }} <span class="text-lg font-bold opacity-50">pts</span>
                            </p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[9px] font-bold uppercase tracking-widest">{{ releasedPoints.toLocaleString() }} liberados</span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-gray-500 text-[9px] font-bold uppercase tracking-widest">{{ utilizedPoints.toLocaleString() }} usados</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  INFO ROW — Vigência + Cliente em 2 colunas                   -->
                <!-- =========================================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Vigência -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700 rounded-[20px] p-6 hover:shadow-lg transition-all duration-300">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-5 flex items-center gap-2.5">
                            <span class="w-5 h-5 rounded-lg bg-brand-green/10 dark:bg-brand-green/20 flex items-center justify-center">
                                <svg class="w-3 h-3 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/></svg>
                            </span>
                            Vigência do Contrato
                        </h3>
                        <div class="grid grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Duração</p>
                                <p class="text-lg font-bold text-slate-900 dark:text-white leading-none">{{ product?.duration || 'N/A' }}</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Expira em</p>
                                <p class="text-lg font-bold text-slate-900 dark:text-white leading-none">{{ usableUntilStr }}</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Restam</p>
                                <p class="text-lg font-bold text-brand-green dark:text-brand-green leading-none">{{ yearsRemainingVal }} <span class="text-sm font-bold opacity-60">anos</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Dados do Titular -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700 rounded-[20px] p-6 hover:shadow-lg transition-all duration-300">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-5 flex items-center gap-2.5">
                            <span class="w-5 h-5 rounded-lg bg-brand-green/10 dark:bg-brand-green/20 flex items-center justify-center">
                                <svg class="w-3 h-3 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            Dados do Titular
                        </h3>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Celular</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ client?.celular1 || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Estado Civil</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white capitalize">{{ client?.estado_civil || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Cidade / UF</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ [client?.address?.cidade, client?.address?.estado].filter(Boolean).join(' / ') || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">E-mail</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ client?.email || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  HISTÓRICO DE PROTOCOLOS (ACCORDION)                          -->
                <!-- =========================================================== -->
                <div v-if="service.protocols && service.protocols.length > 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] hover:shadow-lg transition-all duration-300 mb-6">
                    <!-- Header -->
                    <button @click="isTimelineExpanded = !isTimelineExpanded" class="w-full px-6 py-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30 hover:bg-slate-100 dark:hover:bg-white/[0.02] rounded-t-2xl transition-colors focus:outline-none">
                        <div class="flex items-center gap-4">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Histórico de Protocolos
                            </h3>
                            <span class="px-2 py-0.5 rounded-full bg-brand-green/10 text-brand-green dark:text-brand-green text-[10px] font-bold uppercase tracking-widest">
                                {{ service.protocols.length }} Registros
                            </span>
                        </div>
                        <div class="text-slate-500 transition-transform duration-300" :class="{ 'rotate-180': isTimelineExpanded }">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <!-- Protocol Timeline -->
                    <div v-show="isTimelineExpanded" class="p-6">
                        <div class="relative border-l-2 border-slate-100 dark:border-slate-800 ml-4 sm:ml-6 py-2 space-y-10">
                            <div v-for="protocol in [...service.protocols].sort((a,b) => new Date(b.created_at) - new Date(a.created_at))" :key="protocol.id" class="relative pl-8 sm:pl-10 group/timeline">
                                
                                <!-- Timeline Node (Avatar) -->
                                <div class="absolute -left-[17px] top-1">
                                    <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 border-4 border-white dark:border-[#141925] shadow-sm flex items-center justify-center ring-1 ring-slate-200 dark:ring-white/10 z-10 overflow-hidden group-hover/timeline:scale-110 transition-transform duration-300">
                                        <img v-if="protocol.user?.profile_photo_url" :src="protocol.user.profile_photo_url" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-brand-green dark:bg-brand-green/20 text-brand-green dark:text-brand-green flex items-center justify-center text-[10px] font-bold uppercase">
                                            {{ protocol.user?.name?.substring(0, 2) || 'S' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Content -->
                                <div class="bg-white dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-800 rounded-[20px] p-5 shadow-sm hover:shadow-md hover:border-brand-green dark:hover:border-brand-green/30 transition-all duration-300">
                                    <!-- Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-3">
                                                <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                    {{ protocol.subject }}
                                                </h4>
                                                <span class="text-xs font-mono font-bold text-brand-green dark:text-brand-green bg-brand-green dark:bg-brand-green/10 px-2 py-0.5 rounded-md">
                                                    {{ protocol.protocol_number }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] font-medium text-slate-500 dark:text-gray-500 uppercase tracking-widest flex items-center gap-2">
                                                <span>Adicionado por <strong class="text-slate-700 dark:text-gray-300">{{ protocol.user?.name || 'Sistema' }}</strong></span>
                                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-gray-600"></span>
                                                <span>{{ formatDateTime(protocol.created_at) }}</span>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg border text-[9px] font-bold uppercase tracking-widest shadow-sm" :class="getPriorityColor(protocol.priority)">
                                                {{ getPriorityLabel(protocol.priority) }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg border text-[9px] font-bold uppercase tracking-widest shadow-sm" :class="getProtocolStatusColor(protocol.status || 'aberto')">
                                                {{ getProtocolStatusLabel(protocol.status || 'aberto') }}
                                            </span>

                                            <!-- Ações do Protocolo -->
                                            <Dropdown v-if="protocol.status !== 'fechado'" align="right" width="48">
                                                <template #trigger>
                                                    <button class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-gray-300 dark:hover:bg-white/5 transition-colors focus:outline-none">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                                    </button>
                                                </template>
                                                <template #content>
                                                    <div class="block px-4 py-2 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                                                        Alterar Status
                                                    </div>
                                                    <button @click="updateProtocolStatus(protocol.id, 'aberto')" class="w-full text-left block px-4 py-2 text-sm leading-5 text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-white/5 focus:outline-none transition duration-150 ease-in-out font-medium">
                                                        <span class="inline-block w-2 h-2 rounded-full bg-brand-green mr-2"></span> Aberto
                                                    </button>
                                                    <button @click="updateProtocolStatus(protocol.id, 'em_andamento')" class="w-full text-left block px-4 py-2 text-sm leading-5 text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-white/5 focus:outline-none transition duration-150 ease-in-out font-medium">
                                                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500 mr-2"></span> Em Andamento
                                                    </button>
                                                    <button @click="updateProtocolStatus(protocol.id, 'fechado')" class="w-full text-left block px-4 py-2 text-sm leading-5 text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-white/5 focus:outline-none transition duration-150 ease-in-out font-medium">
                                                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span> Fechado
                                                    </button>
                                                </template>
                                            </Dropdown>
                                        </div>
                                    </div>

                                    <!-- Message Body -->
                                    <div class="prose prose-sm dark:prose-invert max-w-none text-sm text-slate-600 dark:text-gray-400 leading-relaxed mb-5" v-html="protocol.message || '<p class=\'italic opacity-50\'>Nenhum detalhe adicional informado.</p>'">
                                    </div>

                                    <!-- Attachments -->
                                    <div v-if="protocol.attachments && protocol.attachments.length > 0" class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Anexos ({{ protocol.attachments.length }})
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <a v-for="(attachment, index) in protocol.attachments" :key="index" :href="attachment.url || attachment" target="_blank" class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-slate-950/20 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-brand-green dark:hover:border-brand-green/50 hover:bg-brand-green dark:hover:bg-brand-green/10 transition-colors group/attach">
                                                <svg class="w-3.5 h-3.5 text-brand-green group-hover/attach:text-brand-green dark:group-hover/attach:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                <span class="text-[10px] font-bold text-slate-600 dark:text-gray-300 truncate max-w-[150px]">Anexo {{ index + 1 }}</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Nested Replies Section -->
                                    <div v-if="protocol.replies && protocol.replies.length > 0" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            Interações ({{ protocol.replies.length }})
                                        </p>
                                        <div class="space-y-4">
                                            <div v-for="reply in protocol.replies" :key="reply.id" class="flex gap-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                                                <!-- Reply Avatar -->
                                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-green dark:bg-brand-green/20 flex items-center justify-center text-[8px] font-bold text-brand-green dark:text-brand-green uppercase overflow-hidden">
                                                    <img v-if="reply.user?.profile_photo_url" :src="reply.user.profile_photo_url" class="w-full h-full object-cover">
                                                    <span v-else>{{ reply.user?.name?.substring(0, 2) || 'S' }}</span>
                                                </div>
                                                <!-- Reply Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ reply.user?.name || 'Sistema' }}</span>
                                                        <span class="text-[9px] font-medium text-slate-500 uppercase tracking-widest">{{ formatDateTime(reply.created_at) }}</span>
                                                    </div>
                                                    <div class="text-xs text-slate-600 dark:text-gray-400 whitespace-pre-wrap leading-relaxed" v-html="reply.message"></div>
                                                    <!-- Reply Attachments -->
                                                    <div v-if="reply.attachments && reply.attachments.length > 0" class="mt-2 flex flex-wrap gap-2">
                                                        <a v-for="(att, i) in reply.attachments" :key="i" :href="att" target="_blank" class="flex items-center gap-1 text-[9px] font-bold text-brand-green hover:text-brand-green dark:hover:text-brand-green">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            Anexo {{ i + 1 }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reply Actions -->
                                    <div v-if="protocol.status !== 'fechado'" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <button v-if="activeReplyProtocolId !== protocol.id" @click="openReplyForm(protocol.id)" class="text-xs font-bold text-brand-green hover:text-brand-green dark:hover:text-brand-green flex items-center gap-2 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            Responder Solicitação
                                        </button>

                                        <!-- Reply Form -->
                                        <form v-if="activeReplyProtocolId === protocol.id" @submit.prevent="submitProtocolReply(protocol.id)" class="space-y-3 bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-brand-green dark:border-brand-green/20">
                                            <textarea v-model="protocolReplyForm.message" rows="3" class="w-full text-sm border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-indigo-500 focus:border-brand-green transition-colors" placeholder="Escreva sua resposta ou resolução aqui..." required></textarea>
                                            
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                <div class="flex items-center gap-3">
                                                    <input type="file" multiple @change="handleReplyAttachmentChange" class="block w-full text-xs text-slate-500 dark:text-gray-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 dark:file:bg-white/10 dark:file:text-white dark:hover:file:bg-white/20 transition-colors" />
                                                    
                                                    <select v-model="protocolReplyForm.status" class="text-xs border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-brand-green">
                                                        <option value="">Manter Status</option>
                                                        <option value="em_andamento">Em Andamento</option>
                                                        <option value="fechado">Resolver (Fechado)</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="flex items-center gap-2">
                                                    <button type="button" @click="activeReplyProtocolId = null" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit" :disabled="protocolReplyForm.processing" class="px-4 py-1.5 bg-brand-green hover:bg-brand-green text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-2 disabled:opacity-50">
                                                        <svg v-if="protocolReplyForm.processing" class="animate-spin w-3 h-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        Enviar
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================================== -->
                <!--  EXTRATO FINANCEIRO — Tabela Principal                        -->
                <!-- =========================================================== -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] hover:shadow-lg transition-all duration-300">
                    <!-- Header da tabela -->
                    <div class="px-6 py-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between flex-wrap gap-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-t-2xl">
                        <div class="flex items-center gap-4">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Extrato Financeiro
                            </h3>
                            <!-- Legenda de Status -->
                            <div class="hidden sm:flex items-center gap-3 border-l border-slate-200 dark:border-slate-700 pl-4">
                                <div v-for="(cfg, key) in { pending: { label: 'A Receber', color: 'bg-brand-green' }, paid: { label: 'Baixado', color: 'bg-emerald-500' }, overdue: { label: 'Inadimplente', color: 'bg-orange-500' }, cancelled: { label: 'Cancelado', color: 'bg-red-500' } }" :key="key" class="flex items-center gap-1.5">
                                    <div :class="cfg.color" class="w-2 h-2 rounded-full"></div>
                                    <span class="text-[9px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">{{ cfg.label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button v-if="proposal" @click="isEditProposalModalOpen = true"
                                class="flex items-center gap-2 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-700 dark:text-emerald-400 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all border border-emerald-500/20 dark:border-emerald-500/30">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Editar Proposta
                            </button>
                            <button v-if="proposal" @click="openCreateModal"
                                class="flex items-center gap-2 bg-brand-green hover:bg-brand-green/90 text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all shadow-md shadow-brand-green/20 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Nova Parcela
                            </button>
                        </div>
                    </div>

                    <div class="p-0">
                        <div v-for="group in groupedBills" :key="group.key" class="border-b border-slate-200/80 dark:border-slate-800 last:border-0">
                            <!-- Cabeçalho do grupo -->
                            <div class="px-6 py-3.5 bg-slate-100/50 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center gap-3">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-green"></span>
                                <h4 class="text-[10px] font-bold text-slate-700 dark:text-gray-300 uppercase tracking-widest">{{ group.label }}</h4>
                                <span class="ml-auto text-[9px] px-2 py-0.5 rounded bg-white dark:bg-white/5 border border-slate-200 dark:border-slate-700 font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">{{ group.items.length }} parcela{{ group.items.length > 1 ? 's' : '' }}</span>
                            </div>

                            <!-- Tabela -->
                            <div>
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/50">
                                            <th class="px-6 py-4 w-10 text-center">
                                                <span class="sr-only">Seleção</span>
                                            </th>
                                            <th class="px-4 py-4 text-[9px] font-bold text-slate-500 uppercase tracking-widest">Parcela</th>
                                            <th class="px-4 py-4 text-[9px] font-bold text-slate-500 uppercase tracking-widest">Vencimento</th>
                                            <th class="px-4 py-4 text-[9px] font-bold text-slate-500 uppercase tracking-widest hidden md:table-cell">Data Pagto</th>
                                            <th class="px-4 py-4 text-[9px] font-bold text-slate-500 uppercase tracking-widest hidden lg:table-cell">Forma</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-bold text-slate-500 uppercase tracking-widest">Valor</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-bold text-slate-500 uppercase tracking-widest hidden xl:table-cell">Juros</th>
                                            <th class="px-4 py-4 text-right text-[9px] font-bold text-slate-500 uppercase tracking-widest">Pago</th>
                                            <th class="px-4 py-4 text-center text-[9px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                            <th class="px-6 py-4 text-center text-[9px] font-bold text-slate-500 uppercase tracking-widest">Ações</th>
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
                                                    class="w-4 h-4 rounded border-slate-300 dark:border-white/20 bg-transparent text-brand-green focus:ring-indigo-500/30 cursor-pointer transition-colors"
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
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-slate-700 text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:text-gray-400">
                                                    {{ bill.payment_method }}
                                                </span>
                                            </td>

                                            <!-- Valor -->
                                            <td class="px-4 py-4.5 text-right">
                                                <span class="text-[15px] font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(bill.amount) }}</span>
                                            </td>

                                            <!-- Juros -->
                                            <td class="px-4 py-4.5 text-right hidden xl:table-cell">
                                                <span class="text-sm font-mono font-bold" :class="bill.interest_amount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500'">
                                                    {{ bill.interest_amount > 0 ? formatCurrency(bill.interest_amount) : '—' }}
                                                </span>
                                            </td>

                                            <!-- Valor Pago -->
                                            <td class="px-4 py-4.5 text-right">
                                                <span class="text-[15px] font-bold font-mono" :class="bill.paid_amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'">
                                                    {{ bill.paid_amount > 0 ? formatCurrency(bill.paid_amount) : '—' }}
                                                </span>
                                            </td>

                                            <!-- Status Badge -->
                                            <td class="px-4 py-4.5 text-center">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded border text-[9px] font-bold uppercase tracking-widest"
                                                    :class="{
                                                        'bg-brand-green dark:bg-brand-green/10 text-brand-green dark:text-brand-green border-brand-green dark:border-brand-green/20': bill.status === 'pending',
                                                        'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20': bill.status === 'paid',
                                                        'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/20': bill.status === 'overdue',
                                                        'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border-red-200 dark:border-red-500/20': bill.status === 'cancelled',
                                                    }">
                                                    <span class="w-1.5 h-1.5 rounded-full"
                                                        :class="{
                                                            'bg-brand-green': bill.status === 'pending',
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
                                                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-white/5 border border-slate-200 dark:border-slate-700 shadow-sm text-slate-500 dark:text-gray-400 hover:text-brand-green dark:hover:text-brand-green hover:border-brand-green dark:hover:border-brand-green/30 hover:bg-brand-green dark:hover:bg-brand-green/10 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                                    </button>
                                                    <div class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-[#1a202c] border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl py-1.5 z-50 opacity-0 invisible group-hover/kebab:opacity-100 group-hover/kebab:visible transition-all duration-200 translate-y-1 group-hover/kebab:translate-y-0">
                                                        <button @click="openViewModal(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-gray-300 hover:text-brand-green dark:hover:text-brand-green hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            Visualizar
                                                        </button>
                                                        <button @click="openEditModal(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-white/5 transition-colors border-t border-slate-100 dark:border-slate-800">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                            Editar
                                                        </button>
                                                        <button @click="deleteBill(bill)" class="w-full flex items-center gap-3 px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors border-t border-slate-100 dark:border-slate-800">
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
                            <div class="w-16 h-16 rounded-[20px] bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-slate-700 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">Nenhum extrato gerado</p>
                            <p class="text-xs text-slate-500 mt-2 font-medium">Crie a proposta para gerar as parcelas automaticamente</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Create Installment Modal -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Adicionar Nova Parcela
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Financeiro</p>
                        </div>
                    </div>
                    <button @click="closeCreateModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Categoria</label>
                            <select v-model="createForm.category" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                                <option value="taxa_contrato">Taxa de Contrato</option>
                                <option value="entrada">Entrada</option>
                                <option value="saldo">Parcelas do Saldo</option>
                                <option value="taxa_manutencao">Taxa de Manutenção</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Forma de Pgto</label>
                            <select v-model="createForm.payment_method" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Original</label>
                            <input :value="formatCurrencyInput(createForm.amount)" @input="e => updateCurrencyInput(e, createForm, 'amount')" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vencimento</label>
                            <input v-model="createForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</label>
                            <select v-model="createForm.status" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                                <option value="pending">A Receber</option>
                                <option value="paid">Baixado</option>
                                <option value="overdue">Inadimplente</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        <div class="space-y-1" v-if="createForm.status === 'paid'">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data Pago</label>
                            <input v-model="createForm.paid_at" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                        <div class="space-y-1" v-if="createForm.status === 'paid'">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Pago</label>
                            <input :value="formatCurrencyInput(createForm.paid_amount)" @input="e => updateCurrencyInput(e, createForm, 'paid_amount')" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Observações Internas</label>
                        <textarea v-model="createForm.observations" rows="2" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeCreateModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="createForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            {{ createForm.processing ? 'Adicionando...' : 'Adicionar Parcela' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Installment Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Editar Parcela
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Financeiro</p>
                        </div>
                    </div>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vencimento</label>
                            <input v-model="editForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data de Pago</label>
                            <input v-model="editForm.paid_at" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Original</label>
                            <input :value="formatCurrencyInput(editForm.amount)" @input="e => updateCurrencyInput(e, editForm, 'amount')" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Forma de Pgto</label>
                            <select v-model="editForm.payment_method" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Juros/Mora</label>
                            <input :value="formatCurrencyInput(editForm.interest_amount)" @input="e => updateCurrencyInput(e, editForm, 'interest_amount')" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                        <div class="space-y-1" v-if="editForm.status === 'paid' || editForm.paid_amount > 0">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor Pago</label>
                            <input :value="formatCurrencyInput(editForm.paid_amount)" @input="e => updateCurrencyInput(e, editForm, 'paid_amount')" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Observações Internas</label>
                        <textarea v-model="editForm.observations" rows="3" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20 resize-none"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</label>
                        <select v-model="editForm.status" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20">
                            <option value="pending">A Receber</option>
                            <option value="paid">Baixado</option>
                            <option value="overdue">Inadimplente</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeEditModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            {{ editForm.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Proposal Modal -->
        <ProposalFormModal 
            :show="isEditProposalModalOpen"
            :service="service"
            :force-edit-mode="true"
            @close="isEditProposalModalOpen = false"
        />

        <!-- Renegotiation/BulkPay Floating Action Bar -->
        <div v-show="selectedBills.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-700 px-6 py-3 rounded-[20px] shadow-[0_0_40px_rgba(34,197,94,0.15)] z-40 flex items-center gap-6 animate-in slide-in-from-bottom-5">
            <span class="text-xs font-bold text-brand-green dark:text-brand-green uppercase tracking-widest">{{ selectedBills.length }} Selecionadas</span>
            <div class="flex gap-2">
                <button v-if="selectedBills.length > 1" @click="openRenegotiateModal" class="px-5 py-2.5 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[10px] font-bold uppercase tracking-[0.2em] shadow-lg shadow-brand-green/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Renegociar
                </button>
                <button @click="openBulkPayModal" class="px-5 py-2.5 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[10px] font-bold uppercase tracking-[0.2em] shadow-lg shadow-brand-green/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Baixar Selecionadas
                </button>
            </div>
        </div>

        <!-- View Installment Modal -->
        <Modal :show="isViewModalOpen" @close="closeViewModal" maxWidth="lg">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Visualizar Parcela
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Financeiro</p>
                        </div>
                    </div>
                    <button @click="closeViewModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <div v-if="viewBill" class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <!-- Head -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Categoria</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ viewBill.category?.replace('_', ' ') }}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Forma de Pgto.</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ viewBill.payment_method }}</p>
                        </div>
                    </div>

                    <!-- Financial -->
                    <div class="bg-brand-green/5 dark:bg-brand-green/10 border border-brand-green/10 dark:border-brand-green/20 p-4 rounded-xl grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Valor Original</p>
                            <p class="text-sm font-bold text-brand-green">R$ {{ Number(viewBill.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div v-if="viewBill.interest_amount > 0">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Juros / Mora</p>
                            <p class="text-sm font-bold text-amber-500">+ R$ {{ Number(viewBill.interest_amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div v-if="viewBill.paid_amount > 0 || viewBill.status === 'paid'">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Valor Pago</p>
                            <p class="text-sm font-bold text-emerald-500">R$ {{ Number(viewBill.paid_amount || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                    </div>

                    <!-- Dates & Status -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-3 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Vencimento</p>
                            <p class="text-xs font-bold text-slate-900 dark:text-white">{{ formatDate(viewBill.due_date) }}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-3 rounded-xl" v-if="viewBill.paid_at">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data Pagamento</p>
                            <p class="text-xs font-bold text-slate-900 dark:text-white">{{ formatDate(viewBill.paid_at) }}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-3 rounded-xl">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status</p>
                            <div class="inline-flex items-center gap-1.5 px-2 py-1 mt-0.5 rounded-lg border border-slate-200 dark:border-slate-700" 
                                :class="{
                                    'bg-brand-green/10 text-brand-green': viewBill.status === 'pending',
                                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': viewBill.status === 'paid',
                                    'bg-amber-500/10 text-amber-600 dark:text-amber-400': viewBill.status === 'overdue',
                                    'bg-rose-500/10 text-rose-600 dark:text-rose-400': viewBill.status === 'cancelled'
                                }">
                                <span class="text-[10px] font-bold uppercase tracking-widest">
                                    {{ viewBill.status === 'pending' ? 'A Receber' : viewBill.status === 'paid' ? 'Baixado' : viewBill.status === 'overdue' ? 'Inadimplente' : 'Cancelado' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Observations -->
                    <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl relative">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Observações</p>
                        <p class="text-sm text-slate-700 dark:text-gray-300 leading-relaxed font-medium whitespace-pre-wrap">{{ viewBill.observations || 'Nenhuma observação registrada.' }}</p>
                    </div>
                    
                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button @click="closeViewModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Fechar
                        </button>
                        <button v-if="viewBill" @click="openEditModal(viewBill); isViewModalOpen = false;" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            Editar Parcela
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div v-if="billToDelete" class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-8 flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-rose-600 dark:text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight mb-2">Excluir Parcela?</h3>
                    <p class="text-sm text-slate-500 dark:text-gray-400 mb-8">
                        Você está prestes a excluir a parcela <strong class="text-slate-900 dark:text-white">#{{ billToDelete.installment_number }}</strong> no valor de <strong class="text-slate-900 dark:text-white">{{ formatCurrency(billToDelete.amount) }}</strong>. Esta ação é definitiva e irreversível.
                    </p>

                    <div class="flex gap-3 w-full">
                        <button @click="closeDeleteModal" :disabled="isDeleting" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button @click="executeDelete" :disabled="isDeleting" class="flex-1 py-3 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Renegotiate Modal -->
        <Modal :show="isRenegotiateModalOpen" @close="closeRenegotiateModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Renegociar Parcelas
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Financeiro</p>
                        </div>
                    </div>
                    <button @click="closeRenegotiateModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <form @submit.prevent="submitRenegotiate" class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl text-center">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Valor Total Agrupado</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">R$ {{ Number(renegotiateTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                        <p class="text-[10px] font-bold text-slate-500 mt-2">Corresponde a {{ selectedBills.length }} parcelas selecionadas</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nova Data de Vencimento</label>
                            <input v-model="renegotiateForm.due_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nova Forma de Pgto</label>
                            <select v-model="renegotiateForm.payment_method" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeRenegotiateModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="renegotiateForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Bulk Pay Modal -->
        <Modal :show="isBulkPayModalOpen" @close="closeBulkPayModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Baixa em Lote
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Financeiro</p>
                        </div>
                    </div>
                    <button @click="closeBulkPayModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <form @submit.prevent="submitBulkPay" class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl text-center">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Valor Total a Baixar</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">R$ {{ Number(bulkPayTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                        <p class="text-[10px] font-bold text-slate-500 mt-2">Corresponde a {{ selectedBills.length }} parcelas selecionadas</p>
                    </div>

                    <!-- Toggle Mode -->
                    <div class="bg-white dark:bg-slate-800/50 p-1 rounded-xl flex border border-slate-200 dark:border-slate-700">
                        <button type="button" @click="bulkPayForm.mode = 'single'" :class="bulkPayForm.mode === 'single' ? 'bg-slate-900 dark:bg-brand-green text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="flex-1 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                            Mesma Data
                        </button>
                        <button type="button" @click="bulkPayForm.mode = 'individual'" :class="bulkPayForm.mode === 'individual' ? 'bg-slate-900 dark:bg-brand-green text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="flex-1 py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all">
                            Datas Individuais
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4" v-if="bulkPayForm.mode === 'single'">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data do Pagamento</label>
                            <input v-model="bulkPayForm.paid_at" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Forma de Pagamento</label>
                            <select v-model="bulkPayForm.payment_method" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4" v-else>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Forma de Pagamento (Geral)</label>
                            <select v-model="bulkPayForm.payment_method" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20" required>
                                <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                        
                        <div class="max-h-[200px] overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                            <div v-for="billId in selectedBills" :key="billId" class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-3 rounded-xl flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase truncate">
                                        {{ bills.find(b => b.id === billId)?.category?.replace('_', ' ') }}
                                    </p>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                        Parcela #{{ bills.find(b => b.id === billId)?.installment_number }}
                                    </p>
                                </div>
                                <input v-model="bulkPayForm.bill_dates[billId]" type="date" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded text-xs text-slate-900 dark:text-white w-32 focus:ring-brand-green/20">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Observação (Opcional)</label>
                        <textarea v-model="bulkPayForm.observations" rows="2" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-brand-green/20 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeBulkPayModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="bulkPayForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Protocol Modal -->
        <Modal :show="isProtocolModalOpen" @close="closeProtocolModal" :maxWidth="(activeProtocolTab === 'loading' || activeProtocolTab === 'success') ? 'sm' : '3xl'">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight leading-none">
                                Gerar Novo Protocolo
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Administrativo</p>
                        </div>
                    </div>
                    <button @click="closeProtocolModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>

                <div class="p-6 md:p-8 space-y-6 bg-slate-50 dark:bg-transparent flex-1 overflow-y-auto">
                    <!-- Success View -->
                    <div v-if="activeProtocolTab === 'success'" class="flex flex-col items-center justify-center py-10 space-y-6 text-center">
                        <div class="w-24 h-24 bg-brand-green/10 rounded-full flex items-center justify-center mb-2">
                            <svg class="h-12 w-12 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-widest">Protocolo Gerado com Sucesso!</h3>
                        <p class="text-sm text-slate-500">O número do seu protocolo único é:</p>
                        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 px-6 py-3 rounded-xl flex items-center gap-4 justify-center">
                            <span class="text-xl font-medium text-slate-900 dark:text-white tracking-[0.1em]">{{ generatedProtocolNumber || '...' }}</span>
                            <button @click="copyProtocol" class="text-brand-green p-1.5 rounded-md hover:bg-brand-green/10">
                                <svg v-if="copied" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>
                        </div>
                        <div class="pt-6">
                            <button type="button" @click="closeProtocolModal" class="px-8 py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors w-full">
                                Concluir e Fechar
                            </button>
                        </div>
                    </div>

                    <!-- Loading View -->
                    <div v-else-if="activeProtocolTab === 'loading'" class="flex flex-col items-center justify-center py-16 space-y-6 text-center">
                        <div class="relative w-24 h-24 flex items-center justify-center">
                            <svg class="animate-spin absolute w-full h-full text-slate-200 dark:text-slate-800" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                            <svg class="animate-spin absolute w-16 h-16 text-brand-green" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-widest">Gerando Protocolo...</h3>
                            <p class="text-sm text-slate-500">Por favor aguarde, sincronizando dados no sistema.</p>
                        </div>
                    </div>

                    <!-- Form View -->
                    <div v-else>
                        <div class="flex gap-2 mb-6 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <button type="button" @click="activeProtocolTab = 'edit'" :class="activeProtocolTab === 'edit' ? 'text-brand-green border-b-2 border-brand-green' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="pb-2 text-xs font-bold uppercase tracking-widest transition-colors">
                                Editar Conteúdo
                            </button>
                            <button type="button" @click="activeProtocolTab = 'preview'" :class="activeProtocolTab === 'preview' ? 'text-brand-green border-b-2 border-brand-green' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="pb-2 text-xs font-bold uppercase tracking-widest transition-colors">
                                Visualizar Resumo
                            </button>
                        </div>

                        <form v-if="activeProtocolTab === 'edit'" @submit.prevent="submitProtocol" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Assunto do Protocolo</label>
                                    <input v-model="protocolForm.subject" type="text" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20" required placeholder="Ex: Solicitação de Cancelamento">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Prioridade</label>
                                    <select v-model="protocolForm.priority" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20">
                                        <option value="baixa">Baixa</option>
                                        <option value="media">Média</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Mensagem ou Histórico</label>
                                <textarea v-model="protocolForm.message" rows="5" class="w-full bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-brand-green/20 resize-none" placeholder="Descreva os detalhes deste protocolo..."></textarea>
                            </div>

                            <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                                <button type="button" @click="closeProtocolModal" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="protocolForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                                    Salvar Protocolo
                                </button>
                            </div>
                        </form>

                        <div v-else class="space-y-6">
                            <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-6 rounded-xl">
                                <div class="flex justify-between items-start border-b border-slate-200 dark:border-slate-700 pb-4 mb-4">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase">{{ protocolForm.subject }}</h4>
                                        <p class="text-[10px] text-slate-500 mt-1">Prioridade: <span class="uppercase font-bold">{{ protocolForm.priority }}</span></p>
                                    </div>
                                </div>
                                <div class="text-sm text-slate-700 dark:text-gray-300 min-h-[100px] whitespace-pre-wrap">{{ protocolForm.message || 'Nenhuma mensagem.' }}</div>
                            </div>

                            <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                                <button type="button" @click="activeProtocolTab = 'edit'" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                    Voltar para Edição
                                </button>
                                <button type="button" @click="submitProtocol" :disabled="protocolForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-xl text-[11px] font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                                    Confirmar e Salvar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Edit Proposal Modal -->
        <ProposalFormModal 
            :show="isEditProposalModalOpen"
            :service="service"
            :force-edit-mode="true"
            @close="isEditProposalModalOpen = false"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-in { animation: fade-in 0.5s ease-out both; }
input, select { color-scheme: dark; }
</style>
