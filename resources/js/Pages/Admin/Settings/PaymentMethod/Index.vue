<script setup>
import { ref } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    paymentMethods: Array,
});

const showEditModal = ref(false);
const editingMethod = ref(null);

const form = useForm({
    name: '',
    type: 'boleto',
    description: '',
    is_active: true,
    auto_baixa: false,
});

const types = [
    { label: 'Boleto', value: 'boleto' },
    { label: 'Cartão de Crédito', value: 'credit_card' },
    { label: 'Cartão de Débito', value: 'debit_card' },
    { label: 'PIX', value: 'pix' },
    { label: 'Dinheiro (Espécie)', value: 'cash' },
    { label: 'Cheque', value: 'check' },
    { label: 'Transferência Bancária', value: 'transfer' },
    { label: 'Outros', value: 'other' },
];

const openCreateModal = () => {
    editingMethod.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (method) => {
    editingMethod.value = method;
    form.name = method.name;
    form.type = method.type;
    form.description = method.description;
    form.is_active = !!method.is_active;
    form.auto_baixa = !!method.auto_baixa;
    showEditModal.value = true;
};

const submit = () => {
    if (editingMethod.value) {
        form.put(route('admin.payment_methods.update', editingMethod.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.payment_methods.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const showDeleteConfirmModal = ref(false);
const methodToDelete = ref(null);

const confirmDelete = (method) => {
    methodToDelete.value = method;
    showDeleteConfirmModal.value = true;
};

const executeDelete = () => {
    if (methodToDelete.value) {
        form.delete(route('admin.payment_methods.destroy', methodToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmModal.value = false;
                methodToDelete.value = null;
            }
        });
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Formas de Pagamento" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Formas de Pagamento</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gestão de Nomenclaturas e Tipos
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button v-if="can('configuracoes.formas_pagamento.gerenciar')" 
                                @click="openCreateModal"
                                class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Cadastrar Nova
                            </button>
                        </div>
                    </div>
                </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-12">
                <div 
                    v-for="method in paymentMethods" 
                    :key="method.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] p-6 shadow-sm hover:border-brand-green/30 dark:hover:border-brand-green/40 transition-all group"
                >
                    <div class="flex items-start justify-between mb-5">
                        <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center text-brand-green">
                            <svg v-if="method.type === 'credit_card' || method.type === 'debit_card'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-width="2"/></svg>
                            <svg v-else-if="method.type === 'boleto'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 7h.01M7 12h.01M7 17h.01M10 7h10M10 12h10M10 17h10" stroke-width="3" stroke-linecap="round"/></svg>
                            <svg v-else-if="method.type === 'pix'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2"/></svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg>
                        </div>
                        <div class="flex gap-2 transition-opacity">
                            <button @click="openEditModal(method)" class="p-2 bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 dark:hover:bg-brand-green/20 rounded-lg text-slate-500 hover:text-brand-green dark:text-slate-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2"/></svg>
                            </button>
                            
                            <!-- Delete Button with Custom Tooltip -->
                            <div class="relative group/tooltip flex items-center justify-center">
                                <button 
                                    @click="method.proposals_count > 0 ? null : confirmDelete(method)"
                                    :disabled="method.proposals_count > 0"
                                    :class="[
                                        'p-2 rounded-lg transition-colors',
                                        method.proposals_count > 0 
                                            ? 'bg-slate-50 dark:bg-slate-800/20 text-slate-300 dark:text-slate-600 cursor-not-allowed' 
                                            : 'bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 text-red-500'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                                </button>
                                
                                <!-- Custom Tooltip -->
                                <div v-if="method.proposals_count > 0" class="absolute right-full top-1/2 -translate-y-1/2 mr-3 w-56 bg-slate-900 dark:bg-black text-white text-xs font-medium leading-relaxed px-3 py-2 rounded-xl opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-300 shadow-xl border border-slate-700/50 z-[100] pointer-events-none transform -translate-x-2 group-hover/tooltip:translate-x-0 text-center">
                                    Não é possível remover: Existem <strong class="text-brand-green">{{ method.proposals_count }} venda(s)</strong> usando esta forma de pagamento. Apenas inative-a.
                                    <div class="absolute top-1/2 -right-1 -translate-y-1/2 w-2 h-2 bg-slate-900 dark:bg-black border-t border-r border-slate-700/50 transform rotate-45"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">{{ method.name }}</h3>
                            <span v-if="!method.is_active" class="px-2 py-0.5 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-500 text-[9px] font-bold uppercase tracking-widest rounded-md">Inativo</span>
                            <span v-if="method.auto_baixa" class="px-2 py-0.5 bg-brand-green/10 border border-brand-green/20 text-brand-green text-[9px] font-bold uppercase tracking-widest rounded-md">Auto Baixa</span>
                        </div>
                        <p class="text-[10px] font-bold text-brand-green/80 uppercase tracking-widest mb-3">
                            Tipo: {{ types.find(t => t.value === method.type)?.label || method.type }}
                        </p>
                        <p v-if="method.description" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ method.description }}</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="paymentMethods.length === 0" class="col-span-full py-20 flex flex-col items-center justify-center text-center bg-white dark:bg-slate-900/40 rounded-[20px] border border-dashed border-slate-200 dark:border-slate-800">
                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" stroke-width="2"/></svg>
                    </div>
                    <h3 class="text-slate-900 dark:text-white font-bold uppercase tracking-wider text-sm">Nenhuma forma cadastrada</h3>
                    <p class="text-slate-500 text-xs mt-2">Clique em "Cadastrar Nova" para começar.</p>
                </div>
            </div>
        </div>
        </div>

        <!-- Modal de Cadastro/Edição -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                                {{ editingMethod ? 'Editar Forma de Pagamento' : 'Nova Forma de Pagamento' }}
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Gestão de Meios de Pagamento</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5 bg-slate-50 dark:bg-transparent">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Nomenclatura (Ex: Boleto Bancário R2)</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all"
                            placeholder="DIGITE O NOME"
                            required
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Tipo Técnico</label>
                            <select 
                                v-model="form.type" 
                                class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all"
                            >
                                <option v-for="t in types" :key="t.value" :value="t.value" class="bg-white dark:bg-slate-800">{{ t.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Status</label>
                            <div class="flex items-center h-[46px] px-2 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                                    <span class="ml-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ form.is_active ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Auto Baixar Parcelas?</label>
                        <div class="flex items-center h-[46px] px-4 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
                            <label class="relative inline-flex items-center cursor-pointer w-full">
                                <input type="checkbox" v-model="form.auto_baixa" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                                <span class="ml-3 text-xs font-medium text-slate-600 dark:text-slate-300">Sim, criar parcelas já como baixadas</span>
                            </label>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1 px-1">Se marcado, o financeiro será gerado com o status "Pago" usando o valor e data de vencimento.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Descrição (Opcional)</label>
                        <textarea 
                            v-model="form.description" 
                            rows="3" 
                            class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all resize-none"
                            placeholder="OUTRAS INFORMAÇÕES..."
                        ></textarea>
                    </div>

                    <div class="pt-6 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold uppercase text-xs tracking-wider rounded-[12px] transition-all shadow-sm"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-[2] py-3.5 bg-brand-green hover:bg-[#485638] text-white font-bold uppercase text-xs tracking-wider rounded-[12px] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ editingMethod ? 'Salvar Alterações' : 'Cadastrar Forma' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Excluir Forma de Pagamento?</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        Tem certeza que deseja remover <strong>{{ methodToDelete?.name }}</strong>? Esta ação não poderá ser desfeita.
                    </p>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex gap-3">
                    <button @click="showDeleteConfirmModal = false" class="flex-1 px-4 py-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all shadow-sm">
                        Cancelar
                    </button>
                    <button @click="executeDelete" class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 shadow-red-500/20 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                        Sim, Excluir
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
