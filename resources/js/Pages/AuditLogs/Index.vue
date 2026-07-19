<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object,
    users: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || '');
const moduleFilter = ref(props.filters.module || '');
const userFilter = ref(props.filters.user_id || '');
const dateStartFilter = ref(props.filters.date_start || '');
const dateEndFilter = ref(props.filters.date_end || '');

watch([search, eventFilter, moduleFilter, userFilter, dateStartFilter, dateEndFilter], debounce(() => {
    router.get(route('admin.audit-logs.index'), { 
        search: search.value, 
        event: eventFilter.value,
        module: moduleFilter.value,
        user_id: userFilter.value,
        date_start: dateStartFilter.value,
        date_end: dateEndFilter.value,
    }, { 
        preserveState: true, 
        replace: true 
    });
}, 300));

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getEventColor = (event) => {
    switch (event) {
        case 'created': return 'text-green-400 bg-green-500/10 border-green-500/20';
        case 'updated': return 'text-blue-400 bg-blue-500/10 border-blue-500/20';
        case 'deleted': return 'text-red-400 bg-red-500/10 border-red-500/20';
        default: return 'text-gray-400 bg-gray-500/10 border-gray-500/20';
    }
};

const getModelName = (type) => {
    if (!type) return 'N/A';
    const model = type.split('\\').pop();
    const translations = {
        'User': 'Usuário',
        'Role': 'Cargo',
        'Permission': 'Permissão',
        'Product': 'Produto',
        'ProductType': 'Tipo de Produto',
        'Proposal': 'Proposta',
        'ProposalTemplate': 'Modelo de Proposta',
        'ContractTemplate': 'Modelo de Contrato',
        'Client': 'Cliente',
        'SalesService': 'Atendimento',
        'PaymentMethod': 'Forma de Pagamento'
    };
    return translations[model] || model;
};

const selectedLog = ref(null);

const formatJson = (json) => {
    if (!json) return null;
    return typeof json === 'string' ? JSON.parse(json) : json;
};
</script>

