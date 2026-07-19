<script setup>
import { ref, watch } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import Modal from '@/Components/Modal.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    protocols: Object,
    metrics: Object,
    filters: Object,
});

const isCreateModalOpen = ref(false);
const searchServiceQuery = ref('');
const searchResults = ref([]);
const isSearchingService = ref(false);
const selectedService = ref(null);

// Bulk Actions State
const selectedProtocols = ref([]);
const isBulkUpdating = ref(false);

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedProtocols.value = props.protocols.data.map(p => p.id);
    } else {
        selectedProtocols.value = [];
    }
};

const applyBulkAction = (action, value) => {
    if (selectedProtocols.value.length === 0) return;
    
    isBulkUpdating.value = true;
    router.post(route('sales.protocols.bulk-update'), {
        protocol_ids: selectedProtocols.value,
        action: action,
        value: value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedProtocols.value = [];
            isBulkUpdating.value = false;
        },
        onError: () => {
            isBulkUpdating.value = false;
        }
    });
};

const createForm = useForm({
    subject: '',
    priority: 'media',
    message: '',
});

const searchService = debounce(async () => {
    if (!searchServiceQuery.value || searchServiceQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    
    isSearchingService.value = true;
    try {
        const response = await axios.get(route('api.search.global'), { params: { q: searchServiceQuery.value } });
        searchResults.value = response.data;
    } catch (error) {
        console.error("Error searching services:", error);
    } finally {
        isSearchingService.value = false;
    }
}, 400);

watch(searchServiceQuery, searchService);

const selectService = (service) => {
    selectedService.value = service;
    searchServiceQuery.value = '';
    searchResults.value = [];
};

const removeSelectedService = () => {
    selectedService.value = null;
};

const openCreateModal = () => {
    isCreateModalOpen.value = true;
    createForm.reset();
    selectedService.value = null;
    searchServiceQuery.value = '';
    searchResults.value = [];
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    setTimeout(() => {
        createForm.reset();
        selectedService.value = null;
    }, 100);
};

const submitCreateProtocol = () => {
    if (!selectedService.value) return;
    
    createForm.post(route('sales.atendimentos.protocols.store', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
            selectedService.value = null;
        }
    });
};

const selectedProtocol = ref(null);
const showDescriptionModal = ref(false);

const replyForm = useForm({
    message: '',
    status: '',
});

const openDescription = (protocol) => {
    selectedProtocol.value = protocol;
    replyForm.reset();
    replyForm.clearErrors();
    showDescriptionModal.value = true;
};

const closeDescription = () => {
    showDescriptionModal.value = false;
    setTimeout(() => {
        selectedProtocol.value = null;
        replyForm.reset();
        replyForm.clearErrors();
    }, 300);
};

const submitReply = (closeProtocol = false, withoutMessage = false) => {
    if (!selectedProtocol.value) return;
    
    if (withoutMessage) {
        // Apenas conclui o protocolo sem adicionar resposta
        router.patch(route('sales.protocols.status.update', selectedProtocol.value.id), { status: 'fechado' }, {
            preserveScroll: true,
            onSuccess: () => {
                closeDescription();
            }
        });
        return;
    }

    if (closeProtocol) {
        replyForm.status = 'fechado';
    } else {
        replyForm.status = ''; // keeps it as is or can be handled by backend
    }

    replyForm.post(route('sales.protocols.replies.store', selectedProtocol.value.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            replyForm.reset('message');
            // Fechar modal se o protocolo for fechado com sucesso
            if (closeProtocol) {
                closeDescription();
            } else {
                // Atualizar o selectedProtocol na interface
                const updatedProtocol = page.props.protocols.data.find(p => p.id === selectedProtocol.value.id);
                if (updatedProtocol) {
                    selectedProtocol.value = updatedProtocol;
                }
            }
        },
    });
};

const todayDate = new Date().toISOString().split('T')[0];

const form = ref({
    start_date: props.filters.start_date || todayDate,
    end_date: props.filters.end_date || todayDate,
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    search: props.filters.search || '',
});

const setDateRange = (days) => {
    const end = new Date();
    const start = new Date();
    if (days > 0) {
        start.setDate(start.getDate() - days);
    }
    form.value.start_date = start.toISOString().split('T')[0];
    form.value.end_date = end.toISOString().split('T')[0];
};

