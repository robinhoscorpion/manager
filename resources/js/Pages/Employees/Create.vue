<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('employees.index')" class="p-2 text-gray-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-2xl font-bold text-white">Novo Funcionário</h2>
            </div>
        </template>

        <div class="mt-8 max-w-2xl">
            <div class="bg-[#191e29]/50 backdrop-blur-md border border-white/5 rounded-2xl shadow-xl p-8">
                <form @submit.prevent="submit" class="grid gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Nome Completo</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="login-input w-full"
                            placeholder="Ex: João Silva"
                            required
                        >
                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-400 mb-2 font-black uppercase tracking-widest text-[10px]">Cargo Formal</label>
                        <input 
                            v-model="form.role"
                            type="text" 
                            class="login-input w-full"
                            placeholder="Ex: Analista de Sistemas III"
                        >
                        <div v-if="form.errors.role" class="text-red-400 text-xs mt-1">{{ form.errors.role }}</div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Email Profissional</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="login-input w-full"
                            placeholder="exemplo@empresa.com"
                            required
                        >
                        <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Cargo</label>
                            <input 
                                v-model="form.position"
                                type="text" 
                                class="login-input w-full"
                                placeholder="Ex: Desenvolvedor Senior"
                                required
                            >
                            <div v-if="form.errors.position" class="text-red-400 text-xs mt-1">{{ form.errors.position }}</div>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Salário Mensal</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">R$</span>
                                <input 
                                    v-model="form.salary"
                                    type="number" 
                                    step="0.01"
                                    class="login-input w-full pl-10"
                                    placeholder="0,00"
                                    required
                                >
                            </div>
                            <div v-if="form.errors.salary" class="text-red-400 text-xs mt-1">{{ form.errors.salary }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Tipo de Contrato</label>
                            <select 
                                v-model="form.type"
                                class="login-input w-full"
                                required
                            >
                                <option value="Efetivado">Efetivado</option>
                                <option value="Terceirizado">Terceirizado</option>
                                <option value="Estagiário">Estagiário</option>
                                <option value="Freelancer">Freelancer</option>
                            </select>
                            <div v-if="form.errors.type" class="text-red-400 text-xs mt-1">{{ form.errors.type }}</div>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Carga Horária</label>
                            <input 
                                v-model="form.workload"
                                type="text" 
                                class="login-input w-full"
                                placeholder="Ex: 40 Horas"
                                required
                            >
                            <div v-if="form.errors.workload" class="text-red-400 text-xs mt-1">{{ form.errors.workload }}</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Data de Contratação</label>
                        <input 
                            v-model="form.hired_at"
                            type="date" 
                            class="login-input w-full"
                            required
                        >
                        <div v-if="form.errors.hired_at" class="text-red-400 text-xs mt-1">{{ form.errors.hired_at }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-4">
                        <Link 
                            :href="route('employees.index')"
                            class="px-6 py-2.5 text-gray-400 hover:text-white font-medium transition-colors"
                        >
                            Cancelar
                        </Link>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-2.5 bg-gradient-to-r from-cyan-500 to-purple-600 text-white rounded-xl font-bold shadow-lg hover:shadow-cyan-500/20 transition-all duration-300 transform hover:-translate-y-0.5 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Salvando...' : 'Cadastrar Funcionário' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
