<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    holidays: Array
});

const form = useForm({
    id: null,
    name: '',
    holiday_date: '',
    start_date: '',
    end_date: ''
});

const isEditing = ref(false);
const showModal = ref(false);

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (holiday) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = holiday.id;
    form.name = holiday.name;
    form.holiday_date = holiday.holiday_date ? holiday.holiday_date.split('T')[0] : '';
    form.start_date = holiday.start_date ? holiday.start_date.split('T')[0] : '';
    form.end_date = holiday.end_date ? holiday.end_date.split('T')[0] : '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.holidays.update', form.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.holidays.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteHoliday = (id) => {
    if (confirm('Tem certeza que deseja excluir esta data especial?')) {
        router.delete(route('admin.holidays.destroy', id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'Não definido';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('pt-BR');
};
</script>

<template>
    <Head title="Feriados e Datas Especiais" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Períodos dos Feriados</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Datas Especiais Prolongadas
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button 
                                @click="openCreateModal"
                                class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Adicionar Feriado
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-slate-900 rounded-[20px] shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Feriado / Data Especial</th>
                                    <th class="px-6 py-4 font-bold">Data do Feriado</th>
                                    <th class="px-6 py-4 font-bold">Início</th>
                                    <th class="px-6 py-4 font-bold">Término</th>
                                    <th class="px-6 py-4 font-bold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr 
                                    v-for="holiday in holidays" 
                                    :key="holiday.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors group"
                                >
                                    <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ holiday.name }}</td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-medium">{{ formatDate(holiday.holiday_date) }}</td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-medium">{{ formatDate(holiday.start_date) }}</td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-medium">{{ formatDate(holiday.end_date) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="openEditModal(holiday)" class="p-1.5 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="deleteHoliday(holiday.id)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="holidays.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 font-medium">Nenhum feriado cadastrado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[20px] shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Editar Feriado' : 'Novo Feriado' }}</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Nome do Feriado</label>
                        <input
                            type="text"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="form.name"
                            required
                        />
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Data do Feriado</label>
                        <input
                            type="date"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="form.holiday_date"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Início</label>
                            <input
                                type="date"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                                v-model="form.start_date"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Término</label>
                            <input
                                type="date"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                                v-model="form.end_date"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-brand-green hover:bg-[#485638] text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm hover:shadow-md disabled:opacity-50">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
