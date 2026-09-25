<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    availableTags: Object,
});

const form = useForm({
    dispatch_mode: props.settings?.dispatch_mode || 'manual',
    auto_send_email: props.settings?.auto_send_email ?? true,
    auto_send_whatsapp: props.settings?.auto_send_whatsapp ?? false,
    notify_first_access: props.settings?.notify_first_access ?? true,
    whatsapp_template: props.settings?.whatsapp_template || '',
    email_subject: props.settings?.email_subject || '',
    email_template: props.settings?.email_template || '',
});

const activeTab = ref('general'); // 'general', 'whatsapp', 'email'

const submit = () => {
    form.post(route('admin.settings.welcome_access.update'), {
        preserveScroll: true,
    });
};

const insertTag = (field, tag) => {
    if (field === 'whatsapp') {
        form.whatsapp_template += tag;
    } else if (field === 'email_body') {
        form.email_template += tag;
    } else if (field === 'email_subject') {
        form.email_subject += tag;
    }
};
</script>

<template>
    <Head title="Configurações de Boas-Vindas e Acessos" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12 max-w-[1600px] mx-auto">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Premium Header & Toolbar (Igual à barra de Atendimentos) -->
                    <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                        
                        <!-- Top Row: Title & Main Action -->
                        <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                                <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="text-center sm:text-left">
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Boas-Vindas e Acessos</h2>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                        Configurações do Sistema
                                    </p>
                                </div>
                            </div>

                            <!-- Main Save Action Button -->
                            <div class="w-full sm:w-auto">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-6 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                                >
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm font-semibold text-white">{{ form.processing ? 'Salvando...' : 'Salvar Configurações' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs Bar -->
                    <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                        <div class="flex border-b border-slate-200/80 dark:border-slate-800/80 gap-6">
                            <button type="button" @click="activeTab = 'general'" :class="activeTab === 'general' ? 'border-brand-green text-brand-green font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="pb-3 border-b-2 text-xs font-bold uppercase tracking-widest transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Geral & Automação
                            </button>
                            <button type="button" @click="activeTab = 'whatsapp'" :class="activeTab === 'whatsapp' ? 'border-brand-green text-brand-green font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="pb-3 border-b-2 text-xs font-bold uppercase tracking-widest transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                Template WhatsApp
                            </button>
                            <button type="button" @click="activeTab = 'email'" :class="activeTab === 'email' ? 'border-brand-green text-brand-green font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="pb-3 border-b-2 text-xs font-bold uppercase tracking-widest transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Template E-mail
                            </button>
                        </div>
                    </div>

                    <!-- Tab 1: General & Automation Settings -->
                    <div v-show="activeTab === 'general'" class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 space-y-6 shadow-sm">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-1">Modo de Envio das Credenciais</h3>
                            <p class="text-xs text-slate-500">Escolha se os acessos serão disparados manualmente pelo Pós-Venda ou automaticamente ao aprovar contratos.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <label class="relative flex p-4 rounded-xl border cursor-pointer transition-all" :class="form.dispatch_mode === 'manual' ? 'bg-brand-green/10 border-brand-green/40 text-brand-green dark:text-brand-green' : 'bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400'">
                                    <input type="radio" v-model="form.dispatch_mode" value="manual" class="sr-only">
                                    <div>
                                        <span class="font-bold text-sm block">✋ Envio Manual (Pós-Venda)</span>
                                        <span class="text-xs text-slate-500 mt-1 block">O operador do Pós-Venda revisa os clientes e clica nos botões de WhatsApp ou E-mail para realizar os envios.</span>
                                    </div>
                                </label>

                                <label class="relative flex p-4 rounded-xl border cursor-pointer transition-all" :class="form.dispatch_mode === 'automatic' ? 'bg-brand-green/10 border-brand-green/40 text-brand-green dark:text-brand-green' : 'bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400'">
                                    <input type="radio" v-model="form.dispatch_mode" value="automatic" class="sr-only">
                                    <div>
                                        <span class="font-bold text-sm block">⚡ Envio Automático ao Aprovar Venda</span>
                                        <span class="text-xs text-slate-500 mt-1 block">Assim que a proposta/contrato for aprovada no sistema, as credenciais e instruções de acesso são disparadas automaticamente.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <hr class="border-slate-200 dark:border-slate-800">

                        <!-- Automation Options -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase text-slate-600 dark:text-slate-400 tracking-wider">Canais de Automação</h4>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.auto_send_email" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                                <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Disparar E-mail de Boas-Vindas automaticamente ao aprovar contrato</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.notify_first_access" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                                <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Registrar log e notificar equipe quando o sócio realizar o Primeiro Acesso</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tab 2: WhatsApp Template -->
                    <div v-show="activeTab === 'whatsapp'" class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 space-y-4 shadow-sm">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-1">Modelo de Mensagem do WhatsApp</h3>
                            <p class="text-xs text-slate-500">Esta mensagem será pré-carregada quando o operador clicar no botão do WhatsApp ou no disparo automático.</p>
                        </div>

                        <!-- Tags Helper Bar -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clique para Inserir Tag Dinâmica:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" v-for="(desc, tag) in availableTags" :key="tag" @click="insertTag('whatsapp', tag)" class="px-2.5 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[10px] font-mono font-bold text-brand-green dark:text-brand-green hover:bg-brand-green/10 transition-colors" :title="desc">
                                    {{ tag }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <textarea v-model="form.whatsapp_template" rows="10" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs p-3 font-mono focus:ring-brand-green/20"></textarea>
                        </div>
                    </div>

                    <!-- Tab 3: Email Template -->
                    <div v-show="activeTab === 'email'" class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 p-6 space-y-4 shadow-sm">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-1">Modelo de E-mail de Boas-Vindas</h3>
                            <p class="text-xs text-slate-500">Defina o assunto e o corpo do e-mail que será enviado para os novos sócios.</p>
                        </div>

                        <!-- Tags Helper Bar -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clique para Inserir Tag Dinâmica:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" v-for="(desc, tag) in availableTags" :key="tag" @click="insertTag('email_body', tag)" class="px-2.5 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[10px] font-mono font-bold text-brand-green dark:text-brand-green hover:bg-brand-green/10 transition-colors" :title="desc">
                                    {{ tag }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Assunto do E-mail</label>
                            <input v-model="form.email_subject" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs px-3 py-2 focus:ring-brand-green/20">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Corpo da Mensagem (Texto)</label>
                            <textarea v-model="form.email_template" rows="8" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs p-3 focus:ring-brand-green/20"></textarea>
                        </div>
                    </div>

                    <!-- Bottom Save Action Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest bg-brand-green hover:bg-[#485638] text-white transition-colors flex items-center gap-2 shadow-sm">
                            <span v-if="form.processing">Salvando...</span>
                            <span v-else>Salvar Configurações</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
