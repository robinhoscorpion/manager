<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Boas-vindas - Pós-venda" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 dark:text-white leading-tight uppercase tracking-widest">
                Boas-vindas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-[#0f1219] overflow-hidden shadow-sm rounded-[20px] border border-slate-200 dark:border-slate-800">
                    <div class="p-6 text-slate-900 dark:text-slate-100 flex flex-col items-center justify-center min-h-[400px]">
                        <div class="w-16 h-16 bg-brand-green/10 text-brand-green rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold uppercase tracking-widest mb-2">Módulo de Boas-vindas</h3>
                        <p class="text-sm text-slate-500 text-center max-w-md">
                            Esta página está em construção. Em breve você poderá gerenciar as mensagens e acompanhamentos de boas-vindas dos clientes por aqui.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
