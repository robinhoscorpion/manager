<script setup>
import { ref, computed } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    products: Array,
    proposals: Array,
    adjustments: Array,
});

const activeTab = ref('products'); // 'products' or 'global'
const showEditModal = ref(false);
const editingProduct = ref(null);

const form = useForm({
    maintenance_fee_value: 0,
    maintenance_fee_installments: 1,
    maintenance_fee_frequency: 'annual',
    maintenance_fee_start_rule: 'semester_based',
    maintenance_fee_day: 10,
    maintenance_fee_delay_years: 0,
    is_maintenance_exempt: false,
});

// Global Adjustment Form
const globalForm = useForm({
    target_month: new Date().getMonth() + 1,
    target_year: new Date().getFullYear(),
    igpm_rate: 0,
    base_type: 'current',
    exempt_proposal_ids: [],
});

const months = [
    { value: 1, label: 'Janeiro' }, { value: 2, label: 'Fevereiro' }, { value: 3, label: 'Março' },
    { value: 4, label: 'Abril' }, { value: 5, label: 'Maio' }, { value: 6, label: 'Junho' },
    { value: 7, label: 'Julho' }, { value: 8, label: 'Agosto' }, { value: 9, label: 'Setembro' },
    { value: 10, label: 'Outubro' }, { value: 11, label: 'Novembro' }, { value: 12, label: 'Dezembro' }
];

const openEditModal = (product) => {
    editingProduct.value = product;
    form.maintenance_fee_value = product.maintenance_fee_value || 0;
    form.maintenance_fee_installments = product.maintenance_fee_installments || 1;
    form.maintenance_fee_frequency = product.maintenance_fee_frequency || 'annual';
    form.maintenance_fee_start_rule = product.maintenance_fee_start_rule || 'semester_based';
    form.maintenance_fee_day = product.maintenance_fee_day || 10;
    form.maintenance_fee_delay_years = product.maintenance_fee_delay_years || 0;
    form.is_maintenance_exempt = !!product.is_maintenance_exempt;
    showEditModal.value = true;
};

