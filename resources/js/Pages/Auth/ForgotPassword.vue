<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import '@/../css/login_style.css';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="login-page login-page-container">
        <Head title="Recuperar senha" />

        <div class="bg-blob" style="top: -100px; left: -100px;"></div>
        <div class="bg-blob" style="bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(146, 0, 255, 0.2) 0%, transparent 70%);"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">MANAGER</div>
                    <p class="login-subtitle">Esqueceu sua senha?</p>
                </div>

                <div class="mb-6 text-xs text-gray-400 text-center leading-relaxed">
                    Sem problemas. Informe seu endereço de e-mail e enviaremos um link para você redefinir sua senha.
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-400 text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail Cadastrado</label>
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

                    <button
                        class="login-button"
                        :class="{ 'opacity-50 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enviando...' : 'Enviar Link de Recuperação' }}
                    </button>
                </form>

                <div class="login-footer">
                    <Link :href="route('login')" class="login-link">Voltar para login</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
