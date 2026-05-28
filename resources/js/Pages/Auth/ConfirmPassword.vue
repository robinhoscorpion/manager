<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import '@/../css/login_style.css';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <div class="login-page login-page-container">
        <Head title="Confirmar senha" />

        <div class="bg-blob" style="top: -100px; left: -100px;"></div>
        <div class="bg-blob" style="bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(146, 0, 255, 0.2) 0%, transparent 70%);"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">MANAGER</div>
                    <p class="login-subtitle">Área Segura</p>
                </div>

                <div class="mb-6 text-xs text-gray-400 text-center leading-relaxed">
                    Esta é uma área segura da plataforma. Por favor, confirme sua senha antes de continuar.
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label" for="password">Sua Senha</label>
                        <div class="input-wrapper">
                            <input
                                id="password"
                                type="password"
                                class="login-input"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                autofocus
                                placeholder="••••••••"
                            />
                        </div>
                        <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>
                    </div>

                    <button
                        class="login-button"
                        :class="{ 'opacity-50 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Confirmando...' : 'Confirmar Acesso' }}
                    </button>
                </form>

                <div class="login-footer">
                    <Link :href="route('logout')" method="post" as="button" class="login-link">Sair</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
