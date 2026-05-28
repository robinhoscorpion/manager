<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import '@/../css/login_style.css';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="login-page login-page-container">
        <Head title="Redefinir senha" />

        <div class="bg-blob" style="top: -100px; left: -100px;"></div>
        <div class="bg-blob" style="bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(146, 0, 255, 0.2) 0%, transparent 70%);"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">MANAGER</div>
                    <p class="login-subtitle">Redefina sua senha abaixo.</p>
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail</label>
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
                        <label class="form-label" for="password">Nova Senha</label>
                        <div class="input-wrapper">
                            <input
                                id="password"
                                type="password"
                                class="login-input"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirmar Nova Senha</label>
                        <div class="input-wrapper">
                            <input
                                id="password_confirmation"
                                type="password"
                                class="login-input"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                        <span v-if="form.errors.password_confirmation" class="error-text">{{ form.errors.password_confirmation }}</span>
                    </div>

                    <button
                        class="login-button"
                        :class="{ 'opacity-50 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Redefinindo...' : 'Atualizar Senha' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
