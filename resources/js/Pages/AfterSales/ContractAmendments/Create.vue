<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    products: Array,
    paymentMethods: Array,
    initialProposalId: [String, Number],
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    proposal_id: null,
    type: 'upgrade',
    product_id: '',
    total_value: 0,
    quantity: null,
    reason: '',
    details: '',
    charge_contract_fee: false,
    commission_reversal_responsible: 'undefined',
    payments: [],
});

const searchTerm = ref('');
const isSearching = ref(false);
const searchError = ref('');
const searchResults = ref([]);
const proposal = ref(null);
const financial = ref(null);
const preview = ref(null);

const reasons = [
    'Solicitação do cliente - aumento de produto',
    'Solicitação do cliente - redução de produto',
    'Dificuldade financeira',
    'Readequação de pontuação',
    'Outros',
];

const categoryLabels = {
    entrada: 'Entrada',
    saldo: 'Saldo',
    taxa_contrato: 'Taxa de Contrato',
    taxa_manutencao: 'Taxa de Manutenção',
    credito_contrato_anterior: 'Valor Pago no Contrato Anterior',
};

const formatCurrency = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
const round2 = (v) => Math.round((Number(v) || 0) * 100) / 100;

const searchContract = async (params) => {
    isSearching.value = true;
    searchError.value = '';
    searchResults.value = [];
    try {
        const { data } = await axios.get(route('after-sales.contract-amendments.search'), { params });
        searchResults.value = data;
        return data;
    } catch (e) {
        searchError.value = e.response?.data?.error || 'Erro ao buscar contrato.';
        return [];
    } finally {
        isSearching.value = false;
    }
};

let searchTimeout;
watch(searchTerm, (val) => {
    clearTimeout(searchTimeout);
    if (!val || val.length < 3) { searchResults.value = []; return; }
    searchTimeout = setTimeout(() => searchContract({ term: val }), 500);
});

const selectProposal = (result) => {
    proposal.value = result.proposal;
    financial.value = result.financial;
    searchResults.value = [];
    searchTerm.value = '';
    form.reset();
    form.proposal_id = result.proposal.id;
    form.product_id = result.proposal.product_id;
    form.total_value = Number(result.proposal.total_value);
    form.quantity = result.proposal.quantity;
    form.payments = [];
    refreshPreview();
};

onMounted(async () => {
    if (props.initialProposalId) {
        const data = await searchContract({ id: props.initialProposalId });
        if (data.length) selectProposal(data[0]);
    }
});

// Sugere o tipo de acordo com o novo valor
watch(() => form.total_value, (val) => {
    if (!proposal.value) return;
    const old = Number(proposal.value.total_value);
    if (Number(val) > old) form.type = 'upgrade';
    else if (Number(val) < old) form.type = 'downgrade';
});

let previewTimeout;
const refreshPreview = () => {
    clearTimeout(previewTimeout);
    if (!form.proposal_id) return;
    previewTimeout = setTimeout(async () => {
        const { data } = await axios.post(route('after-sales.contract-amendments.preview', form.proposal_id), { total_value: form.total_value || 0 });
        preview.value = data;
    }, 300);
};
watch(() => form.total_value, refreshPreview);

const onProductChange = () => {
    const p = props.products.find((x) => x.id === Number(form.product_id));
    if (p) {
        form.total_value = Number(p.price);
        form.quantity = p.quantity;
    }
};

const addPayment = (category) => {
    const remaining = round2(remainingToAllocate.value);
    form.payments.push({
        category,
        payment_method: props.paymentMethods[0] || '',
        installments: 1,
        installment_value: category === 'taxa_contrato' ? 0 : Math.max(remaining, 0),
        total_value: category === 'taxa_contrato' ? 0 : Math.max(remaining, 0),
        start_date: today,
    });
};
const removePayment = (i) => form.payments.splice(i, 1);
const syncInstallment = (p) => { p.installment_value = round2(Number(p.total_value) / Math.max(1, Number(p.installments))); };

const negotiatedPrincipal = computed(() => round2(form.payments.filter((p) => ['entrada', 'saldo'].includes(p.category)).reduce((s, p) => s + Number(p.total_value || 0), 0)));
const remainingToAllocate = computed(() => round2((preview.value?.remaining_amount || 0) - negotiatedPrincipal.value));
const isBalanced = computed(() => Math.abs(remainingToAllocate.value) <= 0.01);

watch(() => form.charge_contract_fee, (on) => {
    if (!on) form.payments = form.payments.filter((p) => p.category !== 'taxa_contrato');
});

const currentPayments = computed(() => proposal.value?.payments || []);

