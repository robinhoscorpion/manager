<script setup>
import { ref } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    accounts: Array,
});

const showEditModal = ref(false);
const editingAccount = ref(null);

const form = useForm({
    name: '',
    owner_type: 'COMERCIALIZADORA',
    bank_name: '',
    agency: '',
    account_number: '',
    is_active: true,
    gateway: '',
    api_token: '',
    webhook_secret: '',
});

const openCreateModal = () => {
    editingAccount.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (account) => {
    editingAccount.value = account;
    form.name = account.name;
    form.owner_type = account.owner_type || 'COMERCIALIZADORA';
    form.bank_name = account.bank_name || '';
    form.agency = account.agency || '';
    form.account_number = account.account_number || '';
    form.is_active = !!account.is_active;
    form.gateway = account.gateway || '';
    form.api_token = account.api_token || '';
    form.webhook_secret = account.webhook_secret || '';
    showEditModal.value = true;
};

const submit = () => {
    if (editingAccount.value) {
        form.put(route('admin.bank-accounts.update', editingAccount.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.bank-accounts.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const showDeleteConfirmModal = ref(false);
const accountToDelete = ref(null);

const confirmDelete = (account) => {
    accountToDelete.value = account;
    showDeleteConfirmModal.value = true;
};

const executeDelete = () => {
    if (accountToDelete.value) {
        form.delete(route('admin.bank-accounts.destroy', accountToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmModal.value = false;
                accountToDelete.value = null;
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
    <Head title="Contas Bancárias" />

    <AuthenticatedLayout>
        <div class="w-full h-auto pt-8 sm:px-6 lg:px-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Contas Bancárias</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gerencie as contas para conciliação financeira
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto" v-if="can('configuracoes.formas_pagamento.gerenciar')">
                            <button 
                                @click="openCreateModal"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span class="text-sm font-semibold text-white">Nova Conta</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-slate-500 uppercase tracking-widest bg-slate-50/50 dark:bg-slate-800/20 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold">Nome da Conta</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Titularidade</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Banco</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Agência</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Número</th>
                                    <th scope="col" class="px-6 py-4 font-bold text-center">Status</th>
                                    <th scope="col" class="px-6 py-4 font-bold text-right" v-if="can('configuracoes.formas_pagamento.gerenciar')">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="account in accounts" :key="account.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors group relative hover:z-50 cursor-pointer">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ account.name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider" :class="account.owner_type === 'PROPRIETARIO' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400'">
                                            {{ account.owner_type === 'PROPRIETARIO' ? 'PROPRIETÁRIO DO HOTEL' : account.owner_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                        {{ account.bank_name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                        {{ account.agency || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                        {{ account.account_number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider',
                                            account.is_active 
                                                ? 'bg-emerald-100/50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                        ]">
                                            <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', account.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                            {{ account.is_active ? 'ATIVO' : 'INATIVO' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right" v-if="can('configuracoes.formas_pagamento.gerenciar')">
                                        <div class="flex items-center justify-end gap-2 transition-opacity">
                                            <button @click="openEditModal(account)" class="p-2 text-slate-400 hover:text-brand-green hover:bg-brand-green/10 rounded-xl transition-colors" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button @click="confirmDelete(account)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-colors" title="Excluir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="accounts.length === 0">
                                    <td :colspan="can('configuracoes.formas_pagamento.gerenciar') ? 6 : 5" class="px-6 py-12 text-center text-slate-500">
                                        Nenhuma conta bancária cadastrada.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- Modal de Formulário -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0f1219] rounded-[24px] shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                        {{ editingAccount ? 'Editar Conta' : 'Nova Conta Bancária' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nome da Conta (Apelido)</label>
                            <input v-model="form.name" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" required placeholder="Ex: Bradesco PJ, Conta Comercializadora">
                            <p class="text-red-500 text-xs mt-1" v-if="form.errors.name">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Titularidade</label>
                            <select v-model="form.owner_type" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" required>
                                <option value="COMERCIALIZADORA">COMERCIALIZADORA</option>
                                <option value="PROPRIETARIO">PROPRIETÁRIO DO HOTEL</option>
                            </select>
                            <p class="text-red-500 text-xs mt-1" v-if="form.errors.owner_type">{{ form.errors.owner_type }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Banco</label>
                            <input v-model="form.bank_name" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Ex: Bradesco, Nubank">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Agência</label>
                                <input v-model="form.agency" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Opcional">
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Número da Conta</label>
                                <input v-model="form.account_number" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Opcional">
                            </div>
                        </div>

                        <!-- Seção de Integração API -->
                        <div class="pt-4 mt-2 border-t border-slate-100 dark:border-slate-800">
                            <h4 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Integração / API (Opcional)
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Gateway de Pagamento</label>
                                    <select v-model="form.gateway" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all">
                                        <option value="">Nenhum (Manual)</option>
                                        <option value="asaas">Asaas</option>
                                        <option value="mercadopago">Mercado Pago</option>
                                        <option value="pagarme">Pagar.me</option>
                                    </select>
                                </div>
                                <div v-if="form.gateway">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">API Token (Access Token)</label>
                                    <input v-model="form.api_token" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Cole o token da sua conta aqui">
                                </div>
                                <div v-if="form.gateway">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Webhook Secret (Opcional)</label>
                                    <input v-model="form.webhook_secret" type="text" class="mt-1.5 w-full bg-slate-50 dark:bg-[#1a1f2e] border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Chave para validar os webhooks de retorno">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center pt-2">
                            <button type="button" @click="form.is_active = !form.is_active" class="flex items-center">
                                <div :class="['relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-green focus:ring-offset-2', form.is_active ? 'bg-brand-green' : 'bg-slate-200 dark:bg-slate-700']">
                                    <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.is_active ? 'translate-x-2' : '-translate-x-2']"></span>
                                </div>
                                <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-300">
                                    Conta Ativa
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="closeModal" class="px-5 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-brand-green text-slate-900 text-sm font-black rounded-xl hover:bg-brand-green/90 focus:ring-2 focus:ring-offset-2 focus:ring-brand-green transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Salvando...' : 'Salvar Conta' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal de Confirmação de Exclusão -->
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="sm">
            <div class="bg-white dark:bg-[#0f1219] p-6 rounded-[24px]">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Excluir Conta Bancária?</h3>
                    <p class="text-sm text-slate-500">
                        Tem certeza que deseja excluir a conta <strong>{{ methodToDelete?.name }}</strong>? Esta ação não pode ser desfeita. Contas com parcelas vinculadas não podem ser excluídas.
                    </p>
                    
                    <div class="flex gap-3 w-full mt-8">
                        <button @click="showDeleteConfirmModal = false" class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button @click="executeDelete" :disabled="form.processing" class="flex-1 px-4 py-2.5 text-sm font-black text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors disabled:opacity-50">
                            Sim, Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>