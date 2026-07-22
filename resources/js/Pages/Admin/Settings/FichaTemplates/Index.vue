<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { defineProps } from 'vue';

const props = defineProps({
    templates: Array
});

const destroy = (id) => {
    if (confirm('Tem certeza que deseja excluir este modelo?')) {
        router.delete(route('admin.settings.ficha_templates.destroy', id));
    }
};
</script>

<template>
    <Head title="Modelos de Ficha" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Modelos de Ficha de Atendimento</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Gerencie os modelos HTML para impressão de fichas.</p>
                </div>
                <div>
                    <Link :href="route('admin.settings.ficha_templates.create')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-brand-green hover:bg-brand-green/90 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-brand-green/40 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Modelo
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome do Modelo</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Padrão</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr v-for="template in templates" :key="template.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ template.name }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span v-if="template.is_active" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Ativo</span>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">Inativo</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div v-if="template.is_default" class="flex items-center gap-1 text-brand-green">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span class="text-xs font-bold uppercase">Padrão</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('admin.settings.ficha_templates.edit', template.id)" class="p-1.5 text-slate-400 hover:text-brand-green rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </Link>
                                            <button @click="destroy(template.id)" class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Excluir">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!templates.length">
                                    <td colspan="4" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        Nenhum modelo cadastrado.
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
