<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import '@/../css/login_style.css';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="login-page login-page-container">
        <Head title="Acessar conta" />

        <div class="bg-blob" style="top: -100px; left: -100px;"></div>
        <div class="bg-blob" style="bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(146, 0, 255, 0.2) 0%, transparent 70%);"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">MANAGER</div>
                    <p class="login-subtitle">Bem-vindo ao futuro do gerenciamento.</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-400 text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail de Acesso</label>
                        <div class="input-wrapper">
                            <input
                                id="email"
                                type="email"
                                class="login-input"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="seu@email.com"
                            />
                        </div>
                        <span v-if="form.errors.email" class="error-text">{{ form.errors.email }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Senha</label>
                        <div class="input-wrapper">
                            <input
                                id="password"
                                type="password"
                                class="login-input"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                v-model="form.remember"
                                class="rounded border-gray-700 bg-gray-800 text-cyan-500 focus:ring-cyan-500"
                            />
                            <span class="ms-2 text-xs text-gray-400">Lembrar de mim</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-cyan-500 hover:text-white transition-colors"
                        >
                            Esqueceu a senha?
                        </Link>
                    </div>

                    <button
                        class="login-button"
                        :class="{ 'opacity-50 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Acessando...' : 'Entrar na Plataforma' }}
                    </button>
                </form>

                <div class="login-footer">
                    Ainda não tem uma conta? 
                    <Link :href="route('register')" class="login-link">Criar conta</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scoped styles if needed, but most are in login_style.css */
</style>