<template>
    <Head title="Logs do Sistema" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Logs do Sistema</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Auditoria e Rastreamento
                                </p>
                            </div>
                        </div>

                        <div class="relative group/search w-full sm:w-64">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within/search:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="BUSCAR LOG..." 
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl pl-11 pr-4 py-2.5 text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest placeholder:text-slate-400 focus:outline-none focus:border-brand-green/40 transition-all shadow-sm"
                            >
                        </div>
                    </div>

                    <!-- Advanced Filters Bar -->
                    <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-200/50 dark:border-slate-800/50 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
                        
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Módulo / Tipo</label>
                            <select v-model="moduleFilter" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest focus:outline-none focus:border-brand-green/40 transition-all shadow-sm">
                                <option value="" class="bg-white dark:bg-slate-800">Todos os Módulos</option>
                                <option value="User" class="bg-white dark:bg-slate-800">Usuários</option>
                                <option value="Role" class="bg-white dark:bg-slate-800">Cargos</option>
                                <option value="Product" class="bg-white dark:bg-slate-800">Produtos</option>
                                <option value="Proposal" class="bg-white dark:bg-slate-800">Propostas</option>
                                <option value="Client" class="bg-white dark:bg-slate-800">Clientes</option>
                                <option value="SalesService" class="bg-white dark:bg-slate-800">Atendimentos</option>
                                <option value="PaymentMethod" class="bg-white dark:bg-slate-800">F. Pagamento</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Usuário Responsável</label>
                            <select v-model="userFilter" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest focus:outline-none focus:border-brand-green/40 transition-all shadow-sm">
                                <option value="" class="bg-white dark:bg-slate-800">Todos os Usuários</option>
                                <option v-for="user in users" :key="user.id" :value="user.id" class="bg-white dark:bg-slate-800">{{ user.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Tipo de Evento</label>
                            <select v-model="eventFilter" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest focus:outline-none focus:border-brand-green/40 transition-all shadow-sm">
                                <option value="" class="bg-white dark:bg-slate-800">Todos Eventos</option>
                                <option value="created" class="bg-white dark:bg-slate-800">Criação (Insert)</option>
                                <option value="updated" class="bg-white dark:bg-slate-800">Edição (Update)</option>
                                <option value="deleted" class="bg-white dark:bg-slate-800">Exclusão (Delete)</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Período de Data</label>
                            <div class="flex items-center gap-2">
                                <input 
                                    v-model="dateStartFilter"
                                    type="date"
                                    class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase focus:outline-none focus:border-brand-green/40 transition-all shadow-sm"
                                    title="Data Inicial"
                                >
                                <span class="text-slate-400 font-bold text-[10px]">ATÉ</span>
                                <input 
                                    v-model="dateEndFilter"
                                    type="date"
                                    class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase focus:outline-none focus:border-brand-green/40 transition-all shadow-sm"
                                    title="Data Final"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-sm overflow-hidden mb-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Responsável</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ação</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Módulo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data / Hora</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-right text-slate-500 uppercase tracking-widest">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr v-for="log in logs.data" :key="log.id" class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-400 uppercase shadow-sm">
                                                {{ log.user ? log.user.name.charAt(0) : 'S' }}
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-tight">{{ log.user ? log.user.name : 'Sistema' }}</div>
                                                <div class="text-[10px] text-slate-500 font-medium tracking-normal">{{ log.ip_address }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="['px-2.5 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border', getEventColor(log.event)]">
                                            {{ log.event }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight">{{ getModelName(log.auditable_type) }}</span>
                                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">ID: #{{ log.auditable_id }}</span>
                                        </div>
                                        <div v-if="log.description" class="mt-1 text-[10px] text-slate-400 dark:text-slate-500 font-medium italic">
                                            {{ log.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400 tabular-nums">{{ formatDate(log.created_at) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            @click="selectedLog = log"
                                            class="p-2 text-slate-400 hover:text-brand-green hover:bg-brand-green/10 rounded-lg transition-all inline-flex"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="logs.data.length === 0" class="py-20 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-4 border border-slate-200 dark:border-slate-700">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-slate-900 dark:text-white font-bold uppercase tracking-wider text-sm">Nenhum registro encontrado</h3>
                        <p class="text-slate-500 text-xs mt-2">Nenhum log corresponde aos filtros informados.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links.length > 3" class="mb-12 flex justify-center gap-2">
                    <Link
                        v-for="(link, i) in logs.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-sm"
                        :class="[
                            link.active ? 'bg-brand-green text-white shadow-brand-green/20' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="selectedLog" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 dark:bg-black/80 backdrop-blur-sm" @click="selectedLog = null"></div>
            
            <div class="relative w-full max-w-2xl bg-white dark:bg-[#0f1219] rounded-[20px] shadow-2xl overflow-hidden animate-slide-up flex flex-col max-h-[90vh]">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f1219] flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Detalhes do Evento</h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Track ID #{{ selectedLog.id }}</p>
                        </div>
                    </div>
                    <button @click="selectedLog = null" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 md:p-8 flex-1 overflow-y-auto bg-slate-50 dark:bg-[#0f1219] custom-scrollbar">
                    <div class="grid grid-cols-2 gap-4 md:gap-8 mb-6">
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Responsável</span>
                            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-400 font-bold text-xs">{{ selectedLog.user ? selectedLog.user.name.charAt(0) : 'S' }}</div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase">{{ selectedLog.user ? selectedLog.user.name : 'Sistema' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Data e Hora</span>
                            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm h-[58px]">
                                <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 tabular-nums">{{ formatDate(selectedLog.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedLog.description" class="mb-8">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Descrição da Ação</span>
                        <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm text-sm text-slate-700 dark:text-slate-300 font-medium">
                            {{ selectedLog.description }}
                        </div>
                    </div>

                    <!-- Value Diff Section -->
                    <div v-if="selectedLog.event === 'updated'" class="space-y-4">
                        <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <span class="w-1.5 h-6 bg-brand-green rounded-full"></span>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Diferencial de Alterações</span>
                        </div>
                        <div class="grid gap-3">
                            <div v-for="(value, key) in formatJson(selectedLog.new_values)" :key="key" class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ key }}</span>
                                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 text-[9px] font-bold uppercase rounded-md tracking-widest">Alterado</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest px-1">Valor Anterior</span>
                                        <div class="p-2.5 bg-red-50 dark:bg-red-500/5 border border-red-100 dark:border-red-500/10 rounded-lg text-[11px] text-red-600 dark:text-red-400/80 break-words font-medium">
                                            {{ formatJson(selectedLog.old_values)[key] ?? 'Inexistente' }}
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest px-1">Novo Valor</span>
                                        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-100 dark:border-emerald-500/10 rounded-lg text-[11px] text-emerald-600 dark:text-emerald-400 break-words font-bold">
                                            {{ value }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Creation Details -->
                    <div v-else-if="selectedLog.event === 'created'" class="space-y-3">
                        <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <span class="w-1.5 h-6 bg-brand-green rounded-full"></span>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Dados do Registro</span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[14px] border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto shadow-inner">
                            <pre class="text-[11px] text-slate-700 dark:text-slate-400 font-mono">{{ JSON.stringify(formatJson(selectedLog.new_values), null, 4) }}</pre>
                        </div>
                    </div>

                    <!-- Deletion Details -->
                    <div v-else-if="selectedLog.event === 'deleted'" class="space-y-3">
                        <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <span class="w-1.5 h-6 bg-brand-green rounded-full"></span>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Dados do Registro Excluído</span>
                        </div>
                        <div class="bg-red-50 dark:bg-red-500/5 rounded-[14px] border border-red-100 dark:border-red-500/10 p-6 overflow-x-auto shadow-inner">
                            <pre class="text-[11px] text-red-600 dark:text-red-400/80 font-mono">{{ JSON.stringify(formatJson(selectedLog.old_values), null, 4) }}</pre>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <div class="flex flex-col gap-2 px-1">
                            <div class="flex items-center justify-between text-[10px] mb-2">
                                <span class="font-bold text-slate-500 uppercase tracking-widest">Endereço IP</span>
                                <span class="text-slate-700 dark:text-slate-400 font-medium tabular-nums">{{ selectedLog.ip_address }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-500 uppercase tracking-widest">User Agent</span>
                                <span class="text-slate-700 dark:text-slate-400 font-medium line-clamp-1 max-w-[70%] select-all">{{ selectedLog.user_agent }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white dark:bg-[#0f1219] border-t border-slate-200 dark:border-slate-800 flex justify-end flex-shrink-0">
                    <button 
                        @click="selectedLog = null"
                        class="px-8 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold uppercase text-xs tracking-wider rounded-[12px] transition-all shadow-sm"
                    >
                        Fechar Visualizador
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
