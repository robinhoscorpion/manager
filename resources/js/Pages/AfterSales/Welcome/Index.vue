<script setup>
import { ref, computed } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    clients: Array,
    welcomeMetrics: Object,
    filters: Object,
    portalUrl: String,
    welcomeSettings: Object,
});

const pageProps = usePage().props;
const baseUrl = computed(() => props.portalUrl || window.location.origin);

const welcomeMetrics = computed(() => props.welcomeMetrics || { new_clients: 0, pending: 0, sent: 0, access_created: 0 });
const clients = computed(() => props.clients || []);

const search = ref('');
const statusFilter = ref('');
const today = new Date().toISOString().split('T')[0];
const startDate = ref(props.filters?.start_date || today);
const endDate = ref(props.filters?.end_date || today);

const formatTemplate = (template, client) => {
    if (!template) return '';
    return template
        .replaceAll('{nome}', client?.name || 'Sócio')
        .replaceAll('{cpf}', client?.cpf || 'Não informado')
        .replaceAll('{email}', client?.email || '')
        .replaceAll('{contrato}', client?.contract || 'S/N')
        .replaceAll('{produto}', client?.product || '')
        .replaceAll('{link_portal}', baseUrl.value);
};

const fetchFilteredData = () => {
    router.get(route('after-sales.welcome.index'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearDates = () => {
    startDate.value = '';
    endDate.value = '';
    fetchFilteredData();
};

const filteredClients = computed(() => {
    return clients.value.filter(client => {
        const matchSearch = client.name.toLowerCase().includes(search.value.toLowerCase()) || 
                            client.email.toLowerCase().includes(search.value.toLowerCase()) ||
                            (client.cpf && client.cpf.includes(search.value));
        const matchStatus = statusFilter.value === '' || client.status === statusFilter.value;
        return matchSearch && matchStatus;
    });
});

// Modals State
const showWhatsappModal = ref(false);
const showEmailModal = ref(false);
const showTempPasswordModal = ref(false);
const selectedClient = ref(null);

const whatsappMessage = ref('');
const emailSubject = ref('');
const emailBody = ref('');

// Email Modal & Progress State
const isSendingEmail = ref(false);
const emailProgress = ref(0);
const emailStepText = ref('');
const emailStatusResult = ref(null);

// Temp Password State
const customPasswordInput = ref('');
const generatedPassword = ref('');
const isGeneratingPassword = ref(false);
const showCustomPassword = ref(false);
const showGeneratedPassword = ref(true);

// Helper Functions
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    if (dateStr.includes('/')) {
        return dateStr;
    }
    try {
        return new Intl.DateTimeFormat('pt-BR').format(new Date(dateStr));
    } catch (e) {
        return '-';
    }
};

const getStatusBadge = (status) => {
    const badges = {
        'pending': { label: 'Pendente', color: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20' },
        'sent_whatsapp': { label: 'Enviado Zap', color: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20' },
        'sent_email': { label: 'Enviado E-mail', color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' }
    };
    return badges[status] || badges['pending'];
};

// Actions
const openWhatsapp = (client) => {
    selectedClient.value = client;
    const defaultTpl = props.welcomeSettings?.whatsapp_template;
    if (defaultTpl) {
        whatsappMessage.value = formatTemplate(defaultTpl, client);
    } else {
        const cpfDisplay = client.cpf ? client.cpf : 'Seu CPF';
        whatsappMessage.value = `Olá ${client.name}! Seja muito bem-vindo(a) ao Portal do Sócio.\n\n` +
            `Estamos felizes em ter você conosco no ${client.product}.\n\n` +
            `🔑 *COMO ACESSAR SEU PORTAL DO SÓCIO:*\n` +
            `🌐 *Link:* ${baseUrl.value}\n` +
            `👤 *Seu Login:* ${cpfDisplay} (ou seu e-mail)\n` +
            `📌 *Primeiro Acesso:* Clique na opção "Primeiro Acesso" no portal, informe seu CPF e cadastre sua senha de forma rápida e segura.\n\n` +
            `Se precisar de suporte, estamos à disposição por aqui!`;
    }
    showWhatsappModal.value = true;
};

const sendWhatsapp = () => {
    const encodedMessage = encodeURIComponent(whatsappMessage.value);
    const url = `https://wa.me/${selectedClient.value.phone}?text=${encodedMessage}`;
    window.open(url, '_blank');
    
    router.patch(route('after-sales.welcome.status.update', selectedClient.value.id), {
        status: 'sent_whatsapp'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showWhatsappModal.value = false;
        }
    });
};

const openEmail = (client) => {
    selectedClient.value = client;
    isSendingEmail.value = false;
    emailProgress.value = 0;
    emailStepText.value = '';
    emailStatusResult.value = null;

    const subjectTpl = props.welcomeSettings?.email_subject;
    const bodyTpl = props.welcomeSettings?.email_template;
    
    emailSubject.value = subjectTpl ? formatTemplate(subjectTpl, client) : `Bem-vindo(a) ao Portal do Sócio, ${client.name}!`;
    emailBody.value = bodyTpl ? formatTemplate(bodyTpl, client) : `Olá ${client.name},\n\nSeja muito bem-vindo(a)!\n\nSeu cadastro no serviço ${client.product} já está disponível no Portal do Sócio.\n\nPara acessar, acesse o portal (${baseUrl.value}), vá na opção 'Primeiro Acesso', digite seu CPF (${client.cpf || 'cadastrado'}) e crie sua senha com segurança.\n\nQualquer dúvida, estamos à disposição.\n\nAtenciosamente,\nEquipe de Pós-venda`;
    showEmailModal.value = true;
};

const sendEmail = () => {
    if (!selectedClient.value?.email) {
        alert('Este cliente não possui e-mail cadastrado.');
        return;
    }

    isSendingEmail.value = true;
    emailProgress.value = 15;
    emailStepText.value = 'Conectando ao servidor de e-mail (SMTP)...';
    emailStatusResult.value = null;

    let progressInterval = setInterval(() => {
        if (emailProgress.value < 85) {
            emailProgress.value += 15;
            if (emailProgress.value >= 40 && emailProgress.value < 70) {
                emailStepText.value = 'Validando destinatário e compilando credenciais...';
            } else if (emailProgress.value >= 70) {
                emailStepText.value = 'Transmitindo mensagem para o provedor...';
            }
        }
    }, 400);

    router.post(route('after-sales.welcome.send-email', selectedClient.value.id), {
        message: emailBody.value,
        subject: emailSubject.value,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            clearInterval(progressInterval);
            emailProgress.value = 100;
            isSendingEmail.value = false;

            const flashWarning = page.props?.flash?.warning || usePage().props?.flash?.warning;
            const flashSuccess = page.props?.flash?.success || usePage().props?.flash?.success;

            if (flashWarning) {
                emailStepText.value = 'Status atualizado com aviso do servidor SMTP.';
                emailStatusResult.value = {
                    type: 'warning',
                    title: 'Aviso de Envio',
                    message: flashWarning
                };
            } else {
                emailStepText.value = 'E-mail disparado e entregue com sucesso!';
                emailStatusResult.value = {
                    type: 'success',
                    title: 'E-mail Enviado com Sucesso!',
                    message: flashSuccess || `O e-mail de boas-vindas foi entregue com sucesso para ${selectedClient.value.email}.`
                };
            }
        },
        onError: () => {
            clearInterval(progressInterval);
            emailProgress.value = 100;
            isSendingEmail.value = false;
            
            const flashError = usePage().props?.flash?.error || 'Ocorreu uma falha durante o disparo do e-mail. Verifique a conexão ou o endereço cadastrado.';
            emailStepText.value = 'Falha no envio do e-mail.';
            emailStatusResult.value = {
                type: 'error',
                title: 'Falha no Disparo do E-mail',
                message: flashError
            };
        }
    });
};

// Temp Password Modal
const openTempPasswordModal = (client) => {
    const currentSearch = search.value;
    selectedClient.value = client;
    customPasswordInput.value = '';
    generatedPassword.value = '';
    showCustomPassword.value = false;
    showGeneratedPassword.value = true;
    showTempPasswordModal.value = true;

    // Previne que o autofill de senhas do navegador preencha o e-mail no campo de busca da página
    setTimeout(() => {
        search.value = currentSearch;
    }, 50);
    setTimeout(() => {
        search.value = currentSearch;
    }, 250);
};

const generateTempPasswordAction = () => {
    isGeneratingPassword.value = true;
    router.post(route('after-sales.welcome.temp-password', selectedClient.value.id), {
        custom_password: customPasswordInput.value
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            isGeneratingPassword.value = false;
            const tempPass = page.props?.flash?.temp_password || usePage().props?.flash?.temp_password;
            if (tempPass) {
                generatedPassword.value = tempPass;
            } else if (customPasswordInput.value) {
                generatedPassword.value = customPasswordInput.value;
            }
        },
        onError: () => {
            isGeneratingPassword.value = false;
        }
    });
};

const copyGeneratedPassword = () => {
    if (generatedPassword.value) {
        navigator.clipboard.writeText(generatedPassword.value);
        alert('Senha copiada para a área de transferência!');
    }
};

const sendTempPasswordWhatsapp = () => {
    if (!generatedPassword.value) return;
    const client = selectedClient.value;
    const cpfDisplay = client.cpf ? client.cpf : 'Seu CPF';
    const message = `Olá ${client.name}! Seguem os seus dados de acesso ao Portal do Sócio:\n\n` +
        `🌐 *Link:* ${baseUrl.value}\n` +
        `👤 *Login (CPF):* ${cpfDisplay}\n` +
        `🔑 *Senha Provisória:* ${generatedPassword.value}\n\n` +
        `*Atenção:* Recomendamos alterar a sua senha no primeiro acesso ao portal.`;
    
    const encodedMessage = encodeURIComponent(message);
    const url = `https://wa.me/${client.phone}?text=${encodedMessage}`;
    window.open(url, '_blank');
    showTempPasswordModal.value = false;
};
</script>

<template>
    <Head title="Boas-vindas - Pós-venda" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12 max-w-[1600px] mx-auto space-y-6">
                
                <!-- Premium Header & Toolbar (Igual à barra de Atendimentos) -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Action -->
                    <div class="px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Boas-vindas</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Pós-Venda • Gestão de Onboarding e Acessos de Sócios
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div v-if="can('admin')" class="w-full sm:w-auto">
                            <Link 
                                :href="route('admin.settings.welcome_access.index')"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 text-xs font-bold uppercase tracking-wider"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Configurar Mensagens</span>
                            </Link>
                        </div>
                    </div>
                </div>
            
            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Novos Clientes</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ welcomeMetrics.new_clients }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Pendentes Boas-Vindas</p>
                        <h3 class="text-2xl font-black text-yellow-500">{{ welcomeMetrics.pending }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500/10 text-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Mensagens Enviadas</p>
                        <h3 class="text-2xl font-black text-brand-green">{{ welcomeMetrics.sent }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-brand-green/10 text-brand-green rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Senhas Criadas (Sócios)</p>
                        <h3 class="text-2xl font-black text-emerald-500">{{ welcomeMetrics.access_created }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                
                <!-- Filter Bar -->
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-3 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <!-- Search -->
                        <div class="relative w-full sm:w-56">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="search" type="search" name="search_filter_welcome_no_autofill" id="welcome_search_input" autocomplete="off" aria-autocomplete="none" data-lpignore="true" placeholder="Buscar por cliente, CPF, e-mail..." class="w-full pl-7 pr-2 h-8 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-brand-green/20 placeholder-slate-400">
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="flex items-center h-8 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 gap-1 shrink-0">
                            <input v-model="startDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <span class="text-slate-400 text-[9px] uppercase font-bold">até</span>
                            <input v-model="endDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <button v-if="startDate || endDate" @click="clearDates" class="ml-1 text-slate-400 hover:text-red-500 flex items-center" title="Limpar Datas">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Search Button -->
                        <button @click="fetchFilteredData" class="h-8 shrink-0 bg-brand-green text-white px-4 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-brand-green/90 transition-colors flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Buscar
                        </button>
                    </div>
                    
                    <!-- Status Filters -->
                    <div class="flex flex-wrap items-center gap-1.5 w-full xl:w-auto">
                        <button @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Todos
                        </button>
                        <button @click="statusFilter = 'pending'" :class="statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Pendentes
                        </button>
                        <button @click="statusFilter = 'sent_whatsapp'" :class="statusFilter === 'sent_whatsapp' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Enviado Zap
                        </button>
                        <button @click="statusFilter = 'sent_email'" :class="statusFilter === 'sent_email' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Enviado E-mail
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cliente</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Contato / CPF</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Produto/Serviço</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Data Venda</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Boas-Vindas</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Status Acesso Portal</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Disparar Credencial / Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="client in filteredClients" :key="client.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2" v-if="can('pos_venda.boas_vindas.gerenciar')">
                                        <div class="w-6 h-6 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-xs">
                                            {{ client.name.charAt(0) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ client.name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-600 dark:text-slate-300 font-semibold">{{ client.formatted_phone || '-' }}</span>
                                        <span class="text-[9px] text-slate-500">{{ client.email || 'Sem e-mail' }}</span>
                                        <span class="text-[9px] text-slate-400 font-mono" v-if="client.cpf">CPF: {{ client.cpf }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ client.product }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Contrato: {{ client.contract }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ formatDate(client.date) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span :class="getStatusBadge(client.status).color" class="inline-flex px-2 py-0.5 rounded-md border text-[9px] font-bold uppercase tracking-widest">
                                        {{ getStatusBadge(client.status).label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex flex-col items-center">
                                        <span v-if="client.has_password" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[9px] font-bold uppercase tracking-widest">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Acesso Ativo
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 text-[9px] font-bold uppercase tracking-widest">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Primeiro Acesso Pendente
                                        </span>
                                        <span v-if="client.password_set_at" class="text-[8px] text-slate-400 mt-0.5">Cadastrado em: {{ client.password_set_at }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Zap Button -->
                                        <button @click="openWhatsapp(client)" class="p-1.5 rounded-md bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20 transition-colors flex items-center gap-1" title="Enviar WhatsApp com Link de Primeiro Acesso">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                        </button>
                                        <!-- Email Button -->
                                        <button @click="openEmail(client)" class="p-1.5 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 transition-colors flex items-center gap-1" title="Enviar E-mail com Instruções de Acesso">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </button>
                                        <!-- Temp Password / Reset Button -->
                                        <button @click="openTempPasswordModal(client)" class="p-1.5 rounded-md bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-500/10 dark:text-purple-400 dark:hover:bg-purple-500/20 transition-colors flex items-center gap-1" title="Gerar Senha Provisória (Suporte)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredClients.length === 0">
                                <td colspan="7" class="px-3 py-8 text-center text-slate-500 text-xs">
                                    Nenhum cliente encontrado com os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal WhatsApp -->
        <Modal :show="showWhatsappModal" @close="showWhatsappModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Enviar Acesso via WhatsApp</h3>
                        <p class="text-xs text-slate-500">Para: {{ selectedClient?.name }} ({{ selectedClient?.formatted_phone }})</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Mensagem Formatada (Editável)</label>
                    <textarea v-model="whatsappMessage" rows="9" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs p-3 font-mono focus:ring-brand-green/20"></textarea>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="showWhatsappModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button type="button" @click="sendWhatsapp" class="px-4 py-2 rounded-xl text-xs font-bold bg-green-500 text-white hover:bg-green-600 transition-colors flex items-center gap-2">
                        Abrir no WhatsApp
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal E-mail -->
        <Modal :show="showEmailModal" @close="showEmailModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Enviar E-mail com Instruções de Acesso</h3>
                        <p class="text-xs text-slate-500">Para: {{ selectedClient?.email || 'Sem e-mail' }}</p>
                    </div>
                </div>

                <!-- Painel de Progresso / Carregamento / Confirmação -->
                <div v-if="isSendingEmail || emailStatusResult" class="p-4 rounded-xl border space-y-3 mb-5 transition-all duration-300"
                    :class="{
                        'bg-blue-500/10 border-blue-500/30': isSendingEmail,
                        'bg-emerald-500/10 border-emerald-500/30': emailStatusResult?.type === 'success',
                        'bg-amber-500/10 border-amber-500/30': emailStatusResult?.type === 'warning',
                        'bg-red-500/10 border-red-500/30': emailStatusResult?.type === 'error'
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <!-- Spinner animado enquanto dispara -->
                            <div v-if="isSendingEmail" class="w-4 h-4 rounded-full border-2 border-blue-500 border-t-transparent animate-spin"></div>
                            
                            <!-- Ícone de Sucesso -->
                            <svg v-else-if="emailStatusResult?.type === 'success'" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>

                            <!-- Ícone de Alerta -->
                            <svg v-else-if="emailStatusResult?.type === 'warning'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>

                            <!-- Ícone de Erro -->
                            <svg v-else-if="emailStatusResult?.type === 'error'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>

                            <span class="text-xs font-bold uppercase tracking-wider"
                                :class="{
                                    'text-blue-600 dark:text-blue-400': isSendingEmail,
                                    'text-emerald-600 dark:text-emerald-400': emailStatusResult?.type === 'success',
                                    'text-amber-600 dark:text-amber-400': emailStatusResult?.type === 'warning',
                                    'text-red-600 dark:text-red-400': emailStatusResult?.type === 'error'
                                }"
                            >
                                {{ isSendingEmail ? 'Disparando E-mail...' : emailStatusResult?.title }}
                            </span>
                        </div>
                        <span class="font-mono text-xs font-bold"
                            :class="{
                                'text-blue-600 dark:text-blue-400': isSendingEmail,
                                'text-emerald-600 dark:text-emerald-400': emailStatusResult?.type === 'success',
                                'text-amber-600 dark:text-amber-400': emailStatusResult?.type === 'warning',
                                'text-red-600 dark:text-red-400': emailStatusResult?.type === 'error'
                            }"
                        >
                            {{ emailProgress }}%
                        </span>
                    </div>

                    <!-- Barra de Progresso Animada -->
                    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-300 ease-out"
                            :style="{ width: emailProgress + '%' }"
                            :class="{
                                'bg-gradient-to-r from-blue-500 to-indigo-600 animate-pulse': isSendingEmail,
                                'bg-emerald-500': emailStatusResult?.type === 'success',
                                'bg-amber-500': emailStatusResult?.type === 'warning',
                                'bg-red-500': emailStatusResult?.type === 'error'
                            }"
                        ></div>
                    </div>

                    <!-- Texto com etapa/status -->
                    <p class="text-[11px] font-medium text-slate-600 dark:text-slate-300">
                        {{ emailStepText }}
                    </p>

                    <!-- Mensagem do resultado -->
                    <div v-if="emailStatusResult?.message" class="text-xs p-2.5 rounded-lg bg-white dark:bg-slate-900 border"
                        :class="{
                            'border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-200': emailStatusResult?.type === 'success',
                            'border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-200': emailStatusResult?.type === 'warning',
                            'border-red-200 dark:border-red-800/50 text-red-800 dark:text-red-200': emailStatusResult?.type === 'error'
                        }"
                    >
                        {{ emailStatusResult.message }}
                    </div>
                </div>

                <div v-if="!emailStatusResult && !isSendingEmail" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Assunto</label>
                        <input v-model="emailSubject" type="text" :disabled="isSendingEmail" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs px-3 py-2 focus:ring-brand-green/20">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Mensagem Adicional / Personalizada</label>
                        <textarea v-model="emailBody" rows="6" :disabled="isSendingEmail" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs p-3 focus:ring-brand-green/20"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button v-if="!isSendingEmail && !emailStatusResult" type="button" @click="showEmailModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    
                    <button v-if="emailStatusResult" type="button" @click="showEmailModal = false" class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 hover:bg-slate-900 transition-colors">
                        Concluir / Fechar
                    </button>

                    <button v-if="!emailStatusResult" type="button" @click="sendEmail" :disabled="isSendingEmail" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 transition-colors flex items-center gap-2">
                        <span v-if="isSendingEmail">Enviando E-mail...</span>
                        <span v-else>Disparar E-mail</span>
                        <svg v-if="!isSendingEmail" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal Senha Temporária / Reset (Suporte) -->
        <Modal :show="showTempPasswordModal" @close="showTempPasswordModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Gerar Senha Provisória para o Sócio</h3>
                        <p class="text-xs text-slate-500">Cliente: {{ selectedClient?.name }} (CPF: {{ selectedClient?.cpf || '-' }})</p>
                    </div>
                </div>

                <form autocomplete="off" @submit.prevent="generateTempPasswordAction">
                    <!-- Inputs ocultos para desorientar o preenchimento automático do navegador (autofill) -->
                    <input type="text" name="fake_email_prevent_autofill" style="display:none" tabindex="-1" autocomplete="username">
                    <input type="password" name="fake_password_prevent_autofill" style="display:none" tabindex="-1" autocomplete="current-password">

                    <div class="space-y-4">
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Esta opção permite definir uma senha provisória para o cliente diretamente no banco de dados. Útil caso o cliente precise de assistência imediata do suporte do Pós-Venda.
                        </p>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Senha Personalizada (Opcional - deixe em branco para gerar aleatória)</label>
                            <div class="relative">
                                <input v-model="customPasswordInput" :type="showCustomPassword ? 'text' : 'password'" name="custom_temp_password_input_no_fill" autocomplete="new-password" placeholder="Ex: Socio#2026" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs pl-3 pr-10 py-2 focus:ring-purple-500/20">
                                <button type="button" @click="showCustomPassword = !showCustomPassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-purple-600 transition-colors p-1" title="Mostrar / Ocultar Senha">
                                    <svg v-if="!showCustomPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.04 10.04 0 014.122-.963c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="generatedPassword" class="p-4 rounded-xl bg-purple-500/10 border border-purple-500/30 space-y-2">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest">Senha Temporária Criada com Sucesso:</p>
                                <button type="button" @click="showGeneratedPassword = !showGeneratedPassword" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1">
                                    <svg v-if="!showGeneratedPassword" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.04 10.04 0 014.122-.963c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                    {{ showGeneratedPassword ? 'Ocultar' : 'Exibir' }} Senha
                                </button>
                            </div>
                            <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-purple-200 dark:border-purple-800">
                                <span class="font-mono text-sm font-bold text-purple-700 dark:text-purple-300">
                                    {{ showGeneratedPassword ? generatedPassword : '••••••••' }}
                                </span>
                                <button type="button" @click="copyGeneratedPassword" class="px-2.5 py-1 text-[10px] font-bold bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded hover:bg-purple-200 transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    Copiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showTempPasswordModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Fechar</button>

                        <button v-if="!generatedPassword" type="submit" :disabled="isGeneratingPassword" class="px-4 py-2 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-700 transition-colors flex items-center gap-2">
                            <span v-if="isGeneratingPassword">Gerando...</span>
                            <span v-else>Gerar e Salvar Senha</span>
                        </button>

                        <button v-else type="button" @click="sendTempPasswordWhatsapp" class="px-4 py-2 rounded-xl text-xs font-bold bg-green-500 text-white hover:bg-green-600 transition-colors flex items-center gap-2">
                            Enviar Senha por WhatsApp
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
        </div>
    </AuthenticatedLayout>
</template>
