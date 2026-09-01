<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    rules: Array,
});

const isModalOpen = ref(false);
const editingRule = ref(null);

const form = useForm({
    name: '',
    role_column: '',
    percentage: '',
    distribution_base_percentage: 15.00,
    cash_installments: 1,
    credit_installments: 3,
    boleto_installments_type: 'dynamic',
    boleto_fixed_installments: null,
});

const roleColumns = [
    { value: 'liner_id', label: 'Liner (liner_id)' },
    { value: 'closer_id', label: 'Closer (closer_id)' },
    { value: 'opc_id', label: 'OPC (opc_id)' },
    { value: 'mkt_id', label: 'Marketing (mkt_id)' },
];

const openModal = (rule = null) => {
    editingRule.value = rule;
    if (rule) {
        form.name = rule.name;
        form.role_column = rule.role_column;
        form.percentage = rule.percentage;
        form.distribution_base_percentage = rule.distribution_base_percentage;
        form.cash_installments = rule.cash_installments;
        form.credit_installments = rule.credit_installments;
        form.boleto_installments_type = rule.boleto_installments_type;
        form.boleto_fixed_installments = rule.boleto_fixed_installments;
    } else {
        form.reset();
        // default settings
        form.distribution_base_percentage = 15.00;
        form.cash_installments = 1;
        form.credit_installments = 3;
        form.boleto_installments_type = 'dynamic';
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
                                    Motor Dinâmico
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto">
                            <button 
                                @click="openModal()"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
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
                        O motor de comissões irá iterar sobre todas as regras cadastradas abaixo na hora de calcular uma venda. A matemática é customizável para cada papel, permitindo configurar a base de distribuição e os parcelamentos para cada método de pagamento.
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
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Comissão</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Base da Venda</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parcelamento (À vista/Cartão/Boleto)</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                                <tr v-for="rule in rules" :key="rule.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{{ rule.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono bg-slate-100/50 dark:bg-slate-800 rounded px-2 py-1 mx-4 inline-block mt-3">{{ rule.role_column }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-brand-green">{{ rule.percentage }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ rule.distribution_base_percentage }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="À Vista">{{ rule.cash_installments }}x</span>
                                            <span class="text-slate-300">/</span>
                                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="Cartão de Crédito">{{ rule.credit_installments }}x</span>
                                            <span class="text-slate-300">/</span>
                                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs" title="Boleto">
                                                {{ rule.boleto_installments_type === 'dynamic' ? 'Nx (Acompanha Cliente)' : (rule.boleto_fixed_installments + 'x') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openModal(rule)" class="text-brand-green hover:text-[#485638] dark:hover:text-brand-green/80 mr-4 transition-colors">Editar</button>
                                        <button @click="deleteRule(rule.id)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">Excluir</button>
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
                                    {{ editingRule ? 'Editar Regra' : 'Nova Regra' }}
                                </h3>
                                <button type="button" @click="closeModal" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors focus:outline-none bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full p-2">
                                    <span class="sr-only">Fechar</span>
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="flex flex-col gap-6">
                                <!-- Dados Básicos -->
                                <div class="space-y-4">
                                    <h4 class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2">Informações Básicas</h4>
                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Nome (Ex: Liner)</label>
                                            <input type="text" v-model="form.name" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Vínculo na Venda</label>
                                            <select v-model="form.role_column" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                                <option disabled value="">Selecione a coluna...</option>
                                                <option v-for="col in roleColumns" :key="col.value" :value="col.value">{{ col.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Porcentagem (%)</label>
                                            <input type="number" step="0.01" v-model="form.percentage" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Matemática de Pagamento -->
                                <div class="space-y-4 mt-2">
                                    <h4 class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2">Matemática de Pagamento</h4>
                                    
                                    <div class="flex flex-col gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Base da Venda para Entrada (%)</label>
                                            <div class="text-xs text-slate-500 mb-1">Qual proporção da venda as parcelas acompanham? (Padrão: 15%)</div>
                                            <input type="number" step="0.01" v-model="form.distribution_base_percentage" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: À Vista (Pix/Espécie)</label>
                                            <div class="text-xs text-slate-500 mb-1">Qtd. de vezes a dividir se a entrada for à vista. (Padrão: 1)</div>
                                            <input type="number" min="1" v-model="form.cash_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: Cartão de Crédito</label>
                                            <div class="text-xs text-slate-500 mb-1">Qtd. de vezes a dividir se a entrada for cartão. (Padrão: 3)</div>
                                            <input type="number" min="1" v-model="form.credit_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col gap-4 bg-slate-50 dark:bg-slate-800/50 p-5 rounded-xl border border-slate-100 dark:border-slate-700">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Parcelamento: Boleto</label>
                                            <select v-model="form.boleto_installments_type" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                                <option value="dynamic">Dinâmico (Acompanha Qtd. do Cliente)</option>
                                                <option value="fixed">Fixo (Definir quantidade manual)</option>
                                            </select>
                                        </div>
                                        <div v-if="form.boleto_installments_type === 'fixed'">
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Qtd. Parcelas Fixo</label>
                                            <input type="number" min="1" v-model="form.boleto_fixed_installments" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 shadow-sm focus:border-brand-green focus:ring-brand-green sm:text-sm transition-colors" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/80 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-[20px] border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-brand-green text-base font-semibold text-white hover:bg-[#485638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green sm:ml-3 sm:w-auto sm:text-sm transition-colors" :disabled="form.processing">
                                Salvar
                            </button>
                            <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 dark:border-slate-600 shadow-sm px-6 py-2.5 bg-white dark:bg-slate-700 text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
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