const submit = () => {
    form.post(route('after-sales.contract-amendments.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Novo Upgrade / Downgrade" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 space-y-6">

                <div class="bg-white dark:bg-slate-900 border border-indigo-500/20 rounded-[20px] shadow-sm px-6 py-5 flex items-center gap-4">
                    <Link :href="route('after-sales.contract-amendments.index')" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Upgrade / Downgrade de Contrato</h2>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">O número do contrato é mantido e a versão anterior fica guardada como espelho</p>
                    </div>
                </div>

                <!-- Busca -->
                <div class="bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                    <div class="relative p-4 sm:p-5">
                        <input v-model="searchTerm" type="text" placeholder="PESQUISAR CONTRATO, CLIENTE OU CPF..." autocomplete="off"
                            class="w-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder:text-slate-400 font-black uppercase tracking-widest text-sm">
                        <div v-if="isSearching" class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 border-2 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin"></div>
                    </div>
                    <div v-if="searchResults.length" class="border-t border-slate-100 dark:border-white/5 max-h-[40vh] overflow-y-auto">
                        <div v-for="r in searchResults" :key="r.proposal.id" @click="selectProposal(r)"
                            class="px-5 py-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 border-b border-slate-50 dark:border-white/5 flex justify-between">
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ r.proposal.client?.nome }}</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Contrato {{ r.proposal.contract_number }} · v{{ r.proposal.current_version }} · {{ r.proposal.product?.name }}</p>
                            </div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatCurrency(r.proposal.total_value) }}</span>
                        </div>
                    </div>
                    <p v-if="searchError && !searchResults.length" class="px-6 py-3 text-[11px] font-black text-red-600 uppercase tracking-widest bg-red-50 dark:bg-red-500/10">{{ searchError }}</p>
                </div>

                <template v-if="proposal && financial">
                    <!-- Contrato atual -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Contrato vigente (versão {{ financial.current_version }})</p>
                                <h4 class="text-xl font-bold text-slate-900 dark:text-white">#{{ proposal.contract_number }} · {{ proposal.product?.name }}</h4>
                                <p class="text-sm text-slate-500">{{ proposal.client?.nome }} · CPF {{ proposal.client?.cpf }}</p>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-right">
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-2"><p class="text-[9px] font-bold text-slate-500 uppercase">Valor</p><p class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(financial.total_value) }}</p></div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-2"><p class="text-[9px] font-bold text-slate-500 uppercase">Pago (entr.+saldo)</p><p class="font-bold text-emerald-600">{{ formatCurrency(financial.paid_principal) }}</p></div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-2"><p class="text-[9px] font-bold text-slate-500 uppercase">Em aberto</p><p class="font-bold text-slate-500">{{ formatCurrency(financial.open_principal) }}</p></div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl px-4 py-2"><p class="text-[9px] font-bold text-slate-500 uppercase">Saldo credor</p><p class="font-bold text-amber-600">{{ formatCurrency(financial.credit_balance) }}</p></div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <p class="font-bold uppercase tracking-widest text-[10px] text-slate-500 mb-1">Negociação atual</p>
                            <ul class="space-y-0.5">
                                <li v-for="p in currentPayments" :key="p.id">{{ categoryLabels[p.category] || p.category }}: {{ p.installments }}x {{ formatCurrency(p.installment_value) }} ({{ p.payment_method }}) = <b>{{ formatCurrency(p.total_value) }}</b></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Formulário -->
                    <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Tipo</label>
                                <select v-model="form.type" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                                    <option value="upgrade">Upgrade</option>
                                    <option value="downgrade">Downgrade</option>
                                </select>
                            </div>
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Novo produto</label>
                                <select v-model="form.product_id" @change="onProductChange" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} — {{ formatCurrency(p.price) }}</option>
                                </select>
                                <p v-if="form.errors.product_id" class="text-xs text-red-500">{{ form.errors.product_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Pontuação</label>
                                <input v-model.number="form.quantity" type="number" min="0" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                            </div>
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Novo valor do contrato (entrada + saldo)</label>
                                <input v-model.number="form.total_value" type="number" step="0.01" min="0" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-900 dark:text-white">
                                <p v-if="form.errors.total_value" class="text-xs text-red-500">{{ form.errors.total_value }}</p>
                            </div>
                        </div>

                        <!-- Cálculo -->
                        <div v-if="preview" class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-xl bg-indigo-50/60 dark:bg-cyan-500/5 border border-indigo-100 dark:border-cyan-500/20 text-sm">
                            <div><p class="text-[9px] font-bold text-slate-500 uppercase">Diferença</p><p class="font-bold" :class="preview.difference_amount >= 0 ? 'text-emerald-600' : 'text-amber-600'">{{ formatCurrency(preview.difference_amount) }}</p></div>
                            <div><p class="text-[9px] font-bold text-slate-500 uppercase">Pago que entra no novo contrato</p><p class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(preview.credit_applied) }}</p></div>
                            <div><p class="text-[9px] font-bold text-slate-500 uppercase">Sobra (saldo credor)</p><p class="font-bold text-amber-600">{{ formatCurrency(preview.credit_balance) }}</p></div>
                            <div><p class="text-[9px] font-bold text-slate-500 uppercase">Restante a negociar</p><p class="font-bold text-indigo-600 dark:text-cyan-400">{{ formatCurrency(preview.remaining_amount) }}</p></div>
                            <p v-if="preview.credit_balance > 0" class="col-span-2 sm:col-span-4 text-[11px] text-amber-700 dark:text-amber-400">
                                O valor pago é maior que o novo valor. A sobra não será devolvida automaticamente: fica registrada como saldo credor do cliente para tratativa posterior.
                            </p>
                        </div>

                        <!-- Nova negociação -->
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Nova negociação</h3>
                                <div class="flex gap-2">
                                    <button type="button" @click="addPayment('entrada')" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">+ Entrada</button>
                                    <button type="button" @click="addPayment('saldo')" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">+ Saldo</button>
                                    <button v-if="form.charge_contract_fee" type="button" @click="addPayment('taxa_contrato')" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">+ Taxa de contrato</button>
                                </div>
                            </div>

                            <div v-if="preview && preview.credit_applied > 0" class="flex items-center justify-between text-xs px-4 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                <span>Valor Pago no Contrato Anterior (lançado automaticamente)</span>
                                <b>{{ formatCurrency(preview.credit_applied) }}</b>
                            </div>
                            <div class="text-xs px-4 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/40 text-slate-500">
                                A taxa de manutenção e a taxa de contrato já negociadas são mantidas como estão.
                            </div>

                            <div v-for="(p, i) in form.payments" :key="i" class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase self-center">{{ categoryLabels[p.category] }}</div>
                                <select v-model="p.payment_method" class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-2 text-xs text-slate-900 dark:text-white">
                                    <option v-for="m in paymentMethods" :key="m" :value="m">{{ m }}</option>
                                </select>
                                <label class="text-[9px] uppercase text-slate-500">Total<input v-model.number="p.total_value" @input="syncInstallment(p)" type="number" step="0.01" min="0" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-2 text-xs text-slate-900 dark:text-white"></label>
                                <label class="text-[9px] uppercase text-slate-500">Parcelas<input v-model.number="p.installments" @input="syncInstallment(p)" type="number" min="1" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-2 text-xs text-slate-900 dark:text-white"></label>
                                <label class="text-[9px] uppercase text-slate-500">1º venc.<input v-model="p.start_date" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-2 text-xs text-slate-900 dark:text-white"></label>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-slate-500">{{ p.installments }}x {{ formatCurrency(p.installment_value) }}</span>
                                    <button type="button" @click="removePayment(i)" class="text-red-500 text-xs font-bold">Remover</button>
                                </div>
                            </div>

                            <p class="text-xs font-bold" :class="isBalanced ? 'text-emerald-600' : 'text-red-500'">
                                Entrada + saldo negociados: {{ formatCurrency(negotiatedPrincipal) }} · falta alocar: {{ formatCurrency(remainingToAllocate) }}
                            </p>
                            <p v-if="form.errors.payments" class="text-xs text-red-500">{{ form.errors.payments }}</p>
                        </div>

                        <label class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer">
                            <input type="checkbox" v-model="form.charge_contract_fee" class="mt-0.5 w-4 h-4 rounded">
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">Cobrar nova taxa de contrato neste aditivo</p>
                                <p class="text-xs text-slate-500">Se marcado, adicione a linha "Taxa de contrato" na nova negociação.</p>
                            </div>
                        </label>

                        <div v-if="form.type === 'downgrade'" class="space-y-1 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
                            <label class="text-[10px] font-bold text-amber-800 dark:text-amber-400 uppercase tracking-widest">Estorno de comissão já paga — responsável</label>
                            <select v-model="form.commission_reversal_responsible" class="w-full bg-white dark:bg-slate-800 border border-amber-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                                <option value="undefined">A definir</option>
                                <option value="employee">Funcionário (descontar da comissão)</option>
                                <option value="client">Cliente (cobrar do cliente)</option>
                            </select>
                            <p class="text-[11px] text-amber-700 dark:text-amber-500">A redução da comissão é abatida primeiro das parcelas de comissão ainda não pagas; o que já foi pago fica registrado como valor a recuperar.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Motivo</label>
                                <select v-model="form.reason" required class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-900 dark:text-white">
                                    <option value="" disabled>Selecione...</option>
                                    <option v-for="r in reasons" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <p v-if="form.errors.reason" class="text-xs text-red-500">{{ form.errors.reason }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Observações</label>
                                <textarea v-model="form.details" rows="2" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white resize-none"></textarea>
                            </div>
                        </div>

                        <p v-if="form.errors.proposal_id" class="text-xs text-red-500">{{ form.errors.proposal_id }}</p>

                        <div class="flex gap-3 p-4 bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/20 rounded-xl text-[11px] text-yellow-800 dark:text-yellow-500">
                            Ao confirmar: o contrato #{{ proposal.contract_number }} passa para a versão {{ financial.current_version + 1 }}, as parcelas de entrada/saldo em aberto são substituídas (canceladas com referência ao aditivo), as pagas continuam pagas e a versão atual fica salva como espelho.
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing || !isBalanced || !form.reason"
                                class="bg-indigo-600 hover:bg-indigo-500 dark:bg-cyan-600 text-white px-8 py-3.5 rounded-[12px] text-sm font-bold uppercase tracking-wider disabled:opacity-50">
                                Confirmar {{ form.type === 'upgrade' ? 'Upgrade' : 'Downgrade' }}
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
