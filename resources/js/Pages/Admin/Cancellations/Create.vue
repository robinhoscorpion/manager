<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const form = useForm({
    proposal_id: null,
    reason: '',
    details: '',
    fine_amount: 0,
    refund_amount: 0,
    cancel_all_bills: false,
});

const searchTerm = ref('');
const isSearching = ref(false);
const searchError = ref('');
const searchResults = ref([]);
const proposalData = ref(null);
const financialData = ref(null);

const reasons = [
    'Financeiro (Falta de recursos)',
    'Motivos de Saúde',
    'Arrependimento / Código do Consumidor',
    'Insatisfação com o Produto',
    'Mudança de Cidade/País',
    'Outros'
];

const searchContract = async () => {
    if (!searchTerm.value) return;
    
    isSearching.value = true;
    searchError.value = '';
    searchResults.value = [];
    proposalData.value = null;
    financialData.value = null;
    form.proposal_id = null;

    try {
        const response = await axios.get(route('after-sales.cancellations.search'), {
            params: { term: searchTerm.value }
        });
        
        searchResults.value = response.data;
    } catch (error) {
        searchError.value = error.response?.data?.error || 'Erro ao buscar contrato. Verifique o número digitado.';
    } finally {
        isSearching.value = false;
    }
};

const selectProposal = (result) => {
    proposalData.value = result.proposal;
    financialData.value = result.financial;
    form.proposal_id = result.proposal.id;
    searchResults.value = []; // hide the results list
    searchTerm.value = ''; // clear search term to look cleaner or leave it
};

