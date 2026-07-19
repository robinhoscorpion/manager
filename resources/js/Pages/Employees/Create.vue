<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import '@/../css/login_style.css'; // Reusing input styles

const form = useForm({
    name: '',
    role: '',
    email: '',
    position: '',
    salary: '',
    type: 'Efetivado',
    workload: '44 Horas',
    status: true,
    is_active: true,
    hired_at: new Date().toISOString().split('T')[0],
});

const submit = () => {
    form.post(route('employees.store'));
};
</script>

<template>
    <Head title="Novo Funcionário" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <Link :href="route('employees.index')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Novo Funcionário</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Cadastro de Colaborador
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-3xl">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8 rounded-[20px] shadow-sm flex flex-col gap-8">
                        <form @submit.prevent="submit" class="grid gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Nome Completo</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                    placeholder="Ex: João Silva"
                                    required
                                >
                                <div v-if="form.errors.name" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Cargo Formal</label>
                                <input 
                                    v-model="form.role"
                                    type="text" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                    placeholder="Ex: Analista de Sistemas III"
                                >
                                <div v-if="form.errors.role" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.role }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Email Profissional</label>
                                <input 
                                    v-model="form.email"
                                    type="email" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                    placeholder="exemplo@empresa.com"
                                    required
                                >
                                <div v-if="form.errors.email" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.email }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Cargo</label>
                                    <input 
                                        v-model="form.position"
                                        type="text" 
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                        placeholder="Ex: Desenvolvedor Senior"
                                        required
                                    >
                                    <div v-if="form.errors.position" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.position }}</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Salário Mensal</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400 font-medium">R$</span>
                                        <input 
                                            v-model="form.salary"
                                            type="number" 
                                            step="0.01"
                                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                            placeholder="0.00"
                                            required
                                        >
                                    </div>
                                    <div v-if="form.errors.salary" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.salary }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Tipo de Contrato</label>
                                    <select 
                                        v-model="form.type"
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm appearance-none"
                                        required
                                    >
                                        <option value="Efetivado">Efetivado</option>
                                        <option value="Terceirizado">Terceirizado</option>
                                        <option value="Estagiário">Estagiário</option>
                                        <option value="Freelancer">Freelancer</option>
                                    </select>
                                    <div v-if="form.errors.type" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.type }}</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Carga Horária</label>
                                    <input 
                                        v-model="form.workload"
                                        type="text" 
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm"
                                        placeholder="Ex: 40 Horas"
                                        required
                                    >
                                    <div v-if="form.errors.workload" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.workload }}</div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Data de Contratação</label>
                                <input 
                                    v-model="form.hired_at"
                                    type="date" 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 transition-all shadow-sm [color-scheme:light] dark:[color-scheme:dark]"
                                    required
                                >
                                <div v-if="form.errors.hired_at" class="text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-widest mt-2 px-1">{{ form.errors.hired_at }}</div>
                            </div>

                            <div class="flex justify-end gap-3 mt-4">
                                <Link 
                                    :href="route('employees.index')"
                                    class="px-8 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-[12px] font-bold uppercase text-[10px] tracking-widest transition-all shadow-sm flex items-center justify-center"
                                >
                                    Cancelar
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-8 py-3 bg-brand-green hover:bg-brand-green/90 text-white rounded-[12px] font-bold uppercase text-[10px] tracking-widest shadow-sm active:scale-95 transition-all flex items-center justify-center disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Salvando...' : 'Cadastrar Funcionário' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
