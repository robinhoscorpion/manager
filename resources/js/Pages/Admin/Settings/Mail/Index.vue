<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    settings: Object,
    hasSavedPassword: Boolean,
});

const form = useForm({
    mailer: props.settings?.mailer || 'smtp',
    host: props.settings?.host || '',
    port: props.settings?.port || 587,
    encryption: props.settings?.encryption || 'tls',
    username: props.settings?.username || '',
    password: '',
    from_address: props.settings?.from_address || '',
    from_name: props.settings?.from_name || '',
});

const showPassword = ref(false);
const testEmail = ref('');
const isTesting = ref(false);
const testResult = ref(null); // { success: boolean, message: string }

const submit = () => {
    form.post(route('admin.settings.mail.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Limpa o campo de senha após salvar por segurança
            form.password = '';
        }
    });
};

const applyPreset = (preset) => {
    if (preset === 'gmail') {
        form.host = 'smtp.gmail.com';
        form.port = 587;
        form.encryption = 'tls';
    } else if (preset === 'hostinger') {
        form.host = 'smtp.hostinger.com';
        form.port = 465;
        form.encryption = 'ssl';
    } else if (preset === 'outlook') {
        form.host = 'smtp.office365.com';
        form.port = 587;
        form.encryption = 'tls';
    } else if (preset === 'brevo') {
        form.host = 'smtp-relay.brevo.com';
        form.port = 587;
        form.encryption = 'tls';
    } else if (preset === 'locaweb') {
        form.host = 'email-ssl.com.br';
        form.port = 465;
        form.encryption = 'ssl';
    }
};

const runTest = async () => {
    if (!testEmail.value) {
        testResult.value = {
            success: false,
            message: 'Por favor, informe um endereço de e-mail de destino para o teste.'
        };
        return;
    }

    if (!form.host || !form.port || !form.from_address) {
        testResult.value = {
            success: false,
            message: 'Preencha ao menos o Host SMTP, a Porta e o E-mail de Remetente antes de testar.'
        };
        return;
    }

    isTesting.value = true;
    testResult.value = null;

    try {
        const response = await axios.post(route('admin.settings.mail.test'), {
            test_email: testEmail.value,
            host: form.host,
            port: form.port,
            encryption: form.encryption,
            username: form.username,
            password: form.password,
            from_address: form.from_address,
            from_name: form.from_name,
        });

        testResult.value = {
            success: true,
            message: response.data.message || 'Conexão estabelecida e e-mail de teste enviado com sucesso!'
        };
    } catch (error) {
        const errorMsg = error.response?.data?.message || error.message || 'Falha desconhecida ao conectar ao servidor SMTP.';
        testResult.value = {
            success: false,
            message: errorMsg
        };
    } finally {
        isTesting.value = false;
    }
};
</script>

