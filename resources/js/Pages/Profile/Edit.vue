<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Meu Perfil" />
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-Full mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Elite Header -->
                <div class="relative bg-white/[0.02] backdrop-blur-3xl border border-white/5 px-6 py-6 rounded-xl mb-8 shadow-2xl group overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 rounded-full blur-[100px] group-hover:bg-blue-500/15 transition-all duration-700"></div>
                    </div>

                    <div class="relative flex items-center gap-6">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-indigo-500/20 border border-white/10 flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tighter leading-none">Configurações</h2>
                            <p class="text-[10px] text-blue-400 font-bold uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                Gerenciamento de Perfil e Segurança
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Informações Básicas -->
                    <div class="bg-white/[0.02] backdrop-blur-3xl border border-white/5 p-4 sm:p-8 rounded-xl shadow-xl flex flex-col gap-8">
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-6 bg-blue-500 rounded-full"></div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tight">Informações Pessoais</h3>
                        </div>
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="w-full"
                        />
                    </div>

                    <div class="space-y-8">
                        <!-- Segurança -->
                        <div class="bg-white/[0.02] backdrop-blur-3xl border border-white/5 p-4 sm:p-8 rounded-xl shadow-xl flex flex-col gap-8">
                            <div class="flex items-center gap-3">
                                <div class="w-1 h-6 bg-indigo-500 rounded-full"></div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tight">Alterar Senha</h3>
                            </div>
                            <UpdatePasswordForm class="w-full" />
                        </div>

                        <!-- Zona de Perigo -->
                        <div class="bg-red-500/[0.02] backdrop-blur-3xl border border-red-500/10 p-4 sm:p-8 rounded-xl shadow-xl flex flex-col gap-8">
                            <div class="flex items-center gap-3 text-red-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <h3 class="text-lg font-black uppercase tracking-tight">Zona Crítica</h3>
                            </div>
                            <DeleteUserForm class="w-full" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

