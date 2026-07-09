<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

// Mock Data para a interface visual
const welcomeMetrics = ref({
    new_clients: 15,
    pending: 8,
    sent: 7
});

const clients = ref([
    {
        id: 1,
        name: 'Maria Silva',
        phone: '5511999999999',
        formatted_phone: '(11) 99999-9999',
        email: 'maria.silva@exemplo.com',
        product: 'Consultoria Premium',
        date: '2026-07-08',
        status: 'pending'
    },
    {
        id: 2,
        name: 'João Pedro Alves',
        phone: '5511888888888',
        formatted_phone: '(11) 88888-8888',
        email: 'joao.alves@exemplo.com',
        product: 'Plano Básico Anual',
        date: '2026-07-08',
        status: 'pending'
    },
    {
        id: 3,
        name: 'Ana Carolina',
        phone: '5511777777777',
        formatted_phone: '(11) 77777-7777',
        email: 'ana.carolina@exemplo.com',
        product: 'Mentoria Vip',
        date: '2026-07-07',
        status: 'sent_whatsapp'
    },
    {
        id: 4,
        name: 'Carlos Mendes',
        phone: '5511666666666',
        formatted_phone: '(11) 66666-6666',
        email: 'carlos.mendes@exemplo.com',
        product: 'Consultoria Premium',
        date: '2026-07-06',
        status: 'sent_email'
    }
]);

const search = ref('');
const statusFilter = ref('');

const filteredClients = computed(() => {
    return clients.value.filter(client => {
        const matchSearch = client.name.toLowerCase().includes(search.value.toLowerCase()) || 
                            client.email.toLowerCase().includes(search.value.toLowerCase());
        const matchStatus = statusFilter.value === '' || client.status === statusFilter.value;
        return matchSearch && matchStatus;
    });
});

// Modals State
const showWhatsappModal = ref(false);
const showEmailModal = ref(false);
const selectedClient = ref(null);

const whatsappMessage = ref('');
const emailSubject = ref('');
const emailBody = ref('');

// Helper Functions
const formatDate = (dateStr) => {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(dateStr));
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
    whatsappMessage.value = `Olá ${client.name}! Seja muito bem-vindo(a).\n\nEstamos muito felizes em ter você conosco no ${client.product}. Se precisar de qualquer ajuda, estamos à disposição por aqui!`;
    showWhatsappModal.value = true;
};

const sendWhatsapp = () => {
    const encodedMessage = encodeURIComponent(whatsappMessage.value);
    const url = `https://wa.me/${selectedClient.value.phone}?text=${encodedMessage}`;
    window.open(url, '_blank');
    
    // Simula a marcação como enviado
    selectedClient.value.status = 'sent_whatsapp';
    showWhatsappModal.value = false;
};

const openEmail = (client) => {
    selectedClient.value = client;
    emailSubject.value = `Bem-vindo(a) à Nossa Empresa, ${client.name}!`;
    emailBody.value = `Olá ${client.name},\n\nSeja muito bem-vindo(a)!\n\nEstamos muito felizes em ter você conosco no serviço ${client.product}.\n\nEste é um e-mail de boas-vindas. Nossa equipe entrará em contato em breve com os próximos passos.\n\nAtenciosamente,\nEquipe de Pós-venda`;
    showEmailModal.value = true;
};

const sendEmail = () => {
    // Aqui implementaremos a requisição real para o backend no futuro
    alert('E-mail enviado com sucesso (Simulação)');
    selectedClient.value.status = 'sent_email';
    showEmailModal.value = false;
};
</script>

<template>
    <Head title="Boas-vindas - Pós-venda" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-green/10 text-brand-green flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-black text-xl text-slate-800 dark:text-white uppercase tracking-widest">
                        Boas-vindas
                    </h2>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Gestão de onboarding de clientes</p>
                </div>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto space-y-6">
            
            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Novos Clientes (Mês)</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ welcomeMetrics.new_clients }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Pendentes de Boas-vindas</p>
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
            </div>

            <!-- Main Content Area -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                
                <!-- Filter Bar -->
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-2 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    <div class="relative w-full sm:w-96 flex gap-2">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Buscar cliente..." class="w-full pl-8 pr-2 py-1.5 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-brand-green/20 placeholder-slate-400">
                        </div>
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0 hide-scrollbar">
                        <button @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Todos
                        </button>
                        <button @click="statusFilter = 'pending'" :class="statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Pendentes
                        </button>
                        <button @click="statusFilter = 'sent_whatsapp'" :class="statusFilter === 'sent_whatsapp' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Enviado Zap
                        </button>
                        <button @click="statusFilter = 'sent_email'" :class="statusFilter === 'sent_email' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
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
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Contato</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Produto/Serviço</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Data</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="client in filteredClients" :key="client.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-xs">
                                            {{ client.name.charAt(0) }}
                                        </div>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ client.name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-slate-600 dark:text-slate-300">{{ client.formatted_phone }}</span>
                                        <span class="text-[9px] text-slate-500">{{ client.email }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ client.product }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ formatDate(client.date) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span :class="getStatusBadge(client.status).color" class="inline-flex px-2 py-0.5 rounded-md border text-[9px] font-bold uppercase tracking-widest">
                                        {{ getStatusBadge(client.status).label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openWhatsapp(client)" class="p-1.5 rounded-md bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20 transition-colors" title="Enviar WhatsApp">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                        </button>
                                        <button @click="openEmail(client)" class="p-1.5 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 transition-colors" title="Enviar E-mail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredClients.length === 0">
                                <td colspan="6" class="px-3 py-8 text-center text-slate-500 text-xs">
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
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Enviar WhatsApp</h3>
                        <p class="text-xs text-slate-500">Para: {{ selectedClient?.name }} ({{ selectedClient?.formatted_phone }})</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Mensagem (Editável)</label>
                    <textarea v-model="whatsappMessage" rows="5" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm p-3 focus:ring-brand-green/20"></textarea>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showWhatsappModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button @click="sendWhatsapp" class="px-4 py-2 rounded-xl text-xs font-bold bg-green-500 text-white hover:bg-green-600 transition-colors flex items-center gap-2">
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
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Enviar E-mail de Boas-vindas</h3>
                        <p class="text-xs text-slate-500">Para: {{ selectedClient?.email }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Assunto</label>
                        <input v-model="emailSubject" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-brand-green/20">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Mensagem</label>
                        <textarea v-model="emailBody" rows="6" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm p-3 focus:ring-brand-green/20"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showEmailModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button @click="sendEmail" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center gap-2">
                        Disparar E-mail
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