const setToday = () => {
    const today = new Date().toISOString().split('T')[0];
    form.value.start_date = today;
    form.value.end_date = today;
};

const applyFilters = debounce(() => {
    router.get(
        route('after-sales.protocols.index'),
        form.value,
        { preserveState: true, preserveScroll: true }
    );
}, 300);

watch(() => form.value, applyFilters, { deep: true });

const clearFilters = () => {
    form.value = {
        start_date: todayDate,
        end_date: todayDate,
        status: '',
        priority: '',
        search: '',
    };
    applyFilters();
};

const getPriorityColor = (priority) => {
    switch (priority?.toLowerCase()) {
        case 'alta':
        case 'high':
            return 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400';
        case 'média':
        case 'media':
        case 'medium':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400';
    }
};

const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'fechado':
        case 'resolvido':
        case 'closed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-400';
        case 'em andamento':
        case 'in progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
    }
};
const resizeIframe = (e) => {
    const iframe = e.target;
    try {
        iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
    } catch (err) {
        // Ignore CORS issues if any, though srcdoc shouldn't trigger them
    }
};

const decodeHtml = (html) => {
    if (!html) return '';
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
};

const isFullHtml = (html) => {
    if (!html) return false;
    const decoded = decodeHtml(html);
    return decoded.includes('<!DOCTYPE html>') || decoded.includes('<html') || decoded.includes('<body');
};
</script>

