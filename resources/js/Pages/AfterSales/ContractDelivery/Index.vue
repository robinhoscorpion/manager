<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    contracts: Array,
    metrics: Object,
    filters: Object,
});

const pageMetrics = computed(() => props.metrics || { total: 0, pending: 0, delivered: 0, signed: 0 });
const contracts = computed(() => props.contracts || []);

const search = ref('');
const statusFilter = ref('');
const today = new Date().toISOString().split('T')[0];
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const fetchFilteredData = () => {
    router.get(route('after-sales.contract-delivery.index'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearDates = () => {
    startDate.value = '';
    endDate.value = '';
    fetchFilteredData();
};

const filteredContracts = computed(() => {
    return contracts.value.filter(contract => {
        const searchLower = search.value.toLowerCase();
        const matchSearch = (contract.name || '').toLowerCase().includes(searchLower) || 
                            (contract.email || '').toLowerCase().includes(searchLower) ||
                            (contract.contract || '').toLowerCase().includes(searchLower);
        const matchStatus = statusFilter.value === '' || contract.status === statusFilter.value;
        return matchSearch && matchStatus;
    });
});

// Modal State
const showDeliveryModal = ref(false);
const showUploadModal = ref(false);
const selectedContract = ref(null);
const deliveryMethod = ref('whatsapp');
const isProcessing = ref(false);

const uploadForm = useForm({
    contract_file: null
});

// Helper Functions
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    if (dateStr.includes('/')) return dateStr;
    try {
        return new Intl.DateTimeFormat('pt-BR').format(new Date(dateStr));
    } catch (e) {
        return '-';
    }
};

const getStatusBadge = (status, method, signature) => {
    if (signature === 'signed') {
        return { label: 'Assinado', color: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20' };
    }
    if (status === 'delivered') {
        let label = 'Entregue';
        if (method === 'whatsapp') label = 'Entregue via Zap';
        if (method === 'email') label = 'Entregue via E-mail';
        if (method === 'physical') label = 'Entregue Físico';
        return { label, color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' };
    }
    return { label: 'Pendente', color: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20' };
};

// Actions
const openDeliveryModal = (contract) => {
    selectedContract.value = contract;
    deliveryMethod.value = 'whatsapp';
    showDeliveryModal.value = true;
};

const confirmDelivery = () => {
    isProcessing.value = true;
    router.patch(route('after-sales.contract-delivery.status.update', selectedContract.value.id), {
        method: deliveryMethod.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeliveryModal.value = false;
            isProcessing.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
};

const openUploadModal = (contract) => {
    selectedContract.value = contract;
    uploadForm.reset();
    showUploadModal.value = true;
};

const submitUpload = () => {
    uploadForm.post(route('after-sales.contract-delivery.upload', selectedContract.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Entrega de Contrato - Pós-venda" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-green/10 text-brand-green flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-black text-xl text-slate-800 dark:text-white uppercase tracking-widest">
                        Entrega de Contrato
                    </h2>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Gestão de vias físicas e digitais</p>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto space-y-6">
            
            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total de Contratos</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ pageMetrics.total }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Pendentes de Entrega</p>
                        <h3 class="text-2xl font-black text-yellow-500">{{ pageMetrics.pending }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500/10 text-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Entregues (Aguard. Assinatura)</p>
                        <h3 class="text-2xl font-black text-brand-green">{{ pageMetrics.delivered }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-brand-green/10 text-brand-green rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Assinados</p>
                        <h3 class="text-2xl font-black text-indigo-500">{{ pageMetrics.signed }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-indigo-500/10 text-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                
                <!-- Filter Bar -->
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-3 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <!-- Search -->
                        <div class="relative w-full sm:w-56">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Buscar cliente ou contrato..." class="w-full pl-7 pr-2 h-8 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-brand-green/20 placeholder-slate-400">
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="flex items-center h-8 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 gap-1 shrink-0">
                            <input v-model="startDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <span class="text-slate-400 text-[9px] uppercase font-bold">até</span>
                            <input v-model="endDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <button v-if="startDate || endDate" @click="clearDates" class="ml-1 text-slate-400 hover:text-red-500 flex items-center" title="Limpar Datas">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Search Button -->
                        <button @click="fetchFilteredData" class="h-8 shrink-0 bg-brand-green text-white px-4 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-brand-green/90 transition-colors flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Buscar
                        </button>
                    </div>
                    
                    <!-- Status Filters -->
                    <div class="flex flex-wrap items-center gap-1.5 w-full xl:w-auto">
                        <button @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Todos
                        </button>
                        <button @click="statusFilter = 'pending'" :class="statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Pendentes
                        </button>
                        <button @click="statusFilter = 'delivered'" :class="statusFilter === 'delivered' ? 'bg-brand-green text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Entregues
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cliente</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Contato</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Produto/Serviço</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Data Aprovação</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="contract in filteredContracts" :key="contract.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-xs">
                                            {{ contract.name.charAt(0) }}
                                        </div>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ contract.name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-600 dark:text-slate-300">{{ contract.formatted_phone }}</span>
                                        <span class="text-[9px] text-slate-500">{{ contract.email }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ contract.product }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Nº {{ contract.contract }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ formatDate(contract.date) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span :class="getStatusBadge(contract.status, contract.method, contract.signature_status).color" class="inline-flex px-2 py-0.5 rounded-md border text-[9px] font-bold uppercase tracking-widest">
                                            {{ getStatusBadge(contract.status, contract.method, contract.signature_status).label }}
                                        </span>
                                        <span v-if="contract.signature_status === 'signed' && contract.signed_at" class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ contract.signed_at }}</span>
                                        <span v-else-if="contract.delivered_at" class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ contract.delivered_at }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button v-if="contract.status === 'pending'" @click="openDeliveryModal(contract)" class="px-3 py-1.5 rounded-md bg-brand-green text-white hover:bg-brand-green/90 transition-colors text-[9px] font-bold uppercase tracking-widest" title="Registrar Entrega">
                                            Entregar
                                        </button>
                                        <button v-else-if="contract.status === 'delivered' && contract.signature_status === 'pending'" @click="openUploadModal(contract)" class="px-3 py-1.5 rounded-md bg-indigo-500 text-white hover:bg-indigo-600 transition-colors text-[9px] font-bold uppercase tracking-widest" title="Anexar Contrato Assinado">
                                            Anexar
                                        </button>
                                        <a v-else-if="contract.signature_status === 'signed' && contract.has_file" :href="route('after-sales.contract-delivery.download', contract.id)" target="_blank" class="px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-[9px] font-bold uppercase tracking-widest flex items-center gap-1" title="Baixar Arquivo">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Baixar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredContracts.length === 0">
                                <td colspan="6" class="px-3 py-8 text-center text-slate-500 text-xs">
                                    Nenhum contrato encontrado com os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Register Delivery -->
        <Modal :show="showDeliveryModal" @close="showDeliveryModal = false" maxWidth="sm">
            <div class="p-6 bg-white dark:bg-[#0f1219]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-widest">Registrar Entrega</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Contrato {{ selectedContract?.contract }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                        Selecione a forma como o contrato foi entregue ao cliente <strong class="text-slate-900 dark:text-white">{{ selectedContract?.name }}</strong>:
                    </p>

                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-colors"
                            :class="deliveryMethod === 'whatsapp' ? 'border-brand-green bg-brand-green/5' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="deliveryMethod" value="whatsapp" class="text-brand-green focus:ring-brand-green border-slate-300">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Via WhatsApp</span>
                                    <span class="text-[10px] text-slate-500">Documento PDF enviado digitalmente via mensageiro.</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </label>

                        <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-colors"
                            :class="deliveryMethod === 'email' ? 'border-brand-green bg-brand-green/5' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="deliveryMethod" value="email" class="text-brand-green focus:ring-brand-green border-slate-300">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Via E-mail</span>
                                    <span class="text-[10px] text-slate-500">Documento PDF anexado em mensagem eletrônica.</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </label>

                        <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-colors"
                            :class="deliveryMethod === 'physical' ? 'border-brand-green bg-brand-green/5' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="deliveryMethod" value="physical" class="text-brand-green focus:ring-brand-green border-slate-300">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Via Físico</span>
                                    <span class="text-[10px] text-slate-500">Contrato impresso e entregue em mãos para o cliente.</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-brand-copper" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-8 border-t border-slate-200 dark:border-slate-800 pt-6">
                    <button @click="showDeliveryModal = false" class="px-4 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button @click="confirmDelivery" :disabled="isProcessing" class="px-6 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-brand-green text-white hover:bg-brand-green/90 transition-colors flex items-center gap-2 disabled:opacity-50">
                        <svg v-if="isProcessing" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Confirmar Entrega
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal Upload Signed Contract -->
        <Modal :show="showUploadModal" @close="showUploadModal = false" maxWidth="sm">
            <form @submit.prevent="submitUpload" class="p-6 bg-white dark:bg-[#0f1219]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-widest">Anexar Contrato</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Contrato {{ selectedContract?.contract }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                        Selecione o arquivo em PDF ou Imagem do contrato assinado pelo cliente <strong class="text-slate-900 dark:text-white">{{ selectedContract?.name }}</strong>.
                    </p>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Arquivo do Contrato</label>
                        <input type="file" @input="uploadForm.contract_file = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-indigo-500/10 dark:file:text-indigo-400 dark:hover:file:bg-indigo-500/20 transition-colors" required>
                        <p v-if="uploadForm.errors.contract_file" class="mt-1 text-xs text-red-500">{{ uploadForm.errors.contract_file }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-8 border-t border-slate-200 dark:border-slate-800 pt-6">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button type="submit" :disabled="uploadForm.processing" class="px-6 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-indigo-500 text-white hover:bg-indigo-600 transition-colors flex items-center gap-2 disabled:opacity-50">
                        <svg v-if="uploadForm.processing" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Enviar e Assinar
                    </button>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
