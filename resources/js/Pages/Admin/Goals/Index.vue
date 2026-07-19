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
</script>

<template>
    <Head title="Metas da Plataforma" />

    <AuthenticatedLayout>
        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Metas Mensais</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Configure os objetivos financeiros e de contratos da plataforma.</p>
            </div>

            <div class="flex items-center gap-3">
                <button 
                    v-if="can('configuracoes.metas.gerenciar')"
                    @click="openModal()"
                    class="group relative h-11 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold tracking-wide transition-all shadow-lg hover:shadow-indigo-500/25 flex items-center gap-2 overflow-hidden"
                >
                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform"></div>
                    <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="relative z-10">Nova Meta</span>
                </button>
            </div>
        </div>

        <!-- Goals List -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Mês/Ano</th>
                            <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Faturamento Alvo</th>
                            <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 text-center">Contratos Alvo</th>
                            <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="goal in goals" 
                            :key="goal.id"
                            class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                        >
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-700 dark:text-slate-300">
                                        {{ goal.month.toString().padStart(2, '0') }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ getMonthName(goal.month) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ goal.year }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(goal.revenue_target) }}</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center justify-center min-w-[3rem] px-2 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm font-bold border border-emerald-200 dark:border-emerald-500/20">
                                    {{ goal.contracts_target }}
                                </span>
                            </td>
                            
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button 
                                        v-if="can('configuracoes.metas.gerenciar')"
                                        @click="openModal(goal)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors"
                                        title="Editar Meta"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="can('configuracoes.metas.gerenciar')"
                                        @click="confirmDeleteGoal(goal)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                                        title="Remover Meta"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="goals.length === 0">
                            <td colspan="4" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                    <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <p class="text-sm font-medium">Nenhuma meta cadastrada.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-[32px] shadow-2xl overflow-hidden animate-slide-up max-h-[90vh] flex flex-col">
                <div class="shrink-0 p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Mês</label>
                                <select v-model="form.month" class="w-full h-12 px-4 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" :disabled="editingGoal !== null">
                                    <option v-for="m in 12" :key="m" :value="m">{{ getMonthName(m) }}</option>
                                </select>
                                <InputError :message="form.errors.month" class="mt-2" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Ano</label>
                                <input v-model="form.year" type="number" class="w-full h-12 px-4 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" :disabled="editingGoal !== null">
                                <InputError :message="form.errors.year" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Faturamento Alvo (R$)</label>
                            <input v-model="form.revenue_target" type="number" step="0.01" min="0" class="w-full h-12 px-4 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all placeholder-slate-400">
                            <InputError :message="form.errors.revenue_target" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Contratos Alvo (Qtd)</label>
                            <input v-model="form.contracts_target" type="number" min="0" class="w-full h-12 px-4 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all placeholder-slate-400">
                            <InputError :message="form.errors.contracts_target" class="mt-2" />
                        </div>

                    </form>
                </div>

                <div class="shrink-0 p-6 sm:p-8 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 rounded-b-2xl sm:rounded-b-[32px]">
                    <button 
                        type="button" 
                        @click="closeModal" 
                        class="px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold uppercase text-[10px] tracking-widest hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="submit" 
                        :disabled="form.processing"
                        class="px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-md hover:shadow-indigo-500/25 active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Salvando...' : 'Salvar Meta' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" max-width="md">
            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-2xl overflow-hidden animate-slide-up p-6">
                
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-4 shadow-sm">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 tracking-tight">Excluir Meta?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Você está prestes a remover a meta de <strong class="text-slate-900 dark:text-white">"{{ goalToDelete ? getMonthName(goalToDelete.month) + '/' + goalToDelete.year : '' }}"</strong>. Esta ação não pode ser desfeita.
                    </p>

                    <div class="flex w-full gap-3">
                        <button 
                            @click="isDeleteModalOpen = false"
                            class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold uppercase text-[10px] tracking-widest transition-colors shadow-sm"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="executeDeleteGoal"
                            class="flex-1 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-md active:scale-95 flex items-center justify-center gap-2"
                        >
                            Sim, Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
