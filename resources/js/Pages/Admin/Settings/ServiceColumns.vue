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
        <template #header>
            <h2 class="text-xl font-black text-white uppercase tracking-tighter text-left">Configurações de Colunas</h2>
            <p class="text-[10px] text-cyan-400 font-bold uppercase tracking-[0.2em] mt-1 text-left">Defina quais dados serão visíveis na listagem de atendimentos</p>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-4xl mx-auto text-left">
                <div class="bg-[#0d1117] border border-white/5 rounded-3xl overflow-hidden relative shadow-2xl">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none"></div>
                    
                    <div class="p-8 sm:p-12 relative">
                        <div class="flex items-center gap-4 mb-10">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h2a2 2 0 00-2 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tighter">Visibilidade das Colunas</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Marque os campos que deseja exibir no dashboard</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <template v-for="(label, key) in columnLabels" :key="key">
                                    <label class="group relative flex items-center p-4 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-all cursor-pointer overflow-hidden">
                                        <div class="flex items-center gap-4 relative z-10 w-full">
                                            <div class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="form.columns[key]" class="sr-only peer">
                                                <div class="w-9 h-5 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-gray-400 after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-cyan-600"></div>
                                            </div>
                                            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" :class="form.columns[key] ? 'text-white' : 'text-gray-600'">
                                                {{ label }}
                                            </span>
                                        </div>
                                        <div v-if="form.columns[key]" class="absolute inset-0 bg-cyan-500/[0.02] pointer-events-none"></div>
                                    </label>
                                </template>
                            </div>

                            <div class="pt-6 border-t border-white/5 flex items-center justify-end">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="px-10 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.2em] shadow-xl shadow-cyan-500/20 active:scale-95 transition-all disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Salvando...' : 'Salvar Preferências' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
