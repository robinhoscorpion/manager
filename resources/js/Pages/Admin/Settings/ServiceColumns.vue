<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    columns: Object
});

const form = useForm({
    columns: { ...props.columns }
});

const submit = () => {
    form.put(route('admin.settings.columns.update'), {
        preserveScroll: true,
    });
};

const columnLabels = {
    id: '# ID',
    date: 'Data',
    time: 'Horários',
    clients: 'Clientes',
    mkt: 'Mkt',
    opc: 'OPC',
    liner: 'Liner',
    closer: 'Closer',
    qualification: 'Qualif.',
    status: 'Status',
    actions: 'AÇÕES'
};
</script>

<template>
    <Head title="Configurações de Colunas" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full max-w-5xl mx-auto sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h2a2 2 0 00-2 2" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Configurações de Colunas</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Defina quais dados serão visíveis
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    
                    <div class="p-6 sm:p-10 relative">
                        <form @submit.prevent="submit" class="space-y-8">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <template v-for="(label, key) in columnLabels" :key="key">
                                    <label class="group relative flex items-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 hover:border-brand-green/30 dark:hover:border-brand-green/30 transition-all cursor-pointer overflow-hidden">
                                        <div class="flex items-center gap-4 relative z-10 w-full">
                                            <div class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="form.columns[key]" class="sr-only peer">
                                                <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-green"></div>
                                            </div>
                                            <span class="text-xs font-bold uppercase tracking-widest transition-colors" :class="form.columns[key] ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">
                                                {{ label }}
                                            </span>
                                        </div>
                                        <div v-if="form.columns[key]" class="absolute inset-0 bg-brand-green/[0.02] dark:bg-brand-green/5 pointer-events-none"></div>
                                    </label>
                                </template>
                            </div>

                            <div class="pt-8 mt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full sm:w-auto px-8 py-3 bg-brand-green hover:bg-[#485638] text-white rounded-[12px] font-semibold text-sm transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="!form.processing" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ form.processing ? 'Salvando...' : 'Salvar Preferências' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
