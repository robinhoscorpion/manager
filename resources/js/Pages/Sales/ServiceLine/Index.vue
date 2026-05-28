<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Linha de atendimento" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">Linha de atendimento</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/5 border border-white/5 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white text-gray-400">Linha de atendimento em desenvolvimento...</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