<template>
    <Head title="Configurações de Servidor de E-mail (SMTP)" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12 max-w-[1600px] mx-auto">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Header & Main Actions -->
                    <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                        <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                                <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Servidor de E-mail (SMTP)</h2>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                        Configurações de Disparo
                                    </p>
                                </div>
                            </div>

                            <!-- Botão Salvar -->
                            <div class="w-full sm:w-auto flex items-center gap-3">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-6 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
                                >
                                    <svg v-if="!form.processing" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm font-semibold text-white">{{ form.processing ? 'Salvando...' : 'Salvar Configurações' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Presets Rápidos de Provedores -->
                        <div class="px-6 py-3 bg-slate-50/70 dark:bg-slate-900/60 rounded-b-[20px] flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-slate-500 font-semibold uppercase tracking-wider text-[11px] mr-1">Preenchimento Rápido:</span>
                            <button type="button" @click="applyPreset('gmail')" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-green text-slate-700 dark:text-slate-300 hover:text-brand-green transition-all shadow-2xs font-medium">
                                Gmail (587 TLS)
                            </button>
                            <button type="button" @click="applyPreset('hostinger')" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-green text-slate-700 dark:text-slate-300 hover:text-brand-green transition-all shadow-2xs font-medium">
                                Hostinger (465 SSL)
                            </button>
                            <button type="button" @click="applyPreset('outlook')" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-green text-slate-700 dark:text-slate-300 hover:text-brand-green transition-all shadow-2xs font-medium">
                                Outlook / 365 (587 TLS)
                            </button>
                            <button type="button" @click="applyPreset('locaweb')" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-green text-slate-700 dark:text-slate-300 hover:text-brand-green transition-all shadow-2xs font-medium">
                                Locaweb (465 SSL)
                            </button>
                            <button type="button" @click="applyPreset('brevo')" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-green text-slate-700 dark:text-slate-300 hover:text-brand-green transition-all shadow-2xs font-medium">
                                Brevo / Sendinblue (587 TLS)
                            </button>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                        <!-- Left Column: Formulário de Configuração SMTP (7 Colunas) -->
                        <div class="lg:col-span-7 space-y-6">
                            
                            <!-- Card: Conexão do Servidor -->
                            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-5">
                                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <div class="w-8 h-8 rounded-lg bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-sm">1</div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Parâmetros de Conexão</h3>
                                        <p class="text-xs text-slate-500">Dados do servidor de correio eletrônico fornecido pela sua hospedagem.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <!-- Host -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Host SMTP <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="form.host" 
                                            placeholder="smtp.dominio.com.br"
                                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                            required
                                        />
                                        <p v-if="form.errors.host" class="text-xs text-red-500 mt-1">{{ form.errors.host }}</p>
                                    </div>

                                    <!-- Porta -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Porta <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            v-model="form.port" 
                                            placeholder="587"
                                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                            required
                                        />
                                        <p v-if="form.errors.port" class="text-xs text-red-500 mt-1">{{ form.errors.port }}</p>
                                    </div>
                                </div>

                                <!-- Criptografia -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                        Criptografia de Segurança
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer text-xs font-semibold transition-all" :class="form.encryption === 'tls' ? 'bg-brand-green/10 border-brand-green text-brand-green dark:text-brand-green font-bold' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'">
                                            <input type="radio" v-model="form.encryption" value="tls" class="sr-only" />
                                            <span>TLS / STARTTLS</span>
                                        </label>
                                        <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer text-xs font-semibold transition-all" :class="form.encryption === 'ssl' ? 'bg-brand-green/10 border-brand-green text-brand-green dark:text-brand-green font-bold' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'">
                                            <input type="radio" v-model="form.encryption" value="ssl" class="sr-only" />
                                            <span>SSL</span>
                                        </label>
                                        <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer text-xs font-semibold transition-all" :class="form.encryption === 'none' ? 'bg-brand-green/10 border-brand-green text-brand-green dark:text-brand-green font-bold' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'">
                                            <input type="radio" v-model="form.encryption" value="none" class="sr-only" />
                                            <span>Nenhuma</span>
                                        </label>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1.5">Recomendado: <strong>587 (TLS)</strong> ou <strong>465 (SSL)</strong> para a maioria dos provedores.</p>
                                </div>
                            </div>

                            <!-- Card: Autenticação -->
                            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-5">
                                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <div class="w-8 h-8 rounded-lg bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-sm">2</div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Credenciais de Autenticação</h3>
                                        <p class="text-xs text-slate-500">Usuário e senha para autorização do envio.</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Username -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Usuário / E-mail de Login
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="form.username" 
                                            placeholder="exemplo@dominio.com.br"
                                            autocomplete="username"
                                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                        />
                                        <p v-if="form.errors.username" class="text-xs text-red-500 mt-1">{{ form.errors.username }}</p>
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                Senha SMTP / Senha de App
                                            </label>
                                            <span v-if="hasSavedPassword && !form.password" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Senha salva no sistema
                                            </span>
                                        </div>
                                        <div class="relative">
                                            <input 
                                                :type="showPassword ? 'text' : 'password'" 
                                                v-model="form.password" 
                                                :placeholder="hasSavedPassword ? '•••••••••••• (deixe vazio para manter)' : 'Digite a senha do SMTP'"
                                                autocomplete="current-password"
                                                class="w-full pl-3.5 pr-11 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                            />
                                            <button 
                                                type="button" 
                                                @click="showPassword = !showPassword"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1"
                                                title="Mostrar/ocultar senha"
                                            >
                                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card: Identificação do Remetente -->
                            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-5">
                                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <div class="w-8 h-8 rounded-lg bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-sm">3</div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Identificação do Remetente</h3>
                                        <p class="text-xs text-slate-500">Como o remetente aparecerá na caixa de entrada dos clientes.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- E-mail do Remetente -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            E-mail do Remetente (From) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="email" 
                                            v-model="form.from_address" 
                                            placeholder="atendimento@dominio.com.br"
                                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                            required
                                        />
                                        <p v-if="form.errors.from_address" class="text-xs text-red-500 mt-1">{{ form.errors.from_address }}</p>
                                    </div>

                                    <!-- Nome do Remetente -->
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            Nome Exibido do Remetente <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="form.from_name" 
                                            placeholder="Portal do Sócio - Atendimento"
                                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white"
                                            required
                                        />
                                        <p v-if="form.errors.from_name" class="text-xs text-red-500 mt-1">{{ form.errors.from_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Teste de Disparo em Tempo Real (5 Colunas) -->
                        <div class="lg:col-span-5 space-y-6">
                            
                            <!-- Card de Teste -->
                            <div class="bg-gradient-to-b from-white to-slate-50 dark:from-[#0f1219] dark:to-slate-900/80 rounded-[20px] border-2 border-brand-green/30 dark:border-brand-green/40 p-6 shadow-md relative overflow-hidden">
                                
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-brand-green text-white flex items-center justify-center shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Testar Conexão e Disparo</h3>
                                        <p class="text-xs text-slate-500">Envie uma mensagem de verificação agora</p>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-400 mb-5 leading-relaxed">
                                    Digite seu e-mail abaixo e clique no botão para testar a comunicação direta com o servidor SMTP usando os parâmetros preenchidos ao lado.
                                </p>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                            E-mail de Destino do Teste
                                        </label>
                                        <input 
                                            type="email" 
                                            v-model="testEmail" 
                                            placeholder="seuemail@exemplo.com"
                                            class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-green/30 focus:border-brand-green outline-none dark:text-white shadow-2xs"
                                        />
                                    </div>

                                    <button 
                                        type="button" 
                                        @click="runTest"
                                        :disabled="isTesting"
                                        class="w-full flex items-center justify-center gap-2 bg-slate-900 dark:bg-brand-green hover:bg-slate-800 dark:hover:bg-[#485638] text-white px-5 py-3 rounded-xl font-bold text-sm transition-all duration-300 shadow-md hover:shadow-lg disabled:opacity-60 cursor-pointer"
                                    >
                                        <svg v-if="!isTesting" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ isTesting ? 'Conectando e enviando...' : 'Disparar E-mail de Teste' }}</span>
                                    </button>
                                </div>

                                <!-- Feedback do Teste -->
                                <div v-if="testResult" class="mt-5 transition-all duration-300">
                                    <!-- Sucesso -->
                                    <div v-if="testResult.success" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300">
                                        <div class="flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-xs uppercase tracking-wider text-emerald-900 dark:text-emerald-200 mb-1">Conexão Estabelecida com Sucesso!</h4>
                                                <p class="text-xs leading-relaxed">{{ testResult.message }}</p>
                                                <span class="inline-block mt-2 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">Verifique a caixa de entrada (ou spam) do endereço informado.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Erro -->
                                    <div v-else class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-red-800 dark:text-red-300">
                                        <div class="flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/60 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0 mt-0.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h4 class="font-bold text-xs uppercase tracking-wider text-red-900 dark:text-red-200 mb-1">Falha na Conexão SMTP</h4>
                                                <p class="text-xs leading-relaxed break-words font-mono bg-white/60 dark:bg-black/40 p-2.5 rounded-lg border border-red-200/60 dark:border-red-900/40 mt-1.5">{{ testResult.message }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de Dicas de Configuração -->
                            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Dicas Importantes
                                </h4>
                                <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-2 leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-brand-green font-bold">•</span>
                                        <span><strong>Gmail / Google Workspace:</strong> Se sua conta tiver verificação em 2 etapas, utilize uma <em>"Senha de Aplicativo"</em> gerada nas configurações de segurança do Google.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-brand-green font-bold">•</span>
                                        <span><strong>Remetente autorizado:</strong> O <em>E-mail do Remetente</em> geralmente deve pertencer ao mesmo domínio autenticado no SMTP para evitar que os e-mails caiam em spam.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-brand-green font-bold">•</span>
                                        <span><strong>Portas recomendadas:</strong> Porta <strong>587</strong> para criptografia TLS ou <strong>465</strong> para criptografia SSL.</span>
                                    </li>
                                </ul>
                            </div>

                        </div>

                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
