<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const authUser = computed(() => usePage().props.auth.user);

const props = defineProps({
    goals: Array
});

const isModalOpen = ref(false);
const editingGoal = ref(null);
const isDeleteModalOpen = ref(false);
const goalToDelete = ref(null);

const form = useForm({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    revenue_target: '',
    contracts_target: '',
});

const openModal = (goal = null) => {
    editingGoal.value = goal;
    if (goal) {
        form.month = goal.month;
        form.year = goal.year;
        form.revenue_target = goal.revenue_target;
        form.contracts_target = goal.contracts_target;
    } else {
        form.reset();
        form.month = new Date().getMonth() + 1;
        form.year = new Date().getFullYear();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    if (editingGoal.value) {
        form.put(route('admin.platform_goals.update', editingGoal.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.platform_goals.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDeleteGoal = (goal) => {
    goalToDelete.value = goal;
    isDeleteModalOpen.value = true;
};

const executeDeleteGoal = () => {
    if (!goalToDelete.value) return;
    router.delete(route('admin.platform_goals.destroy', goalToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            goalToDelete.value = null;
        }
    });
};

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

const formatCurrency = (value) => {
    if (!value) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const getMonthName = (monthNumber) => {
    const date = new Date();
    date.setMonth(monthNumber - 1);
    return date.toLocaleString('pt-BR', { month: 'long' }).replace(/^\w/, c => c.toUpperCase());
};

const maskCurrency = (value) => {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
};

const onRevenueInput = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    form.revenue_target = val ? parseFloat(val) / 100 : 0;
};
</script>

<template>
    <Head title="Metas da Plataforma" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Metas Mensais</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Metas da Plataforma
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto" v-if="can('configuracoes.metas.gerenciar')">
                            <button 
                                @click="openModal()"
                                class="w-full sm:w-auto flex items-center justify-center gap-1.5 bg-brand-green hover:bg-[#485638] text-white px-3 py-2 rounded-[10px] transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            >
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-xs font-bold text-white tracking-wide">Nova Meta</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    <div class="w-full overflow-x-auto">
                        <div class="min-w-[700px]">
                            <!-- Table Header -->
                            <div class="ledger-header-row flex items-center px-6 py-3 border-b border-slate-100 dark:border-slate-800/50">
                                <div class="w-[35%] text-left text-[10px] font-black uppercase tracking-widest text-slate-500">Mês/Ano</div>
                                <div class="w-[25%] text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Faturamento Alvo</div>
                                <div class="w-[25%] text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Contratos Alvo</div>
                                <div class="w-[15%] text-right text-[10px] font-black uppercase tracking-widest text-slate-500 pr-2">Ações</div>
                            </div>

                            <!-- Table Body -->
                            <div class="w-full">
                                <template v-if="goals && goals.length > 0">
                                    <div 
                                        v-for="goal in goals" 
                                        :key="goal.id"
                                        class="ledger-row flex items-center px-6 py-4 cursor-default group relative hover:z-50 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors border-b border-slate-50 dark:border-slate-800/30 last:border-0"
                                    >
                                        <!-- Accent bar on hover -->
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-brand-green opacity-0 group-hover:opacity-100 transition-opacity rounded-r-sm"></div>

                                        <!-- Mês/Ano -->
                                        <div class="w-[35%] text-left flex items-center gap-3 pl-2">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100/80 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700 flex items-center justify-center font-black text-slate-700 dark:text-slate-300 text-[11px] shadow-sm">
                                                {{ goal.month.toString().padStart(2, '0') }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider group-hover:text-brand-green transition-colors">{{ getMonthName(goal.month) }}</span>
                                                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ goal.year }}</span>
                                            </div>
                                        </div>

                                        <!-- Faturamento Alvo -->
                                        <div class="w-[25%] text-center flex flex-col items-center">
                                            <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ formatCurrency(goal.revenue_target) }}</span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Meta BRL</span>
                                        </div>

                                        <!-- Contratos Alvo -->
                                        <div class="w-[25%] text-center flex flex-col items-center">
                                            <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ goal.contracts_target }}</span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Contratos</span>
                                        </div>
                                        
                                        <!-- Ações -->
                                        <div class="w-[15%] flex items-center justify-end gap-1.5 pr-1">
                                            <button 
                                                v-if="can('configuracoes.metas.gerenciar')"
                                                @click="openModal(goal)"
                                                class="w-7 h-7 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400 flex items-center justify-center hover:bg-slate-50 hover:text-brand-green hover:border-brand-green/30 transition-all shadow-sm"
                                                title="Editar Meta"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button 
                                                v-if="can('configuracoes.metas.gerenciar')"
                                                @click="confirmDeleteGoal(goal)"
                                                class="w-7 h-7 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400 flex items-center justify-center hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition-all shadow-sm"
                                                title="Remover Meta"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <div v-else class="w-full py-16 flex flex-col items-center justify-center text-center">
                                    <div class="w-16 h-16 rounded-full bg-brand-green/10 border border-brand-green/20 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nenhuma meta</h3>
                                    <p class="text-sm text-slate-500 mt-1">Não há metas cadastradas no momento.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="relative bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-2xl sm:rounded-[24px] shadow-2xl overflow-hidden animate-slide-up max-h-[90vh] flex flex-col">
                <div class="shrink-0 p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                {{ editingGoal ? 'Editar Meta' : 'Nova Meta' }}
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
                                Configure os objetivos do mês.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Mês</label>
                                <select v-model="form.month" class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-[11px] font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green" :disabled="editingGoal !== null">
                                    <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
                                </select>
                                <InputError :message="form.errors.month" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Ano</label>
                                <input v-model="form.year" type="number" class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-[11px] font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green" :disabled="editingGoal !== null">
                                <InputError :message="form.errors.year" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Faturamento Alvo (R$)</label>
                            <input :value="maskCurrency(form.revenue_target)" @input="onRevenueInput" type="text" class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-[11px] font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green transition-all placeholder-slate-400">
                            <InputError :message="form.errors.revenue_target" class="mt-1" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Contratos Alvo (Qtd)</label>
                            <input v-model="form.contracts_target" type="number" min="0" class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-[11px] font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green transition-all placeholder-slate-400">
                            <InputError :message="form.errors.contracts_target" class="mt-1" />
                        </div>

                    </form>
                </div>

                <div class="shrink-0 p-4 sm:p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-2 rounded-b-2xl sm:rounded-b-[24px]">
                    <button 
                        type="button" 
                        @click="closeModal" 
                        class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-bold uppercase text-[9px] tracking-widest hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="submit" 
                        :disabled="form.processing"
                        class="px-6 py-2 bg-brand-green hover:bg-[#485638] text-white rounded-lg font-black uppercase text-[9px] tracking-widest transition-all shadow-md hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Salvando...' : 'Salvar Meta' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" max-width="md">
            <div class="relative bg-white dark:bg-slate-900 border border-red-500/20 dark:border-red-500/30 rounded-[24px] shadow-2xl overflow-hidden animate-slide-up p-6 md:p-8">
                
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">Excluir Meta?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-xs mx-auto">
                        Você está prestes a remover a meta de <strong class="text-slate-900 dark:text-white">"{{ goalToDelete ? getMonthName(goalToDelete.month) + '/' + goalToDelete.year : '' }}"</strong>. Esta ação não pode ser desfeita.
                    </p>

                    <div class="flex w-full gap-2">
                        <button 
                            @click="isDeleteModalOpen = false"
                            class="flex-1 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-bold uppercase text-[9px] tracking-widest transition-colors shadow-sm"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="executeDeleteGoal"
                            class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-lg font-black uppercase text-[9px] tracking-widest transition-all shadow-md active:scale-95 flex items-center justify-center gap-2"
                        >
                            Sim, Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
