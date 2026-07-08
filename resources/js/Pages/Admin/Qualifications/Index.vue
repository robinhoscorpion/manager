<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    qualifications: Array,
});

const showEditModal = ref(false);
const editingQualification = ref(null);

const form = useForm({
    name: '',
    code: '',
    color: 'bg-blue-500',
    description: '',
    is_active: true,
});

const colors = [
    { name: 'Azul', class: 'bg-blue-500' },
    { name: 'Esmeralda', class: 'bg-emerald-500' },
    { name: 'Ciano', class: 'bg-cyan-500' },
    { name: 'Roxo', class: 'bg-purple-500' },
    { name: 'Rosa', class: 'bg-pink-500' },
    { name: 'Laranja', class: 'bg-orange-500' },
    { name: 'Âmbar', class: 'bg-amber-500' },
    { name: 'Rosa Choque', class: 'bg-rose-500' },
    { name: 'Slate', class: 'bg-slate-500' },
];

const openCreateModal = () => {
    editingQualification.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (qual) => {
    editingQualification.value = qual;
    form.name = qual.name;
    form.code = qual.code;
    form.color = qual.color;
    form.description = qual.description || '';
    form.is_active = !!qual.is_active;
    showEditModal.value = true;
};

const submit = () => {
    if (editingQualification.value) {
        form.put(route('admin.qualifications.update', editingQualification.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.qualifications.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteQualification = (id) => {
    if (confirm('Tem certeza que deseja remover este tipo de qualificação?')) {
        form.delete(route('admin.qualifications.destroy', id));
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Tipos de Qualificação" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Tipos de Qualificação</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Personalização do Fluxo de Atendimento
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button 
                                @click="openCreateModal"
                                class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Nova Qualificação
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="qual in qualifications" 
                        :key="qual.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] group hover:border-brand-green/30 dark:hover:border-brand-green/40 transition-all overflow-hidden flex flex-col shadow-sm hover:shadow-md relative"
                    >
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-start">
                                <div 
                                    class="w-12 h-12 rounded-[14px] flex items-center justify-center text-xl font-bold text-white shadow-sm border border-white/10"
                                    :class="qual.color"
                                >
                                    {{ qual.code }}
                                </div>
                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditModal(qual)" class="text-slate-500 hover:text-brand-green p-1 transition-colors bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteQualification(qual.id)" class="text-slate-500 hover:text-red-500 p-1 transition-colors bg-slate-50 dark:bg-slate-800/50 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-slate-900 dark:text-white font-bold text-lg uppercase tracking-tight group-hover:text-brand-green transition-colors">{{ qual.name }}</h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1 truncate">{{ qual.description || 'Sem descrição' }}</p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Status</span>
                                <div 
                                    class="px-2.5 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border"
                                    :class="qual.is_active ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400'"
                                >
                                    {{ qual.is_active ? 'Ativo' : 'Inativo' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[20px] h-full min-h-[180px] flex flex-col items-center justify-center p-8 group hover:border-brand-green/30 transition-all bg-slate-50/50 dark:bg-slate-900/40 hover:bg-brand-green/5 dark:hover:bg-brand-green/5"
                    >
                        <div class="w-12 h-12 rounded-[14px] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center group-hover:bg-brand-green/10 group-hover:border-brand-green/20 transition-colors mb-3">
                            <svg class="w-6 h-6 text-slate-400 group-hover:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest group-hover:text-brand-green transition-colors">Novo Tipo</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="md">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                                {{ editingQualification ? 'Editar Qualificação' : 'Nova Qualificação' }}
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Gestão de Tipos</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5 bg-slate-50 dark:bg-transparent">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-3 space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Nome</label>
                            <input v-model="form.name" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all" placeholder="Ex: Qualificado">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1 text-center block">Código</label>
                            <input v-model="form.code" type="text" maxlength="5" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-3 text-slate-900 dark:text-white text-sm text-center font-bold outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all uppercase" placeholder="Q">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Cor Associada</label>
                        <div class="grid grid-cols-9 gap-2">
                            <button 
                                v-for="color in colors" 
                                :key="color.class"
                                type="button"
                                @click="form.color = color.class"
                                class="w-full aspect-square rounded-[10px] border-2 transition-all p-0.5"
                                :class="[color.class, form.color === color.class ? 'border-brand-green scale-110 shadow-md ring-2 ring-brand-green/20' : 'border-transparent opacity-60 hover:opacity-100 hover:scale-105']"
                                :title="color.name"
                            ></button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Descrição</label>
                        <textarea v-model="form.description" rows="2" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all resize-none" placeholder="Ex: Cliente atende aos requisitos básicos..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 bg-white dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                            <span class="ml-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qualificação Ativa</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeModal" class="flex-1 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold uppercase text-xs tracking-wider rounded-[12px] transition-all shadow-sm">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-brand-green hover:bg-[#485638] text-white font-bold uppercase text-xs tracking-wider rounded-[12px] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
