<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    providers: Array,
    webhookUrls: Object,
});

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};
const canManage = computed(() => can('configuracoes.assinatura.gerenciar'));

// Credenciais nunca vêm preenchidas: em branco = manter a atual
const emptyCredentials = () => Object.fromEntries(props.providers.map(p => [p.key, Object.fromEntries(p.credential_fields.map(f => [f.key, '']))]));

const form = useForm({
    provider: props.settings.provider,
    sandbox: !!props.settings.sandbox,
    default_delivery: props.settings.default_delivery,
    include_second_holder: !!props.settings.include_second_holder,
    message: props.settings.message || '',
    company_signer: {
        enabled: !!props.settings.company_signer?.enabled,
        name: props.settings.company_signer?.name || '',
        email: props.settings.company_signer?.email || '',
        cpf: props.settings.company_signer?.cpf || '',
    },
    webhook_secret: '',
    credentials: emptyCredentials(),
});

const selectedProvider = computed(() => props.providers.find(p => p.key === form.provider));
const isFilled = (provider, field) => !!props.settings.credentials_filled?.[provider]?.[field];

const deliveryOptions = computed(() => {
    const methods = selectedProvider.value?.delivery_methods || {};
    return Object.keys(methods).length ? methods : { email: 'E-mail', whatsapp: 'WhatsApp', link: 'Link' };
});

const submit = () => {
    form.put(route('admin.settings.signature.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.credentials = emptyCredentials();
            form.webhook_secret = '';
        },
    });
};

const testing = ref(false);
const testConnection = () => {
    testing.value = true;
    router.post(route('admin.settings.signature.test'), {}, {
        preserveScroll: true,
        onFinish: () => { testing.value = false; },
    });
};

const copied = ref('');
const copy = async (text, key) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = key;
        setTimeout(() => { copied.value = ''; }, 2000);
    } catch (e) {
        window.prompt('Copie a URL:', text);
    }
};

const inputClass = 'w-full h-11 px-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green disabled:opacity-60';
const labelClass = 'block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1';
</script>

