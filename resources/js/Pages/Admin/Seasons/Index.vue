<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    seasons: Array
});

const deleteSeason = (id) => {
    if (confirm('Tem certeza que deseja excluir esta temporada?')) {
        router.delete(route('admin.seasons.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Gerenciar Temporadas" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
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
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Gerenciar Temporadas</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Configuração de Períodos
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link :href="route('admin.seasons.mapping')" class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                                </svg>
                                Mapeamento D&D
                            </Link>
                            <Link :href="route('admin.seasons.create')" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Nova Temporada
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-sm overflow-hidden pb-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-widest bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Nome</th>
                                    <th scope="col" class="px-6 py-4">Antecedência (Dias)</th>
                                    <th scope="col" class="px-6 py-4">Período Descrição</th>
                                    <th scope="col" class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr v-for="season in seasons" :key="season.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ season.name }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                        {{ season.advance_days }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-slate-500 whitespace-pre-line uppercase tracking-wide">
                                        {{ season.period_description }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('admin.seasons.edit', season.id)" class="text-slate-400 hover:text-brand-green p-2 transition-colors bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-lg" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </Link>
                                            <button @click="deleteSeason(season.id)" class="text-slate-400 hover:text-red-500 p-2 transition-colors bg-slate-50 dark:bg-slate-800/50 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg" title="Excluir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!seasons.length">
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-500 font-medium">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            Nenhuma temporada cadastrada.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
