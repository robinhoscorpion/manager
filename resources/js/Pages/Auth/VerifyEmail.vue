<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import '@/../css/login_style.css';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <div class="login-page login-page-container">
        <Head title="Verificar e-mail" />

        <div class="bg-blob" style="top: -100px; left: -100px;"></div>
        <div class="bg-blob" style="bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(146, 0, 255, 0.2) 0%, transparent 70%);"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">MANAGER</div>
                    <p class="login-subtitle">Verifique seu e-mail</p>
                </div>

                <div class="mb-6 text-xs text-gray-400 text-center leading-relaxed">
                    Obrigado por se cadastrar! Antes de começar, você poderia verificar seu endereço de e-mail clicando no link que acabamos de enviar para você? Se você não recebeu o e-mail, teremos prazer em enviar outro.
                </div>

                <div
                    class="mb-4 text-xs font-medium text-green-400 text-center"
                    v-if="verificationLinkSent"
                >
                    Um novo link de verificação foi enviado para o endereço de e-mail fornecido durante o registro.
                </div>

                <form @submit.prevent="submit">
                    <button
                        class="login-button"
                        :class="{ 'opacity-50 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Enviando...' : 'Reenviar E-mail de Verificação' }}
                    </button>
                    
                    <div class="login-footer">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="login-link text-xs"
                        >Sair da conta</Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