<template>
    <Head title="Assinatura Digital" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full max-w-5xl mx-auto sm:px-6 lg:px-8 h-auto pt-8 pb-12">

                <!-- Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none">
                    <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Assinatura Digital</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Provedor usado para assinar os contratos
                                </p>
                            </div>
                        </div>
                        <span v-if="selectedProvider?.supports_sending && form.sandbox" class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-widest bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 w-fit">Modo teste (sandbox)</span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- 1. Provedor -->
                    <section class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-[20px] p-6 shadow-sm dark:shadow-none">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">1. Provedor</h3>
                        <p class="text-xs text-slate-500 mb-4">Escolha como os contratos serão assinados. Dá para trocar a qualquer momento — envios já feitos continuam no provedor original.</p>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <label
                                v-for="provider in providers"
                                :key="provider.key"
                                class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors"
                                :class="form.provider === provider.key ? 'border-brand-green bg-brand-green/5' : 'border-slate-200 dark:border-slate-700 hover:border-brand-green/40'"
                            >
                                <input type="radio" v-model="form.provider" :value="provider.key" :disabled="!canManage" class="mt-0.5 text-brand-green focus:ring-brand-green">
                                <div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ provider.label }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ provider.supports_sending ? 'Envia o contrato e acompanha as assinaturas automaticamente.' : 'Sem integração: o contrato assinado é anexado em Pós-venda → Entrega de Contrato.' }}
                                    </div>
                                </div>
                            </label>
                        </div>
                    </section>

                    <!-- 2. Credenciais -->
                    <section v-if="selectedProvider?.supports_sending" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-[20px] p-6 shadow-sm dark:shadow-none">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">2. Credenciais — {{ selectedProvider.label }}</h3>
                                <p class="text-xs text-slate-500">Ficam criptografadas e nunca são exibidas. Deixe em branco para manter a atual.</p>
                            </div>
                            <button type="button" @click="testConnection" :disabled="testing || form.isDirty" class="px-4 py-2 text-xs font-bold rounded-xl border border-brand-green/40 text-brand-green hover:bg-brand-green/10 disabled:opacity-50 shrink-0" :title="form.isDirty ? 'Salve as alterações antes de testar' : ''">
                                {{ testing ? 'Testando...' : 'Testar conexão' }}
                            </button>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div v-for="field in selectedProvider.credential_fields" :key="field.key">
                                <label :class="labelClass">{{ field.label }}</label>
                                <input
                                    v-model="form.credentials[selectedProvider.key][field.key]"
                                    :type="field.type === 'password' ? 'password' : 'text'"
                                    autocomplete="new-password"
                                    :disabled="!canManage"
                                    :placeholder="isFilled(selectedProvider.key, field.key) ? '•••••••• (configurado)' : 'Não configurado'"
                                    :class="inputClass"
                                >
                                <p v-if="field.help" class="text-[11px] text-slate-400 mt-1 px-1">{{ field.help }}</p>
                            </div>
                        </div>

                        <label class="mt-5 flex items-start gap-3 p-4 rounded-xl border cursor-pointer" :class="form.sandbox ? 'border-amber-300 bg-amber-50 dark:bg-amber-500/5 dark:border-amber-500/30' : 'border-slate-200 dark:border-slate-700'">
                            <input type="checkbox" v-model="form.sandbox" :disabled="!canManage" class="mt-0.5 rounded text-amber-500 focus:ring-amber-500">
                            <div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100">Modo teste (sandbox)</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">Documentos de teste não consomem créditos e são apagados pelo provedor após alguns dias. <strong>Desligue para assinar contratos reais.</strong></div>
                            </div>
                        </label>
                    </section>

                    <!-- 3. Envio -->
                    <section v-if="selectedProvider?.supports_sending" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-[20px] p-6 shadow-sm dark:shadow-none space-y-5">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">3. Envio e signatários</h3>
                            <p class="text-xs text-slate-500">Valores sugeridos na hora de enviar — dá para ajustar em cada contrato.</p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClass">Canal de envio padrão</label>
                                <select v-model="form.default_delivery" :disabled="!canManage" :class="inputClass">
                                    <option v-for="(label, key) in deliveryOptions" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-3 sm:mt-6 cursor-pointer">
                                <input type="checkbox" v-model="form.include_second_holder" :disabled="!canManage" class="rounded text-brand-green focus:ring-brand-green">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Incluir o 2º titular como signatário (quando houver)</span>
                            </label>
                        </div>

                        <div>
                            <label :class="labelClass">Mensagem enviada junto com o contrato</label>
                            <textarea v-model="form.message" rows="2" maxlength="500" :disabled="!canManage" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green resize-none"></textarea>
                        </div>

                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.company_signer.enabled" :disabled="!canManage" class="rounded text-brand-green focus:ring-brand-green">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Representante da empresa também assina</span>
                            </label>
                            <div v-if="form.company_signer.enabled" class="grid sm:grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label :class="labelClass">Nome *</label>
                                    <input v-model="form.company_signer.name" type="text" :disabled="!canManage" :class="inputClass">
                                    <p v-if="form.errors['company_signer.name']" class="text-red-500 text-[11px] mt-1 px-1">{{ form.errors['company_signer.name'] }}</p>
                                </div>
                                <div>
                                    <label :class="labelClass">E-mail *</label>
                                    <input v-model="form.company_signer.email" type="email" :disabled="!canManage" :class="inputClass">
                                    <p v-if="form.errors['company_signer.email']" class="text-red-500 text-[11px] mt-1 px-1">{{ form.errors['company_signer.email'] }}</p>
                                </div>
                                <div>
                                    <label :class="labelClass">CPF</label>
                                    <input v-model="form.company_signer.cpf" type="text" :disabled="!canManage" :class="inputClass">
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 4. Webhook -->
                    <section v-if="selectedProvider?.supports_sending && webhookUrls?.[form.provider]" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-[20px] p-6 shadow-sm dark:shadow-none space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">4. Avisos automáticos (webhook)</h3>
                            <p class="text-xs text-slate-500">
                                Cadastre esta URL no painel do provedor (na Autentique: Painel do Desenvolvedor → Webhooks, eventos de documento e assinatura).
                                Assim o status atualiza na hora. Sem webhook, o sistema consulta os envios em andamento a cada 30 minutos.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <input :value="webhookUrls[form.provider]" readonly :class="inputClass" class="font-mono text-xs">
                            <button type="button" @click="copy(webhookUrls[form.provider], 'webhook')" class="px-4 text-xs font-bold rounded-xl bg-slate-800 text-white hover:bg-slate-700 shrink-0">
                                {{ copied === 'webhook' ? 'Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                        <div class="sm:w-1/2">
                            <label :class="labelClass">Segredo de assinatura do webhook (opcional)</label>
                            <input v-model="form.webhook_secret" type="password" autocomplete="new-password" :disabled="!canManage" :placeholder="settings.has_webhook_secret ? '•••••••• (configurado — digite - para remover)' : 'Não configurado'" :class="inputClass">
                            <p class="text-[11px] text-slate-400 mt-1 px-1">Se o provedor fornecer um segredo para validar os avisos, informe aqui.</p>
                        </div>
                    </section>

                    <div v-if="canManage" class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-semibold text-white bg-brand-green hover:bg-[#485638] rounded-[12px] shadow-sm disabled:opacity-50">
                            Salvar configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
