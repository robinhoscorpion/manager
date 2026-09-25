<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    rules: Array,
    products: Array,
    qualifications: Array,
});

const isModalOpen = ref(false);
const editingRule = ref(null);

const form = useForm({
    name: '',
    role_column: '',
    rule_type: 'percentage',
    percentage: '',
    distribution_base_percentage: 15.00,
    cash_installments: 1,
    credit_installments: 3,
    boleto_installments_type: 'dynamic',
    boleto_fixed_installments: null,
    score_rules: [],
    qualification_rules: [],
});

const roleColumns = [
    { value: 'liner_id', label: 'Liner (liner_id)' },
    { value: 'closer_id', label: 'Closer (closer_id)' },
    { value: 'opc_id', label: 'OPC / Promotor (opc_id)' },
    { value: 'mkt_id', label: 'Marketing (mkt_id)' },
];

const defaultQualificationRules = [
    { code: 'A1', amount: 40.00 },
    { code: 'A2', amount: 30.00 },
    { code: 'B1', amount: 20.00 },
    { code: 'B2', amount: 10.00 },
];

// Switch rule_type auto-fill
watch(() => form.rule_type, (newType) => {
    if (newType === 'opc') {
        if (!form.score_rules || form.score_rules.length === 0) {
            // Pre-fill with available products if any, else default row
            if (props.products && props.products.length > 0) {
                form.score_rules = props.products.slice(0, 4).map(p => ({
                    product_id: p.id,
                    min_points: p.quantity || 0,
                    amount: 50.00
                }));
            } else {
                form.score_rules = [
                    { product_id: '', min_points: 200000, amount: 50.00 },
                    { product_id: '', min_points: 400000, amount: 75.00 },
                ];
            }
        }
        if (!form.qualification_rules || form.qualification_rules.length === 0) {
            // Pre-fill with qualifications from props if available
            if (props.qualifications && props.qualifications.length > 0) {
                form.qualification_rules = props.qualifications.slice(0, 4).map(q => ({
                    code: q.code,
                    amount: q.code === 'A1' ? 40 : (q.code === 'A2' ? 30 : (q.code === 'B1' ? 20 : 10))
                }));
            } else {
                form.qualification_rules = JSON.parse(JSON.stringify(defaultQualificationRules));
            }
        }
    }
});

const onProductChange = (rule) => {
    const prod = props.products?.find(p => p.id === rule.product_id);
    if (prod) {
        rule.min_points = prod.quantity || 0;
    }
};

const addScoreRule = () => {
    form.score_rules.push({ product_id: '', min_points: '', amount: '' });
};

const removeScoreRule = (index) => {
    form.score_rules.splice(index, 1);
};

const addQualificationRule = () => {
    form.qualification_rules.push({ code: '', amount: '' });
};

const removeQualificationRule = (index) => {
    form.qualification_rules.splice(index, 1);
};