<template>
    <Head title="Protocolos Diários - Pós-venda" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-green to-emerald-600 flex items-center justify-center text-white shadow-lg shadow-brand-green/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-500 dark:from-white dark:to-slate-400">
                            Central de Protocolos
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Acompanhamento e gestão inteligente de chamados</p>
                    </div>
                </div>
                <div>
                    <button @click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-brand-green hover:bg-emerald-600 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg shadow-brand-green/30 hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Novo Protocolo
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 animate-fade-in-up" style="animation-delay: 0.1s">
            <!-- Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <StatCard 
                    title="Protocolos Hoje" 
                    :value="metrics.total_today.toString()" 
                    trend="Hoje" 
                    subtitle="abertos neste dia"
                    :trend-up="true"
                    class="transform hover:-translate-y-1 transition-all duration-300 shadow-sm hover:shadow-xl hover:shadow-brand-green/10"
                >
                    <template #icon>
                        <div class="p-3 bg-brand-green/10 dark:bg-brand-green/20 rounded-xl text-brand-green">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </template>
                </StatCard>
                <StatCard 
                    title="Atenção Necessária" 
                    :value="metrics.pending.toString()" 
                    trend="Pendentes" 
                    subtitle="em aberto ou andamento"
                    :trend-up="false"
                    class="transform hover:-translate-y-1 transition-all duration-300 shadow-sm hover:shadow-xl hover:shadow-amber-500/10"
                >
                    <template #icon>
                        <div class="p-3 bg-amber-500/10 dark:bg-amber-500/20 rounded-xl text-amber-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </template>
                </StatCard>
            </div>

            <!-- Filters -->
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-lg shadow-slate-200/40 dark:shadow-none border border-slate-200/50 dark:border-slate-700/50 p-5 mb-6 animate-fade-in-up" style="animation-delay: 0.2s">
                <div class="flex flex-col gap-4 mb-4">
                    <!-- Search Row -->
                    <div class="w-full relative group">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Pesquisar</label>
                        <div class="relative flex items-center">
                            <div class="absolute left-3 text-slate-400 group-focus-within:text-brand-green transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="form.search" placeholder="Buscar por número, assunto ou cliente..." class="pl-10 w-full h-11 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300">
                        </div>
                    </div>

                    <!-- Other Filters Row -->
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[140px] max-w-xs">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Data Início</label>
                            <input type="date" v-model="form.start_date" class="w-full h-11 px-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300">
                        </div>

                        <div class="flex-1 min-w-[140px] max-w-xs">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Data Fim</label>
                            <input type="date" v-model="form.end_date" class="w-full h-11 px-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300">
                        </div>

                        <div class="flex-1 min-w-[140px] max-w-xs">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                            <select v-model="form.status" class="w-full h-11 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300 cursor-pointer">
                                <option value="">Todos</option>
                                <option value="aberto">Aberto</option>
                                <option value="em andamento">Em Andamento</option>
                                <option value="fechado">Fechado</option>
                            </select>
                        </div>
                        
                        <div class="flex-1 min-w-[140px] max-w-xs">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Prioridade</label>
                            <select v-model="form.priority" class="w-full h-11 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300 cursor-pointer">
                                <option value="">Todas</option>
                                <option value="alta">Alta</option>
                                <option value="media">Média</option>
                                <option value="baixa">Baixa</option>
                            </select>
                        </div>
                        
                        <button @click="clearFilters" class="h-11 px-5 text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-all duration-300 border border-transparent hover:shadow-md flex items-center justify-center gap-2 group flex-shrink-0">
                            <svg class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Limpar
                        </button>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mr-2">Filtros Rápidos:</span>
                    <button @click="setToday" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="form.start_date === new Date().toISOString().split('T')[0] && form.end_date === new Date().toISOString().split('T')[0] ? 'bg-brand-green text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'">Hoje</button>
                    <button @click="setDateRange(7)" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">Últimos 7 dias</button>
                    <button @click="setDateRange(30)" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">Últimos 30 dias</button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl shadow-xl shadow-slate-200/30 dark:shadow-none border border-slate-200/60 dark:border-slate-700/60 overflow-hidden animate-fade-in-up" style="animation-delay: 0.3s">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/80 backdrop-blur-md">
                                <th class="py-4 px-4 w-12 text-center align-middle">
                                    <input type="checkbox" @change="toggleSelectAll" :checked="selectedProtocols.length === protocols.data.length && protocols.data.length > 0" class="w-4 h-4 rounded border-slate-300 text-brand-green focus:ring-brand-green dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                                </th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Protocolo</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Data</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Cliente</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Assunto</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Prioridade</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Status</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in protocols.data" :key="item.id" class="group relative hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors duration-300" :class="{'bg-brand-green/5 dark:bg-brand-green/10': selectedProtocols.includes(item.id)}">
                                <td class="py-4 px-4 align-middle text-center">
                                    <input type="checkbox" :value="item.id" v-model="selectedProtocols" class="w-4 h-4 rounded border-slate-300 text-brand-green focus:ring-brand-green dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-400 font-light text-xs">#</span>
                                        <span class="font-bold text-sm text-slate-800 dark:text-slate-100 group-hover:text-brand-green transition-colors">{{ item.protocol_number }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ item.created_at.split(' ')[0] }}
                                        </div>
                                        <span class="text-slate-400 dark:text-slate-500 text-[10px] font-medium mt-0.5 ml-5">{{ item.created_at.split(' ')[1] }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="flex flex-col justify-center">
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-brand-green transition-colors">{{ item.client_name }}</div>
                                        <div v-if="item.contract_number" class="text-[10.5px] font-medium text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span>Ctr <span class="text-slate-700 dark:text-slate-300 font-bold">#{{ item.contract_number }}</span></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="text-sm font-semibold text-slate-700 dark:text-slate-200 max-w-[200px] sm:max-w-xs truncate group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                                        {{ item.subject }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <span :class="['inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider', getPriorityColor(item.priority)]">
                                        {{ item.priority || 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <span :class="['inline-flex items-center justify-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider border', getStatusColor(item.status)]">
                                        <div class="w-1 h-1 rounded-full bg-current opacity-70"></div>
                                        {{ item.status || 'aberto' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right align-middle">
                                    <div class="flex items-center justify-end gap-2 opacity-70 group-hover:opacity-100 transition-opacity">
                                        <button @click="openDescription(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-brand-green bg-white hover:bg-brand-green/10 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-brand-green/20 dark:hover:border-brand-green/30 transition-all duration-300 shadow-sm hover:shadow" title="Ver Histórico">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        </button>
                                        <Link :href="route('sales.atendimentos.show', item.service_id)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white bg-white hover:bg-brand-green border border-slate-200 hover:border-brand-green dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-brand-green dark:hover:border-brand-green transition-all duration-300 shadow-sm hover:shadow" title="Detalhes do Atendimento">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="protocols.data.length === 0">
                                <td colspan="7" class="py-16 px-6">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div class="w-16 h-16 mb-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-300 dark:text-slate-600">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Nenhum protocolo encontrado</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm">
                                            Tente ajustar os filtros de busca ou período para encontrar o que procura.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="protocols.links && protocols.links.length > 3" class="p-4 sm:p-6 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-700/80">
                    <Pagination :links="protocols.links" />
                </div>

        <!-- Floating Bulk Action Bar (Clean Light Style) -->
        <transition enter-active-class="transition ease-out duration-300 transform" enter-from-class="opacity-0 translate-y-10 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition ease-in duration-200 transform" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 translate-y-10 scale-95">
            <div v-if="selectedProtocols.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-5 lg:gap-8 px-6 py-3.5 bg-white dark:bg-slate-800 shadow-[0_15px_40px_-10px_rgba(0,0,0,0.15)] dark:shadow-none border border-slate-200 dark:border-slate-700 rounded-2xl w-[95%] sm:w-max max-w-[1400px]">
                
                <div class="flex items-center gap-3 flex-shrink-0">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-green/10 text-brand-green font-black text-sm">
                        {{ selectedProtocols.length }}
                    </div>
                    <div class="flex flex-col hidden sm:flex">
                        <span class="text-slate-800 dark:text-slate-100 font-bold text-sm">Selecionados</span>
                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">Ações em lote</span>
                    </div>
                </div>

                <div class="hidden sm:block w-px h-8 bg-slate-200 dark:bg-slate-700 flex-shrink-0"></div>

                <div class="flex items-center gap-5 lg:gap-8 flex-shrink-0">
                    <!-- Status Actions -->
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Alterar Status para:</span>
                        <div class="flex items-center gap-2">
                            <button @click="applyBulkAction('status', 'aberto')" :disabled="isBulkUpdating" class="px-4 py-1.5 text-xs font-bold rounded-lg border border-emerald-200 text-emerald-600 bg-white hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-400 dark:bg-slate-800 dark:hover:bg-emerald-500/10 transition-colors disabled:opacity-50">Aberto</button>
                            <button @click="applyBulkAction('status', 'em_andamento')" :disabled="isBulkUpdating" class="px-4 py-1.5 text-xs font-bold rounded-lg border border-blue-200 text-blue-600 bg-white hover:bg-blue-50 dark:border-blue-500/30 dark:text-blue-400 dark:bg-slate-800 dark:hover:bg-blue-500/10 transition-colors disabled:opacity-50">Em Andamento</button>
                            <button @click="applyBulkAction('status', 'fechado')" :disabled="isBulkUpdating" class="px-4 py-1.5 text-xs font-bold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">Concluído</button>
                        </div>
                    </div>
                    
                    <div class="hidden sm:block w-px h-8 bg-slate-200 dark:bg-slate-700"></div>
                    
                    <!-- Priority Actions -->
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Alterar Prioridade para:</span>
                        <div class="flex items-center gap-2">
                            <button @click="applyBulkAction('priority', 'urgente')" :disabled="isBulkUpdating" class="px-4 py-1.5 text-xs font-bold rounded-lg border border-red-200 text-red-600 bg-white hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:bg-slate-800 dark:hover:bg-red-500/10 transition-colors disabled:opacity-50">Urgente</button>
                            <button @click="applyBulkAction('priority', 'alta')" :disabled="isBulkUpdating" class="px-4 py-1.5 text-xs font-bold rounded-lg border border-orange-200 text-orange-600 bg-white hover:bg-orange-50 dark:border-orange-500/30 dark:text-orange-400 dark:bg-slate-800 dark:hover:bg-orange-500/10 transition-colors disabled:opacity-50">Alta</button>
                        </div>
                    </div>
                </div>
                
                <div class="hidden sm:block w-px h-8 bg-slate-200 dark:bg-slate-700 flex-shrink-0"></div>

                <button @click="selectedProtocols = []" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-700 rounded-lg transition-colors flex-shrink-0" title="Cancelar Seleção">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </transition>
            </div>
        </div>

        <Modal :show="showDescriptionModal" @close="closeDescription" maxWidth="3xl">
            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-xl shadow-2xl">
                <!-- Header with background pattern -->
                <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-r from-brand-green/20 to-emerald-600/10 dark:from-brand-green/10 dark:to-emerald-900/10 opacity-50"></div>
                
                <div class="relative p-6 sm:p-8">
                    <!-- Top section -->
                    <div class="flex items-start justify-between mb-8">
                        <div class="flex gap-4 items-start">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-green to-emerald-600 flex items-center justify-center text-white shadow-lg shadow-brand-green/30 flex-shrink-0 mt-1">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                                        Protocolo <span class="text-brand-green">#{{ selectedProtocol?.protocol_number }}</span>
                                    </h3>
                                    <span v-if="selectedProtocol" :class="['inline-flex items-center justify-center px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider', getStatusColor(selectedProtocol.status)]">
                                        {{ selectedProtocol.status }}
                                    </span>
                                </div>
                                <p class="text-base font-medium text-slate-600 dark:text-slate-300">
                                    {{ selectedProtocol?.subject }}
                                </p>
                                
                                <!-- Meta infos -->
                                <div v-if="selectedProtocol" class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4 text-sm">
                                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        {{ selectedProtocol.client_name }}
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ selectedProtocol.created_at }}
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" /></svg>
                                        Atendente: <span class="font-medium text-slate-700 dark:text-slate-300">{{ selectedProtocol.user }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="closeDescription" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors bg-white/50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-700 p-2 rounded-xl backdrop-blur-sm border border-slate-200/50 dark:border-slate-700/50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <!-- Content area -->
                    <div class="bg-slate-50/80 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 p-1">
                        <div class="px-5 py-3 border-b border-slate-200/50 dark:border-slate-700/50 bg-white/50 dark:bg-slate-900/30 rounded-t-xl flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Histórico do Protocolo
                            </h4>
                        </div>
                        
                        <div class="p-5 sm:p-6 max-h-[50vh] overflow-y-auto custom-scrollbar flex flex-col gap-6">
                            
                            <!-- Original Message -->
                            <div v-if="selectedProtocol && selectedProtocol.description" class="flex gap-4">
                                <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div class="flex-1 bg-white dark:bg-slate-900/50 rounded-2xl rounded-tl-sm p-4 shadow-sm border border-slate-200/50 dark:border-slate-700/50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ selectedProtocol.user }} <span class="font-normal text-slate-500">(Autor)</span></span>
                                        <span class="text-xs text-slate-400 font-medium">{{ selectedProtocol.created_at }}</span>
                                    </div>
                                    <template v-if="isFullHtml(selectedProtocol.description)">
                                        <iframe :srcdoc="decodeHtml(selectedProtocol.description)" class="w-full border-0 bg-white rounded-xl shadow-inner min-h-[500px]" @load="resizeIframe"></iframe>
                                    </template>
                                    <div v-else class="prose prose-sm sm:prose-base dark:prose-invert max-w-none prose-p:leading-relaxed prose-a:text-brand-green" v-html="decodeHtml(selectedProtocol.description)"></div>
                                </div>
                            </div>

                            <div v-if="selectedProtocol && !selectedProtocol.description" class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Nenhuma descrição inicial informada</span>
                            </div>

                            <!-- Replies Timeline -->
                            <template v-if="selectedProtocol && selectedProtocol.replies && selectedProtocol.replies.length > 0">
                                <div v-for="reply in selectedProtocol.replies" :key="reply.id" class="flex gap-4">
                                    <div class="w-10 h-10 rounded-full bg-brand-green/20 flex items-center justify-center flex-shrink-0 mt-1">
                                        <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="flex-1 bg-brand-green/5 dark:bg-brand-green/10 rounded-2xl rounded-tl-sm p-4 border border-brand-green/20">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ reply.user_name }}</span>
                                            <span class="text-xs text-slate-400 font-medium">{{ reply.created_at }}</span>
                                        </div>
                                        <template v-if="isFullHtml(reply.message)">
                                            <iframe :srcdoc="decodeHtml(reply.message)" class="w-full border-0 bg-white rounded-xl shadow-inner min-h-[300px]" @load="resizeIframe"></iframe>
                                        </template>
                                        <div v-else class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300" v-html="decodeHtml(reply.message)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div v-if="selectedProtocol && selectedProtocol.status !== 'fechado'" class="mt-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200/60 dark:border-slate-700/60">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Adicionar Resposta</label>
                        <textarea v-model="replyForm.message" rows="3" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300 custom-scrollbar resize-none p-3 mb-3" placeholder="Escreva sua resposta aqui..."></textarea>
                        
                        <div v-if="replyForm.errors.message" class="text-red-500 text-xs mb-3 font-medium">{{ replyForm.errors.message }}</div>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="text-xs text-slate-500 font-medium">Você pode apenas responder, responder e concluir, ou apenas concluir o protocolo.</p>
                            <div class="flex flex-wrap gap-2 w-full sm:w-auto justify-end">
                                <button @click="submitReply(false, true)" class="flex-1 sm:flex-none px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Apenas Concluir
                                </button>
                                <button @click="submitReply(false)" :disabled="replyForm.processing" class="flex-1 sm:flex-none px-4 py-2.5 text-xs font-semibold text-brand-green bg-brand-green/10 hover:bg-brand-green/20 rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                    Responder
                                </button>
                                <button @click="submitReply(true)" :disabled="replyForm.processing" class="flex-1 sm:flex-none px-4 py-2.5 text-xs font-semibold text-white bg-brand-green hover:bg-emerald-600 rounded-xl transition-colors shadow-md hover:shadow-lg shadow-brand-green/30 disabled:opacity-50 flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Concluir e Responder
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="selectedProtocol" class="mt-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl p-4 border border-amber-200/60 dark:border-amber-700/30 flex items-center justify-center gap-3 text-amber-700 dark:text-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        <span class="text-sm font-semibold">Este protocolo está fechado e não pode receber novas respostas.</span>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="closeDescription" class="px-6 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-colors">
                            Fechar Janela
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
        <!-- Modal de Novo Protocolo -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="3xl" :closeable="false">
            <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-2xl sm:rounded-2xl flex flex-col max-h-[90vh]">
                <!-- Cabeçalho -->
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50 shrink-0 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-green/5 to-transparent dark:from-brand-green/10"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-green/20 to-emerald-500/20 flex items-center justify-center flex-shrink-0 shadow-inner">
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">
                                Abrir Novo Protocolo
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Crie um chamado vinculado a um atendimento</p>
                        </div>
                    </div>
                    <button @click="closeCreateModal" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-200 dark:hover:text-slate-300 dark:hover:bg-slate-700 transition-colors relative z-10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Conteúdo -->
                <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-slate-900">
                    
                    <!-- Passo 1: Selecionar Atendimento -->
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wider">
                            Vincular ao Cliente / Atendimento <span class="text-red-500">*</span>
                        </label>

                        <div v-if="!selectedService" class="relative">
                            <div class="relative flex items-center">
                                <div class="absolute left-3 text-slate-400">
                                    <svg v-if="isSearchingService" class="animate-spin h-5 w-5 text-brand-green" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="searchServiceQuery" 
                                    placeholder="Digite o nome, CPF ou contrato..." 
                                    class="pl-10 w-full h-12 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all"
                                >
                            </div>

                            <!-- Resultados da Busca -->
                            <div v-if="searchResults.length > 0 && searchServiceQuery" class="mt-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-lg overflow-hidden divide-y divide-slate-100 dark:divide-slate-700/50">
                                <button 
                                    v-for="result in searchResults" 
                                    :key="result.id"
                                    @click="selectService(result)"
                                    class="w-full text-left px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex items-center justify-between group"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-green group-hover:text-white transition-all shadow-sm">
                                            <span class="text-slate-500 dark:text-slate-300 font-bold text-sm group-hover:text-white transition-colors">{{ result.title.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-brand-green transition-colors">{{ result.title }}</div>
                                            <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">{{ result.subtitle }}</div>
                                        </div>
                                    </div>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:text-brand-green group-hover:bg-brand-green/10 transition-all opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </div>
                                </button>
                            </div>
                            
                            <div v-else-if="searchServiceQuery.length >= 2 && !isSearchingService" class="mt-4 bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">Nenhum contrato encontrado</div>
                                <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">Verifique se o termo digitado está correto.</div>
                            </div>
                        </div>
                        
                        <div v-else class="flex items-center justify-between p-5 bg-gradient-to-r from-brand-green/5 to-transparent border border-brand-green/30 rounded-2xl shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-brand-green text-white flex items-center justify-center flex-shrink-0 shadow-inner">
                                    <span class="font-bold text-lg">{{ selectedService.title.charAt(0).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ selectedService.title }}</div>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        {{ selectedService.subtitle }}
                                    </div>
                                </div>
                            </div>
                            <button @click="removeSelectedService" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-all" title="Trocar Cliente">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedService" class="space-y-6 animate-fade-in-up mt-6">
                        <!-- Assunto -->
                        <div class="group">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider group-focus-within:text-brand-green transition-colors">Assunto <span class="text-red-500">*</span></label>
                            <input type="text" v-model="createForm.subject" placeholder="Ex: Dúvida sobre pagamento, Cancelamento..." class="w-full h-12 px-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all shadow-sm" required>
                            <div v-if="createForm.errors.subject" class="text-red-500 text-xs mt-1 font-medium">{{ createForm.errors.subject }}</div>
                        </div>

                        <!-- Prioridade -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Prioridade <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="priority" value="baixa" v-model="createForm.priority" class="sr-only">
                                    <div :class="[
                                        'px-3 py-2 text-center text-xs font-bold rounded-lg border transition-all uppercase tracking-wide',
                                        createForm.priority === 'baixa' 
                                            ? 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30 ring-1 ring-blue-300 dark:ring-blue-500/30' 
                                            : 'border-slate-200 dark:border-slate-700 text-slate-500 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]">
                                        Baixa
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="priority" value="media" v-model="createForm.priority" class="sr-only">
                                    <div :class="[
                                        'px-3 py-2 text-center text-xs font-bold rounded-lg border transition-all uppercase tracking-wide',
                                        createForm.priority === 'media' 
                                            ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30 ring-1 ring-amber-300 dark:ring-amber-500/30' 
                                            : 'border-slate-200 dark:border-slate-700 text-slate-500 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]">
                                        Média
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="priority" value="alta" v-model="createForm.priority" class="sr-only">
                                    <div :class="[
                                        'px-3 py-2 text-center text-xs font-bold rounded-lg border transition-all uppercase tracking-wide',
                                        createForm.priority === 'alta' 
                                            ? 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-500/20 dark:text-orange-400 dark:border-orange-500/30 ring-1 ring-orange-300 dark:ring-orange-500/30' 
                                            : 'border-slate-200 dark:border-slate-700 text-slate-500 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]">
                                        Alta
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="priority" value="urgente" v-model="createForm.priority" class="sr-only">
                                    <div :class="[
                                        'px-3 py-2 text-center text-xs font-bold rounded-lg border transition-all uppercase tracking-wide',
                                        createForm.priority === 'urgente' 
                                            ? 'bg-red-100 text-red-800 border-red-300 dark:bg-red-500/20 dark:text-red-400 dark:border-red-500/30 ring-1 ring-red-300 dark:ring-red-500/30' 
                                            : 'border-slate-200 dark:border-slate-700 text-slate-500 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
                                    ]">
                                        Urgente
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Mensagem -->
                        <div class="group">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider group-focus-within:text-brand-green transition-colors">Descrição / Mensagem Inicial <span class="text-red-500">*</span></label>
                            <RichTextEditor v-model="createForm.message" placeholder="Descreva os detalhes deste protocolo de forma rica..." />
                            <div v-if="createForm.errors.message" class="text-red-500 text-xs mt-1 font-medium">{{ createForm.errors.message }}</div>
                        </div>
                    </div>
                </div>

                <!-- Rodapé -->
                <div class="px-8 py-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 shrink-0 flex justify-end gap-3 rounded-b-2xl">
                    <button @click="closeCreateModal" type="button" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button @click="submitCreateProtocol" :disabled="!selectedService || createForm.processing" class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-green hover:bg-emerald-600 rounded-xl transition-all duration-300 shadow-md shadow-brand-green/30 disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg v-if="createForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Salvar Protocolo
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom Scrollbar for Modal */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}
</style>
