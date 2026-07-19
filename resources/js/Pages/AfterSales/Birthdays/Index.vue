<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    todayBirthdays: Array,
    upcomingBirthdays: Array,
    pastBirthdays: Array,
    currentMonthName: String,
});

const capitalize = (s) => s && s[0].toUpperCase() + s.slice(1);
</script>

<template>
    <Head title="Aniversariantes do Mês" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z" />
                </svg>
                Aniversariantes de {{ capitalize(currentMonthName) }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Hoje -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-brand-green">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center gap-3 mb-6">
                            <svg class="w-8 h-8 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <h3 class="text-xl font-bold">Aniversariantes de Hoje!</h3>
                        </div>

                        <div v-if="todayBirthdays.length === 0" class="text-gray-500 dark:text-gray-400 italic">
                            Nenhum cliente faz aniversário hoje.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="client in todayBirthdays" :key="client.id" 
                                 class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-brand-green/30 flex flex-col justify-between hover:border-brand-green transition-colors">
                                <div>
                                    <h4 class="font-bold text-lg mb-1 truncate" :title="client.nome">{{ client.nome }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ client.idade }} anos
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                        <span class="font-semibold">Telefone:</span> {{ client.celular1 || 'Não informado' }}
                                    </p>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <a :href="'https://wa.me/55' + (client.celular1 ? client.celular1.replace(/\D/g, '') : '')" 
                                       target="_blank"
                                       class="text-xs bg-brand-green text-white px-3 py-1.5 rounded font-medium hover:bg-green-600 w-full text-center"
                                       v-if="client.celular1">
                                        Parabenizar no WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Próximos do Mês -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-lg font-bold">Próximos deste Mês</h3>
                            </div>

                            <div v-if="upcomingBirthdays.length === 0" class="text-gray-500 dark:text-gray-400 italic text-sm">
                                Não há mais aniversariantes neste mês.
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="client in upcomingBirthdays" :key="client.id" 
                                     class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                    <div class="overflow-hidden">
                                        <p class="font-semibold truncate" :title="client.nome">{{ client.nome }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Faz {{ client.idade }} anos em {{ client.data_nascimento }}</p>
                                    </div>
                                    <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-3 py-1 rounded-full text-sm font-bold ml-4">
                                        Dia {{ client.dia }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Já fizeram aniversário -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg opacity-80">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-lg font-bold">Aniversários Passados</h3>
                            </div>

                            <div v-if="pastBirthdays.length === 0" class="text-gray-500 dark:text-gray-400 italic text-sm">
                                Nenhum cliente fez aniversário neste mês ainda.
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="client in pastBirthdays" :key="client.id" 
                                     class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center opacity-75 grayscale-[50%]">
                                    <div class="overflow-hidden">
                                        <p class="font-semibold truncate" :title="client.nome">{{ client.nome }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Fez {{ client.idade }} anos em {{ client.data_nascimento }}</p>
                                    </div>
                                    <div class="flex-shrink-0 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full text-sm font-bold ml-4">
                                        Dia {{ client.dia }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