const openModal = (rule = null) => {
    editingRule.value = rule;
    if (rule) {
        form.name = rule.name;
        form.role_column = rule.role_column;
        form.rule_type = rule.rule_type || (rule.role_column === 'opc_id' ? 'opc' : 'percentage');
        form.percentage = rule.percentage;
        form.distribution_base_percentage = rule.distribution_base_percentage;
        form.cash_installments = rule.cash_installments;
        form.credit_installments = rule.credit_installments;
        form.boleto_installments_type = rule.boleto_installments_type;
        form.boleto_fixed_installments = rule.boleto_fixed_installments;
        form.score_rules = rule.score_rules ? JSON.parse(JSON.stringify(rule.score_rules)) : [];
        form.qualification_rules = rule.qualification_rules ? JSON.parse(JSON.stringify(rule.qualification_rules)) : [];
    } else {
        form.reset();
        form.rule_type = 'percentage';
        form.distribution_base_percentage = 15.00;
        form.cash_installments = 1;
        form.credit_installments = 3;
        form.boleto_installments_type = 'dynamic';
        form.score_rules = [];
        form.qualification_rules = [];
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingRule.value) {
        form.put(route('commissions.rules.update', editingRule.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('commissions.rules.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteRule = (id) => {
    if (confirm('Tem certeza que deseja excluir esta regra? O cálculo futuro de comissões não usará mais este cargo.')) {
        router.delete(route('commissions.rules.destroy', id));
    }
};

const formatCurrency = (val) => {
    if (val === null || val === undefined || val === '') return 'R$ 0,00';
    return Number(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const formatNumber = (val) => {
    if (val === null || val === undefined || val === '') return '0';
    return Number(val).toLocaleString('pt-BR');
};

const getProductName = (productId) => {
    if (!productId) return null;
    const prod = props.products?.find(p => p.id === productId);
    return prod ? prod.name : null;
};

const getQualificationLabel = (code) => {
    if (!code) return '';
    const qual = props.qualifications?.find(q => q.code === code);
    return qual ? `${qual.code} (${qual.name})` : code;
};
</script>

<template>
    <Head title="Regras de Comissão" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8">
                
                <!-- Premium Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Regras de Comissão</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Motor Dinâmico (Percentual / Tabelas OPC)
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto">
                            <button 
                                @click="openModal()"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 cursor-pointer"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Nova Regra</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="mb-6 bg-brand-green/10 border-l-4 border-brand-green p-4 rounded-r-[12px]">
                    <p class="text-sm text-brand-green/90 dark:text-brand-green/80 font-medium">
                        O motor de comissões suporta cálculos por <strong>Porcentagem da Venda</strong> (Liner, Closer) e por <strong>Tabelas Dinâmicas de Produtos e Qualificações Cadastradas</strong> (Promotores OPC), com pagamento em parcela única no mês subsequente.
                    </p>
                </div>

                <!-- Data Table -->
                <div class="flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200/50 dark:divide-slate-700/50">
                            <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome da Regra</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gatilho</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo / Modelo</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Valores / Tabelas</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parcelamento</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                                <tr v-for="rule in rules" :key="rule.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                        {{ rule.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono bg-slate-100/50 dark:bg-slate-800 rounded px-2 py-1 inline-block">
                                        {{ rule.role_column }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                                        <span v-if="rule.rule_type === 'opc' || rule.role_column === 'opc_id'" class="px-2.5 py-1 rounded-full text-xs bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                            Promotor OPC (Tabelas)
                                        </span>
                                        <span v-else class="px-2.5 py-1 rounded-full text-xs bg-brand-green/10 text-brand-green border border-brand-green/20">
                                            Percentual da Venda
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                        <!-- OPC Rules View -->
                                        <template v-if="rule.rule_type === 'opc' || rule.role_column === 'opc_id'">
                                            <div class="space-y-1.5 text-xs">
                                                <div v-if="rule.score_rules && rule.score_rules.length">
                                                    <span class="font-semibold text-slate-700 dark:text-slate-300">Produtos / Pontuação:</span>
                                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                                        <span v-for="sr in rule.score_rules" :key="sr.product_id || sr.min_points" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[11px] font-mono border border-slate-200 dark:border-slate-700">
                                                            <template v-if="getProductName(sr.product_id)">
                                                                {{ getProductName(sr.product_id) }} ({{ formatNumber(sr.min_points) }} pts): <strong>{{ formatCurrency(sr.amount) }}</strong>
                                                            </template>
                                                            <template v-else>
                                                                {{ formatNumber(sr.min_points) }} pts: <strong>{{ formatCurrency(sr.amount) }}</strong>
                                                            </template>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div v-if="rule.qualification_rules && rule.qualification_rules.length">
                                                    <span class="font-semibold text-slate-700 dark:text-slate-300">Qualificação:</span>
                                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                                        <span v-for="qr in rule.qualification_rules" :key="qr.code" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[11px] font-mono border border-slate-200 dark:border-slate-700">
                                                            {{ getQualificationLabel(qr.code) }}: <strong>{{ formatCurrency(qr.amount) }}</strong>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Percentage View -->
                                        <template v-else>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-brand-green text-sm">{{ rule.percentage }}%</span>
                                                <span class="text-xs text-slate-400">(Base: {{ rule.distribution_base_percentage }}%)</span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                        <template v-if="rule.rule_type === 'opc' || rule.role_column === 'opc_id'">
                                            <span class="px-2 py-1 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20 rounded text-xs font-semibold">
                                                1x Mês Subsequente (M+1)
                                            </span>
                                        </template>
                                        <template v-else>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="À Vista">{{ rule.cash_installments }}x à vista</span>
                                                <span class="text-slate-300">/</span>
                                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="Cartão">{{ rule.credit_installments }}x cartão</span>
                                                <span class="text-slate-300">/</span>
                                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="Boleto">
                                                    {{ rule.boleto_installments_type === 'dynamic' ? 'Nx boleto' : (rule.boleto_fixed_installments + 'x boleto') }}
                                                </span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openModal(rule)" class="text-brand-green hover:text-[#485638] dark:hover:text-brand-green/80 mr-4 transition-colors font-semibold">Editar</button>
                                        <button @click="deleteRule(rule.id)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors font-semibold">Excluir</button>
                                    </td>
                                </tr>
                                <tr v-if="!rules.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">Nenhuma regra cadastrada. Adicione uma regra para começar a gerar comissões.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" :class="isModalOpen ? 'pointer-events-auto' : 'pointer-events-none'">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="isModalOpen" class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                </Transition>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div v-show="isModalOpen" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-[20px] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200 dark:border-slate-800">
                        <form @submit.prevent="submit">
                        <div class="bg-white dark:bg-slate-900 px-6 pt-6 pb-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-xl leading-6 font-bold text-slate-900 dark:text-white" id="modal-title">
                                    {{ editingRule ? 'Editar Regra' : 'Nova Regra de Comissão' }}
                                </h3>
                                <button type="button" @click="closeModal" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors focus:outline-none bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full p-2">
                                    <span class="sr-only">Fechar</span>
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="flex flex-col gap-6">
                                <!-- Informações Básicas -->
                                <div class="space-y-4">
                                    <h4 class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2">Informações Básicas</h4>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Nome da Regra</label>
                                            <input type="text" v-model="form.name" placeholder="Ex: Promotor OPC ou Liner" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Vínculo na Venda (Gatilho)</label>
                                            <select v-model="form.role_column" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                                <option disabled value="">Selecione a coluna...</option>
                                                <option v-for="col in roleColumns" :key="col.value" :value="col.value">{{ col.label }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Modelo de Cálculo</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <label 
                                                class="flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                                :class="form.rule_type === 'percentage' ? 'border-brand-green bg-brand-green/5 dark:bg-brand-green/10' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                                            >
                                                <input type="radio" value="percentage" v-model="form.rule_type" class="text-brand-green focus:ring-brand-green h-4 w-4">
                                                <div class="ml-3">
                                                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Porcentagem da Venda</span>
                                                    <span class="block text-[11px] text-slate-500">Ideal para Liner e Closer (% sobre valor)</span>
                                                </div>
                                            </label>

                                            <label 
                                                class="flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                                :class="form.rule_type === 'opc' ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-500/10' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                                            >
                                                <input type="radio" value="opc" v-model="form.rule_type" class="text-amber-500 focus:ring-amber-500 h-4 w-4">
                                                <div class="ml-3">
                                                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Tabelas Dinâmicas OPC</span>
                                                    <span class="block text-[11px] text-slate-500">Produtos Cadastrados + Qualificações</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== MODELO 1: PORCENTAGEM ===== -->
                                <div v-if="form.rule_type === 'percentage'" class="space-y-4">
                                    <h4 class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2">Matemática e Parcelamento</h4>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Porcentagem (%)</label>
                                            <input type="number" step="0.01" v-model="form.percentage" placeholder="Ex: 2.00" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" :required="form.rule_type === 'percentage'">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Base da Venda (%)</label>
                                            <input type="number" step="0.01" v-model="form.distribution_base_percentage" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" :required="form.rule_type === 'percentage'">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: À Vista</label>
                                            <input type="number" min="1" v-model="form.cash_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" :required="form.rule_type === 'percentage'">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: Cartão</label>
                                            <input type="number" min="1" v-model="form.credit_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" :required="form.rule_type === 'percentage'">
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: Boleto</label>
                                            <select v-model="form.boleto_installments_type" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors">
                                                <option value="dynamic">Dinâmico (Acompanha Qtd. do Cliente)</option>
                                                <option value="fixed">Fixo (Definir quantidade manual)</option>
                                            </select>
                                        </div>
                                        <div v-if="form.boleto_installments_type === 'fixed'">
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Qtd. Parcelas Fixo</label>
                                            <input type="number" min="1" v-model="form.boleto_fixed_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors">
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== MODELO 2: TABELAS DINÂMICAS OPC ===== -->
                                <div v-if="form.rule_type === 'opc'" class="space-y-6">
                                    <!-- Alert M+1 -->
                                    <div class="p-3.5 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl text-xs text-amber-700 dark:text-amber-300 font-medium">
                                        💡 <strong>Forma de Pagamento Promotores OPC:</strong> Pagamento único no mês subsequente (M+1), referente ao produto/pontuação do contrato e qualificação do cliente.
                                    </div>

                                    <!-- 1. TABELA DE PONTUAÇÕES / PRODUTOS -->
                                    <div class="space-y-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-[16px] border border-slate-200/80 dark:border-slate-800">
                                        <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700 pb-2">
                                            <div>
                                                <h5 class="font-bold text-sm text-slate-900 dark:text-white">1. Tabela de Pontuações por Produto</h5>
                                                <p class="text-[11px] text-slate-500">Selecione os produtos cadastrados ou informe a pontuação mínima para a comissão.</p>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="addScoreRule"
                                                class="px-3 py-1.5 text-xs font-bold bg-brand-green text-white rounded-lg hover:bg-[#485638] transition-colors flex items-center gap-1 cursor-pointer"
                                            >
                                                + Adicionar Produto / Pontuação
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <div 
                                                v-for="(rule, idx) in form.score_rules" 
                                                :key="idx"
                                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-700"
                                            >
                                                <!-- Select de Produtos Cadastrados -->
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Produto Cadastrado</label>
                                                    <select 
                                                        v-model="rule.product_id" 
                                                        @change="onProductChange(rule)"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                                                    >
                                                        <option value="">Selecione um produto cadastrado...</option>
                                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                                            {{ product.name }} ({{ formatNumber(product.quantity) }} pts - {{ formatCurrency(product.price) }})
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Campo Pontuação Mínima -->
                                                <div class="w-full sm:w-36">
                                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pontos (Qtd)</label>
                                                    <input 
                                                        type="number" 
                                                        step="1"
                                                        v-model="rule.min_points" 
                                                        placeholder="Ex: 200000"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                                                        required
                                                    />
                                                </div>

                                                <!-- Campo Comissão em R$ -->
                                                <div class="w-full sm:w-36">
                                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Comissão (R$)</label>
                                                    <input 
                                                        type="number" 
                                                        step="0.01"
                                                        v-model="rule.amount" 
                                                        placeholder="50.00"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                                                        required
                                                    />
                                                </div>

                                                <button 
                                                    type="button" 
                                                    @click="removeScoreRule(idx)"
                                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors sm:mt-4 self-end sm:self-center"
                                                    title="Remover Item"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>

                                            <div v-if="!form.score_rules.length" class="text-center py-4 text-xs text-slate-400 italic">
                                                Nenhum produto/pontuação cadastrado. Clique em "+ Adicionar Produto / Pontuação".
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. TABELA DE QUALIFICAÇÕES -->
                                    <div class="space-y-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-[16px] border border-slate-200/80 dark:border-slate-800">
                                        <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700 pb-2">
                                            <div>
                                                <h5 class="font-bold text-sm text-slate-900 dark:text-white">2. Tabela de Qualificações</h5>
                                                <p class="text-[11px] text-slate-500">Selecione as qualificações cadastradas no sistema ou digite o código correspondente.</p>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="addQualificationRule"
                                                class="px-3 py-1.5 text-xs font-bold bg-brand-green text-white rounded-lg hover:bg-[#485638] transition-colors flex items-center gap-1 cursor-pointer"
                                            >
                                                + Adicionar Qualificação
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <div 
                                                v-for="(rule, idx) in form.qualification_rules" 
                                                :key="idx"
                                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-700"
                                            >
                                                <!-- Select de Qualificações Cadastradas -->
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Qualificação Cadastrada</label>
                                                    <select 
                                                        v-model="rule.code" 
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white uppercase focus:border-brand-green focus:ring-brand-green"
                                                        required
                                                    >
                                                        <option value="">Selecione uma qualificação...</option>
                                                        <option v-for="qual in qualifications" :key="qual.id" :value="qual.code">
                                                            {{ qual.code }} - {{ qual.name }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Valor Comissão em R$ -->
                                                <div class="w-full sm:w-48">
                                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Valor Comissão (R$)</label>
                                                    <input 
                                                        type="number" 
                                                        step="0.01"
                                                        v-model="rule.amount" 
                                                        placeholder="40.00"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                                                        required
                                                    />
                                                </div>

                                                <button 
                                                    type="button" 
                                                    @click="removeQualificationRule(idx)"
                                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors sm:mt-4 self-end sm:self-center"
                                                    title="Remover Qualificação"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>

                                            <div v-if="!form.qualification_rules.length" class="text-center py-4 text-xs text-slate-400 italic">
                                                Nenhuma qualificação cadastrada. Clique em "+ Adicionar Qualificação".
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/80 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-[20px] border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-brand-green text-base font-semibold text-white hover:bg-[#485638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green sm:ml-3 sm:w-auto sm:text-sm transition-colors cursor-pointer" :disabled="form.processing">
                                Salvar Regra
                            </button>
                            <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 dark:border-slate-600 shadow-sm px-6 py-2.5 bg-white dark:bg-slate-700 text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors cursor-pointer">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                </Transition>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