let searchTimeout;
watch(searchTerm, (newVal) => {
    clearTimeout(searchTimeout);
    if (!newVal || newVal.length < 3) {
        searchResults.value = [];
        if (!proposalData.value) {
            searchError.value = '';
        }
        return;
    }
    
    // Only search if we haven't just selected one
    if (proposalData.value && form.proposal_id) {
        proposalData.value = null;
        financialData.value = null;
        form.proposal_id = null;
    }
    
    searchTimeout = setTimeout(() => {
        searchContract();
    }, 500);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const displayFineAmount = ref('0,00');
const displayRefundAmount = ref('0,00');

const formatInput = (value) => {
    if (!value) return '0,00';
    const digits = value.toString().replace(/\D/g, '');
    const num = parseInt(digits || '0', 10) / 100;
    return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleFineInput = (e) => {
    const raw = e.target.value;
    const digits = raw.replace(/\D/g, '');
    const num = parseInt(digits || '0', 10) / 100;
    displayFineAmount.value = formatInput(raw);
    form.fine_amount = num;
};

const handleRefundInput = (e) => {
    const raw = e.target.value;
    const digits = raw.replace(/\D/g, '');
    const num = parseInt(digits || '0', 10) / 100;
    displayRefundAmount.value = formatInput(raw);
    form.refund_amount = num;
};

const submitCancellation = () => {
    form.post(route('after-sales.cancellations.store'));
};
</script>

<template>
    <Head title="Novo Distrato" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20">
                
                <!-- Premium Header -->
                <div class="bg-white dark:bg-slate-900 border border-red-500/20 dark:border-red-500/30 rounded-[20px] mb-6 shadow-sm flex flex-col relative z-20">
                    <div class="px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Link :href="route('after-sales.cancellations.index')" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            </Link>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Efetivar Distrato</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    Cancelamento de Contrato
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    
                    <!-- Passo 1: Busca (Estilo Global Search) -->
                    <div class="bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden transition-all" :class="{'ring-2 ring-indigo-500/20 dark:ring-cyan-500/20': true}">
                        <div class="relative p-4 sm:p-5">
                            <svg class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                v-model="searchTerm"
                                type="text" 
                                placeholder="PESQUISAR CLIENTE OU CONTRATO..."
                                class="w-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-gray-600 font-black uppercase tracking-widest pl-10 pr-12 text-sm"
                                autocomplete="off"
                            >
                            <div v-if="isSearching" class="absolute right-6 top-1/2 -translate-y-1/2">
                                <div class="w-4 h-4 border-2 border-indigo-500/20 border-t-indigo-500 dark:border-cyan-500/20 dark:border-t-cyan-500 rounded-full animate-spin"></div>
                            </div>
                        </div>

                        <!-- Results Dropdown -->
                        <div v-if="searchResults.length > 0" class="border-t border-slate-100 dark:border-white/5 max-h-[40vh] overflow-y-auto">
                            <div 
                                v-for="(result, index) in searchResults" 
                                :key="index"
                                @click="selectProposal(result)"
                                class="px-5 py-3 cursor-pointer flex items-center justify-between group transition-colors hover:bg-slate-50 dark:hover:bg-white/5 border-b border-slate-50 dark:border-white/5 last:border-0"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-black/20 flex items-center justify-center text-lg border border-slate-200 dark:border-white/10 shadow-inner">
                                        ✍️
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ result.proposal?.client?.nome }}</p>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            <span class="text-[10px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">Contrato: {{ result.proposal?.contract_number }}</span>
                                            <span class="text-[10px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest">CPF: {{ result.proposal?.client?.cpf }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-[9px] font-black text-indigo-500 dark:text-cyan-400 uppercase tracking-widest">Selecionar</span>
                                    <svg class="w-4 h-4 text-indigo-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="searchError && searchResults.length === 0" class="px-6 pb-4 pt-4 border-t border-slate-100 dark:border-white/5 bg-red-50 dark:bg-red-500/10">
                            <p class="text-[11px] font-black text-red-600 dark:text-red-500 uppercase tracking-widest flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ searchError }}
                            </p>
                        </div>
                    </div>

                    <!-- Dados do Contrato Encontrado -->
                    <div v-if="proposalData && financialData" class="space-y-6 animate-fade-in-up">
                        
                        <!-- Header do Contrato -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm flex flex-col sm:flex-row items-center gap-6 justify-between relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-32 h-32 bg-slate-50 dark:bg-slate-800/20 rounded-full translate-x-10 -translate-y-10 blur-2xl"></div>
                            
                            <div class="flex items-center gap-4 relative z-10">
                                <div class="w-14 h-14 rounded-2xl bg-brand-green/10 flex items-center justify-center text-brand-green">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Contrato Localizado</p>
                                    <h4 class="text-xl font-bold text-slate-900 dark:text-white">#{{ proposalData.contract_number }}</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">{{ proposalData.client?.name }}</p>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 w-full sm:w-auto relative z-10">
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-100 dark:border-slate-800 flex-1 sm:flex-none">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Já Pago</p>
                                    <p class="text-lg font-bold text-brand-green">{{ formatCurrency(financialData.total_paid) }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-100 dark:border-slate-800 flex-1 sm:flex-none">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Saldo Devedor</p>
                                    <p class="text-lg font-bold text-slate-400">{{ formatCurrency(financialData.total_pending) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Formulário -->
                        <form @submit.prevent="submitCancellation" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm space-y-6">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                                <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs">2</span>
                                Dados do Distrato
                            </h3>

                            <!-- Motivos -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Motivo do Cancelamento</label>
                                <select 
                                    v-model="form.reason" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50"
                                    required
                                >
                                    <option value="" disabled selected>Selecione um motivo...</option>
                                    <option v-for="reason in reasons" :key="reason" :value="reason" class="bg-white dark:bg-slate-800">{{ reason }}</option>
                                </select>
                                <p v-if="form.errors.reason" class="text-xs text-red-500 mt-1">{{ form.errors.reason }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Relatório / Observações (Opcional)</label>
                                <textarea 
                                    v-model="form.details" 
                                    rows="3" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 resize-none"
                                    placeholder="Descreva detalhes sobre o atendimento, tratativas de retenção, etc..."
                                ></textarea>
                            </div>

                            <!-- Valores -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-5 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-800">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1 flex items-center justify-between">
                                        Multa Retida (R$)
                                        <span class="text-[9px] text-slate-400 font-medium normal-case">Valor que a empresa vai reter</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold">R$</div>
                                        <input 
                                            :value="displayFineAmount"
                                            @input="handleFineInput"
                                            type="text" inputmode="numeric"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-3 text-slate-900 dark:text-white font-bold outline-none focus:border-brand-green/50"
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1 flex items-center justify-between">
                                        Valor a Estornar (R$)
                                        <span class="text-[9px] text-slate-400 font-medium normal-case">Valor devolvido ao cliente</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold">R$</div>
                                        <input 
                                            :value="displayRefundAmount"
                                            @input="handleRefundInput"
                                            type="text" inputmode="numeric"
                                            class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-3 text-red-500 font-bold outline-none focus:border-red-500/50"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Opção de Estorno Total -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700 rounded-xl">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.cancel_all_bills"
                                            class="w-4 h-4 text-brand-green bg-white border-slate-300 rounded focus:ring-brand-green dark:focus:ring-brand-green dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">O valor total pago foi devolvido?</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Desejo inativar todas as parcelas deste contrato, inclusive as que já constam como pagas.
                                        </p>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Alerta de Segurança -->
                            <div class="flex gap-3 p-4 bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/20 rounded-xl">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <p class="text-xs font-bold text-yellow-800 dark:text-yellow-500">Atenção: Ação Irreversível</p>
                                    <p v-if="!form.cancel_all_bills" class="text-[11px] text-yellow-700 dark:text-yellow-600 mt-0.5">
                                        Ao confirmar, o contrato mudará para o status "Cancelado" e todas as parcelas <span class="font-bold">pendentes</span> no financeiro serão inativadas.
                                    </p>
                                    <p v-else class="text-[11px] text-red-600 dark:text-red-400 font-medium mt-0.5">
                                        Ao confirmar, o contrato mudará para o status "Cancelado" e <span class="font-bold uppercase underline">TODAS</span> as parcelas (incluindo as já pagas) serão inativadas e receberão uma observação.
                                    </p>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end gap-3">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="bg-red-500 hover:bg-red-600 text-white px-8 py-3.5 rounded-[12px] text-sm font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50 flex items-center gap-2"
                                >
                                    <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Efetivar Cancelamento
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
