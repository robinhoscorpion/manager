<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    templates: Array
});

const form = useForm({});
const showDeleteConfirmModal = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirmModal.value = true;
};

const executeDelete = () => {
    form.delete(route('admin.settings.ficha_templates.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            itemToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Modelos de Ficha" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Modelos de Ficha de Atendimento</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gestão de Modelos de Impressão
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <Link 
                                :href="route('admin.settings.ficha_templates.create')"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Modelo</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="item in templates" 
                        :key="item.id"
                        class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/60 dark:border-slate-800/60 rounded-2xl group hover:border-brand-green/50 dark:hover:border-brand-green/50 transition-all duration-500 overflow-hidden flex flex-col shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] dark:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.2)] hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.15)] dark:hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.25)] relative hover:-translate-y-1"
                    >
                        <!-- Glow Effect on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-brand-green/0 to-brand-green/5 dark:from-brand-green/0 dark:to-brand-green/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                        <!-- Ribbon for Default -->
                        <div v-if="item.is_default" class="absolute top-0 right-0 z-10 overflow-hidden w-24 h-24 pointer-events-none">
                            <div class="bg-gradient-to-r from-brand-green to-[#5c6e46] text-[9px] font-black uppercase tracking-[0.2em] text-white px-8 py-1.5 rotate-45 translate-x-[22px] translate-y-[16px] shadow-lg text-center">
                                Global
                            </div>
                        </div>

                        <div class="p-6 flex flex-col flex-1 relative z-20">
                            <!-- Header (Icon + Title) -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="relative w-12 h-12 flex-shrink-0 rounded-[14px] flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                                    <svg class="w-6 h-6 text-brand-green drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                </div>
                                <div class="flex flex-col pt-1">
                                    <span 
                                        class="text-[9px] font-black uppercase tracking-widest mb-1"
                                        :class="item.is_default ? 'text-brand-green' : 'text-slate-400'"
                                    >
                                        {{ item.is_default ? 'Padrão Global' : 'Específico' }}
                                    </span>
                                    <h3 class="text-slate-900 dark:text-white font-bold text-lg tracking-tight line-clamp-2 leading-tight">{{ item.name }}</h3>
                                </div>
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <!-- Source Indicator -->
                                <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                                    <div class="w-6 h-6 rounded flex items-center justify-center shrink-0 bg-brand-green/10 text-brand-green">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Formato</span>
                                        <span class="text-[11px] font-semibold truncate text-slate-700 dark:text-slate-300">Editor Interno HTML</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-auto border-t border-slate-100 dark:border-slate-800/60 p-3 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-1 relative z-20">
                            <a :href="route('admin.settings.ficha_templates.preview_pdf', item.id)" target="_blank" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-purple-500 hover:bg-purple-50 dark:hover:bg-purple-500/10 transition-colors" title="Visualizar / Imprimir PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                            <Link :href="route('admin.settings.ficha_templates.edit', item.id)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </Link>
                            <button @click="confirmDelete(item)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Remover">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add New Placeholder -->
                    <Link 
                        :href="route('admin.settings.ficha_templates.create')"
                        class="bg-transparent border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl h-full min-h-[280px] flex flex-col items-center justify-center p-8 group hover:border-brand-green hover:bg-brand-green/5 dark:hover:bg-brand-green/10 transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-center group-hover:bg-brand-green group-hover:border-brand-green transition-colors duration-300 mb-4 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-brand-green transition-colors">Cadastrar Novo Modelo</p>
                        <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase tracking-widest text-center">Crie uma nova Ficha de Atendimento</p>
                    </Link>
                </div>

            </div>
        </div>

        <!-- Delete Confirm Modal -->
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6 bg-white dark:bg-[#0f1219]">
                <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mx-auto mb-5 border-4 border-red-100 dark:border-red-500/20">
                    <svg class="w-8 h-8 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                
                <h3 class="text-xl font-black text-slate-900 dark:text-white text-center mb-2 tracking-tight">
                    Excluir Modelo?
                </h3>
                
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-8 px-4 leading-relaxed">
                    Você está prestes a excluir o modelo <br>
                    <strong class="text-slate-700 dark:text-slate-300">"{{ itemToDelete?.name }}"</strong>. <br>
                    <span class="text-red-500 font-bold block mt-3">Esta ação não pode ser desfeita.</span>
                </p>

                <div class="flex gap-3 w-full">
                    <button @click="showDeleteConfirmModal = false" class="flex-1 px-4 py-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all shadow-sm">
                        Cancelar
                    </button>
                    <button @click="executeDelete" :disabled="form.processing" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0">
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ form.processing ? 'Excluindo...' : 'Sim, excluir' }}
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