const submit = () => {
    form.put(route('admin.maintenance.update', editingProduct.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const submitGlobal = () => {
    if (!confirm('Deseja realmente aplicar este reajuste global? Esta ação afetará todas as parcelas pendentes do período selecionado.')) return;
    
    globalForm.post(route('admin.maintenance.global'), {
        onSuccess: () => {
            globalForm.reset('igpm_rate', 'exempt_proposal_ids');
            alert('Reajuste global aplicado com sucesso!');
        },
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

const onPriceInput = (e, field) => {
    let val = e.target.value.replace(/\D/g, '');
    form[field] = parseFloat(val) / 100;
};

const maskCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const getRuleLabel = (rule) => {
    const rules = {
        'semester_based': 'Sazonal (Semestral)',
        'contract_anniversary': 'Aniversário Contrato',
        'fixed_delay': 'Carência Fixa'
    };
    return rules[rule] || rule;
};

</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    @apply bg-white/[0.02] rounded-full;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-white/10 rounded-full hover:bg-purple-500/50;
}

/* Light Mode Overrides if needed by dashboard_style.css variables */
:deep(.dark) .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
</style>

<template>
    <Head title="Controle de Manutenção" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Gestão de Manutenção</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Configurações Financeiras Pós-Venda
                                </p>
                            </div>
                        </div>

                        <div class="flex bg-slate-100 dark:bg-slate-800 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <button 
                                @click="activeTab = 'products'"
                                :class="activeTab === 'products' ? 'bg-brand-green text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white'"
                                class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all"
                            >
                                Por Produto
                            </button>
                            <button 
                                @click="activeTab = 'global'"
                                :class="activeTab === 'global' ? 'bg-brand-green text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white'"
                                class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all"
                            >
                                Reajuste Global (IGPM)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total de Produtos</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ products.length }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm">
                        <p class="text-[10px] font-bold text-brand-green uppercase tracking-widest mb-1">Com Manutenção</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ products.filter(p => !p.is_maintenance_exempt).length }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm">
                        <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Produtos Isentos</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ products.filter(p => p.is_maintenance_exempt).length }}</p>
                    </div>
                </div>

                <!-- Table: By Product -->
                <div v-if="activeTab === 'products'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] overflow-hidden shadow-sm animate-in fade-in slide-in-from-bottom-4 duration-500 mb-12">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Produto / Categoria</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Isento</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Valor/Parc.</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Regra de Início</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dia/Carência</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-slate-900 dark:text-white font-semibold text-sm">{{ product.name }}</div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mt-0.5">{{ product.product_type?.name }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border"
                                        :class="product.is_maintenance_exempt ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400' : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400'"
                                    >
                                        {{ product.is_maintenance_exempt ? 'SIM' : 'NÃO' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="!product.is_maintenance_exempt">
                                        <div class="text-slate-900 dark:text-white font-bold text-sm">{{ formatCurrency(product.maintenance_fee_value) }}</div>
                                        <div class="text-[10px] text-brand-green font-bold uppercase tracking-widest">{{ product.maintenance_fee_installments }} parcelas</div>
                                    </template>
                                    <span v-else class="text-slate-400 dark:text-slate-600 text-[10px] font-bold italic">N/A</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="!product.is_maintenance_exempt" class="text-slate-700 dark:text-slate-300 text-xs font-medium">{{ getRuleLabel(product.maintenance_fee_start_rule) }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-600 text-[10px] font-bold italic">N/A</span>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="!product.is_maintenance_exempt">
                                        <div class="text-slate-900 dark:text-white text-xs font-bold">Dia {{ product.maintenance_fee_day }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">+{{ product.maintenance_fee_delay_years }} anos de carência</div>
                                    </template>
                                    <span v-else class="text-slate-400 dark:text-slate-600 text-[10px] font-bold italic">N/A</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="openEditModal(product)"
                                        class="p-2 bg-brand-green/10 hover:bg-brand-green/20 text-brand-green rounded-lg transition-all"
                                        title="Configurar Manutenção"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Section: Global Adjustment -->
                <div v-if="activeTab === 'global'" class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-500 mb-12">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Configuration Form -->
                        <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-8 shadow-sm flex flex-col h-fit">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-brand-green rounded-full"></span>
                                Aplicar Novo Reajuste
                            </h3>

                            <form @submit.prevent="submitGlobal" class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Mês Alvo</label>
                                        <select v-model="globalForm.target_month" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm">
                                            <option v-for="m in months" :key="m.value" :value="m.value" class="bg-white dark:bg-slate-800">{{ m.label }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Ano Alvo</label>
                                        <input v-model="globalForm.target_year" type="number" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm">
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Taxa IGPM (%)</label>
                                    <input v-model="globalForm.igpm_rate" type="number" step="0.0001" placeholder="Ex: 5.5" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Base de Cálculo</label>
                                    <select v-model="globalForm.base_type" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm">
                                        <option value="current" class="bg-white dark:bg-slate-800">Valor Atual (Individual)</option>
                                        <option value="previous" class="bg-white dark:bg-slate-800">Parcela Ano Anterior (Previsto)</option>
                                    </select>
                                    <p class="text-[8px] text-slate-500 font-medium px-1 mt-1 leading-relaxed uppercase">
                                        * Previous tenta encontrar a parcela de 12 meses atrás como base.
                                    </p>
                                </div>

                                <button type="submit" :disabled="globalForm.processing" class="w-full py-4 bg-brand-green hover:bg-[#485638] text-white rounded-xl font-bold uppercase text-xs tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50">
                                    {{ globalForm.processing ? 'Processando...' : 'Aplicar Reajuste Agora' }}
                                </button>
                            </form>
                        </div>

                        <!-- Exemption Selector -->
                        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-8 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2">
                                    <span class="w-1.5 h-4 bg-red-500 rounded-full"></span>
                                    Contratos Isentos deste Reajuste
                                </h3>
                                <span class="bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-500 text-[10px] font-bold px-2.5 py-1 rounded-md border border-red-200 dark:border-red-500/20 uppercase tracking-widest">
                                    {{ globalForm.exempt_proposal_ids.length }} Selecionados
                                </span>
                            </div>

                            <div class="max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div 
                                        v-for="proposal in proposals" 
                                        :key="proposal.id"
                                        @click="() => {
                                            const idx = globalForm.exempt_proposal_ids.indexOf(proposal.id);
                                            if (idx > -1) globalForm.exempt_proposal_ids.splice(idx, 1);
                                            else globalForm.exempt_proposal_ids.push(proposal.id);
                                        }"
                                        class="p-4 border rounded-xl cursor-pointer transition-all flex items-center justify-between group"
                                        :class="globalForm.exempt_proposal_ids.includes(proposal.id) ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/40' : 'bg-slate-50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                                    >
                                        <div class="flex-1">
                                            <p class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">#{{ proposal.contract_number }}</p>
                                            <p class="text-[9px] text-slate-500 uppercase font-bold tracking-widest mt-0.5 truncate">{{ proposal.client?.nome }}</p>
                                        </div>
                                        <div class="w-4 h-4 border-2 rounded flex items-center justify-center transition-all"
                                            :class="globalForm.exempt_proposal_ids.includes(proposal.id) ? 'bg-red-500 border-red-500' : 'border-slate-300 dark:border-slate-600 group-hover:border-slate-400 dark:group-hover:border-slate-500'"
                                        >
                                            <svg v-if="globalForm.exempt_proposal_ids.includes(proposal.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History of Adjustments -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] overflow-hidden shadow-sm">
                        <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-brand-green"></span>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Histórico de Reajustes Aplicados</h3>
                        </div>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Data Aplicação</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Período Alvo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Taxa IGPM</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Base de Cálculo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Isenções</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                <tr v-for="adj in adjustments" :key="adj.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-6 py-4 text-xs text-slate-900 dark:text-white font-medium">
                                        {{ new Date(adj.applied_at).toLocaleDateString('pt-BR') }}
                                        <span class="text-[9px] text-slate-500 block italic mt-0.5">às {{ new Date(adj.applied_at).toLocaleTimeString('pt-BR') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs text-slate-900 dark:text-white font-bold">{{ months.find(m => m.value === adj.target_month)?.label }} / {{ adj.target_year }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-1 bg-brand-green/10 text-brand-green border border-brand-green/20 rounded-md text-[10px] font-bold">
                                            {{ adj.igpm_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[9px] font-bold uppercase tracking-widest" :class="adj.base_type === 'current' ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-500'">
                                            {{ adj.base_type === 'current' ? 'VALOR ATUAL' : 'ANO ANTERIOR' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-slate-500 font-bold">
                                        {{ adj.exempt_proposal_ids?.length || 0 }} contratos
                                    </td>
                                </tr>
                                <tr v-if="adjustments.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-[10px] uppercase font-bold tracking-widest italic">
                                        Nenhum reajuste aplicado até o momento
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" maxWidth="2xl">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Configurar Manutenção</h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">{{ editingProduct?.name }}</p>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-6 bg-slate-50 dark:bg-transparent">
                    <div class="flex items-center justify-between bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-5 rounded-xl shadow-sm">
                        <div>
                            <p class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Isenção de Manutenção</p>
                            <p class="text-xs text-slate-500 mt-1">Ative para não cobrar manutenção neste produto</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_maintenance_exempt" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                        </label>
                    </div>

                    <div v-show="!form.is_maintenance_exempt" class="space-y-6 animate-in fade-in slide-in-from-top-2 duration-300">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Valor da Parcela (R$)</label>
                                <input :value="maskCurrency(form.maintenance_fee_value)" @input="onPriceInput($event, 'maintenance_fee_value')" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Total de Parcelas</label>
                                <input v-model="form.maintenance_fee_installments" type="number" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Frequência</label>
                                <select v-model="form.maintenance_fee_frequency" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                                    <option value="annual" class="bg-white dark:bg-slate-800">Anual (1/ano)</option>
                                    <option value="semestral" class="bg-white dark:bg-slate-800">Semestral (2/ano)</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Regra de Início</label>
                                <select v-model="form.maintenance_fee_start_rule" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                                    <option value="semester_based" class="bg-white dark:bg-slate-800">Sazonal (Jul/Dez)</option>
                                    <option value="contract_anniversary" class="bg-white dark:bg-slate-800">Aniversário do Contrato</option>
                                    <option value="fixed_delay" class="bg-white dark:bg-slate-800">Apenas Carência (Dia Fixo)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Dia Vencimento</label>
                                <input v-model="form.maintenance_fee_day" type="number" min="1" max="31" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Carência (Anos)</label>
                                <select v-model="form.maintenance_fee_delay_years" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                                    <option :value="0" class="bg-white dark:bg-slate-800">Cobrar no Ano 0</option>
                                    <option :value="1" class="bg-white dark:bg-slate-800">Começar após 1 Ano</option>
                                    <option :value="2" class="bg-white dark:bg-slate-800">Começar após 2 Anos</option>
                                    <option :value="3" class="bg-white dark:bg-slate-800">Começar após 3 Anos</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showEditModal = false" class="flex-1 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-[12px] font-bold text-xs uppercase tracking-wider transition-all shadow-sm">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-brand-green hover:bg-[#485638] text-white rounded-[12px] font-bold text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
