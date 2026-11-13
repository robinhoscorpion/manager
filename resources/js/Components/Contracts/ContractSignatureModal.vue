<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    salesServiceId: [Number, String],
    title: String,
});

const emit = defineEmits(['close', 'changed']);

const can = (permission) => usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
const canManage = computed(() => can('pos_venda.gestao_contratos.gerenciar'));
const canConfigure = computed(() => can('configuracoes.assinatura.acessar'));

const loading = ref(false);
const loadError = ref('');
const data = ref(null);
const processing = ref(false);
const copied = ref(null);
const showHistory = ref(false);

// Formulário de envio
const deliveryMethod = ref('email');
const signers = ref([]);
const selectedTypes = ref([]);

const ROLE_LABELS = { titular: '1º Titular', segundo_titular: '2º Titular', empresa: 'Empresa', testemunha: 'Testemunha' };
const DEFAULT_DOCUMENTS = { contrato: 'Contrato', proposta: 'Proposta', rci: 'RCI' };

const documentTypes = computed(() => data.value?.document_types || DEFAULT_DOCUMENTS);

// Situação de cada documento: envio em destaque (em andamento ou assinado) e o último envio
const documents = computed(() => Object.entries(documentTypes.value).map(([type, label]) => {
    const list = (data.value?.signatures || []).filter(s => s.document_type === type);
    const open = list.find(s => s.is_open);
    const last = list[0] || null;
    const current = open || (last?.status === 'signed' ? last : null);
    return { type, label, current, last };
}));

const sendableDocuments = computed(() => documents.value.filter(d => !d.current));
const showForm = computed(() => data.value?.provider?.supports_sending && sendableDocuments.value.length > 0);
const openSignatures = computed(() => documents.value.map(d => d.current).filter(s => s?.is_open));

const statusStyles = {
    draft: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    sent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
    viewed: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
    partially_signed: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    signed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    refused: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    cancelled: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    expired: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    error: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
};

const signerStatus = {
    pending: { label: 'Aguardando', cls: 'text-slate-500' },
    viewed: { label: 'Visualizou', cls: 'text-indigo-600 dark:text-indigo-400' },
    signed: { label: 'Assinou', cls: 'text-emerald-600 dark:text-emerald-400' },
    refused: { label: 'Recusou', cls: 'text-red-600 dark:text-red-400' },
};

const deliveryLabel = (key) => data.value?.delivery_methods?.[key] || key;

const load = async () => {
    if (!props.salesServiceId) return;
    loading.value = true;
    loadError.value = '';
    try {
        const response = await window.axios.get(route('after-sales.contract-signature.show', props.salesServiceId));
        data.value = response.data;
        deliveryMethod.value = data.value.default_delivery in (data.value.delivery_methods || {}) ? data.value.default_delivery : Object.keys(data.value.delivery_methods || { email: 1 })[0];
        signers.value = (data.value.suggested_signers || []).map(s => ({ ...s, include: true }));
        // Por padrão, todos os documentos ainda não enviados/assinados vêm marcados
        selectedTypes.value = sendableDocuments.value.map(d => d.type);
    } catch (e) {
        loadError.value = e.response?.data?.message || 'Não foi possível carregar os dados da assinatura.';
    } finally {
        loading.value = false;
    }
};

watch(() => [props.show, props.salesServiceId], ([show]) => {
    if (show) {
        data.value = null;
        showHistory.value = false;
        notice.value = '';
        load();
    }
}, { immediate: true });

// ─── Acompanhamento em tempo real ───
// Com a janela aberta e algum envio em andamento, consulta o status a cada 15s e avisa quando alguém assina.
const POLL_INTERVAL = 15000;
const lastCheck = ref(null);
const notice = ref('');
let pollTimer = null;

const isListening = computed(() => props.show && openSignatures.value.length > 0);

const poll = async () => {
    if (!props.show || document.hidden || processing.value) return;
    try {
        const before = openSignatures.value;
        const response = await window.axios.get(route('after-sales.contract-signature.show', props.salesServiceId), { params: { sync: 1 } });
        // Atualiza só os envios (não mexe no formulário que o usuário pode estar preenchendo)
        data.value = { ...data.value, signatures: response.data.signatures };
        lastCheck.value = new Date();

        const messages = [];
        let changed = false;
        for (const old of before) {
            const now = response.data.signatures.find(s => s.id === old.id);
            if (!now) continue;
            const newlySigned = now.signers.filter((s, i) => s.status === 'signed' && old.signers[i]?.status !== 'signed');
            if (now.status === 'signed' && old.status !== 'signed') {
                messages.push(`${now.document_label} assinado por todos — o PDF assinado já está disponível.`);
            } else if (newlySigned.length) {
                messages.push(`${now.document_label}: ${newlySigned.map(s => s.name).join(', ')} assinou.`);
            } else if (now.status === 'refused' && old.status !== 'refused') {
                messages.push(`${now.document_label}: um signatário recusou.`);
            }
            if (now.status !== old.status || newlySigned.length) changed = true;
        }
        if (messages.length) notice.value = messages.join(' ');
        if (changed) emit('changed');
    } catch (e) {
        // Falha pontual de rede: tenta de novo no próximo ciclo
    }
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

watch(isListening, (listening) => {
    stopPolling();
    if (listening) {
        lastCheck.value = new Date();
        pollTimer = setInterval(poll, POLL_INTERVAL);
    }
}, { immediate: true });

const onVisibilityChange = () => { if (!document.hidden && isListening.value) poll(); };
document.addEventListener('visibilitychange', onVisibilityChange);

onBeforeUnmount(() => {
    stopPolling();
    document.removeEventListener('visibilitychange', onVisibilityChange);
});

const addWitness = () => {
    signers.value.push({ role: 'testemunha', role_label: ROLE_LABELS.testemunha, name: '', email: '', phone: '', cpf: '', include: true, removable: true });
};

const removeSigner = (index) => signers.value.splice(index, 1);

// Aviso do que falta antes de enviar (o servidor valida de novo)
const missingContact = (signer) => {
    if (!signer.include) return '';
    if (!signer.name?.trim()) return 'Informe o nome';
    if (signer.role === 'empresa') return signer.email ? '' : 'E-mail obrigatório';
    if (deliveryMethod.value === 'email' && !signer.email) return 'E-mail obrigatório para envio por e-mail';
    if (deliveryMethod.value === 'whatsapp' && (signer.phone || '').replace(/\D/g, '').length < 10) return 'Celular com DDD obrigatório para WhatsApp';
    return '';
};
const canSend = computed(() => selectedTypes.value.length > 0
    && signers.value.some(s => s.include && s.role === 'titular')
    && !signers.value.some(missingContact));

const runAction = (method, url, payload = {}) => {
    processing.value = true;
    router[method](url, payload, {
        preserveScroll: true,
        preserveState: true,
        onFinish: async () => {
            processing.value = false;
            await load();
            emit('changed');
        },
    });
};

const send = () => {
    runAction('post', route('after-sales.contract-signature.store', props.salesServiceId), {
        delivery_method: deliveryMethod.value,
        document_types: selectedTypes.value,
        signers: signers.value.filter(s => s.include).map(({ role, name, email, phone, cpf }) => ({ role, name, email, phone, cpf })),
    });
};

const sync = (signature) => runAction('post', route('after-sales.contract-signature.sync', signature.id));
const resend = (signature) => runAction('post', route('after-sales.contract-signature.resend', signature.id));
const cancel = (signature) => {
    if (confirm(`Cancelar o envio de ${signature.document_label}? Os links de assinatura deixarão de valer.`)) {
        runAction('post', route('after-sales.contract-signature.cancel', signature.id));
    }
};

const copyLink = async (url, key) => {
    try {
        await navigator.clipboard.writeText(url);
        copied.value = key;
        setTimeout(() => { copied.value = null; }, 2000);
    } catch (e) {
        window.prompt('Copie o link de assinatura:', url);
    }
};

const inputClass = 'w-full h-9 px-3 bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green disabled:opacity-60';
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="3xl">
        <div class="flex flex-col max-h-[90vh]">
            <!-- Cabeçalho -->
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Assinatura digital</h3>
                        <p class="text-xs text-slate-500 truncate">{{ title }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span v-if="data?.provider?.supports_sending" class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ data.provider.label }}</span>
                    <span v-if="data?.provider?.supports_sending && data?.sandbox" class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">Sandbox</span>
                    <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="p-6 overflow-y-auto space-y-6">
                <div v-if="loading && !data" class="py-12 text-center text-sm text-slate-400">Carregando...</div>
                <div v-else-if="loadError" class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 text-sm text-red-700 dark:text-red-300">{{ loadError }}</div>

                <template v-else-if="data">
                    <!-- Sem provedor -->
                    <div v-if="!data.provider.supports_sending && !data.signatures.length" class="p-5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/5">
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Nenhum provedor de assinatura digital ativo.</p>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                            O contrato pode ser assinado em papel e anexado em Pós-venda → Entrega de Contrato.
                            <template v-if="canConfigure">Para assinar digitalmente, escolha um provedor em
                                <a :href="route('admin.settings.signature.index')" class="underline font-bold">Configurações → Assinatura Digital</a>.
                            </template>
                        </p>
                    </div>

                    <!-- Aviso de mudança detectada -->
                    <div v-if="notice" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30">
                        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">✓ {{ notice }}</span>
                        <button @click="notice = ''" class="text-xs text-emerald-700 dark:text-emerald-300 hover:underline shrink-0">Fechar</button>
                    </div>

                    <!-- Situação de cada documento -->
                    <div v-if="data.signatures.length" class="space-y-3">
                        <div v-for="doc in documents" :key="doc.type" class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50/70 dark:bg-slate-800/30 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-slate-800 dark:text-slate-100">{{ doc.label }}</span>
                                    <span v-if="doc.current" class="px-2 py-0.5 rounded-md text-[11px] font-bold" :class="statusStyles[doc.current.status]">{{ doc.current.status_label }}</span>
                                    <span v-else-if="doc.last" class="px-2 py-0.5 rounded-md text-[11px] font-bold" :class="statusStyles[doc.last.status]">Último: {{ doc.last.status_label }}</span>
                                    <span v-else class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">Não enviado</span>
                                </div>
                                <div v-if="doc.current" class="flex flex-wrap gap-1.5">
                                    <button v-if="doc.current.is_open" @click="sync(doc.current)" :disabled="processing" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 disabled:opacity-50">Atualizar</button>
                                    <button v-if="doc.current.is_open && canManage" @click="resend(doc.current)" :disabled="processing" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 hover:bg-sky-100 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300 disabled:opacity-50">Lembrete</button>
                                    <button v-if="doc.current.is_open && canManage" @click="cancel(doc.current)" :disabled="processing" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-red-50 hover:bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-300 disabled:opacity-50">Cancelar</button>
                                    <a v-if="doc.current.has_signed_file" :href="route('after-sales.contract-signature.download', [doc.current.id, 'assinado'])" target="_blank" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white">PDF assinado</a>
                                    <a :href="route('after-sales.contract-signature.download', [doc.current.id, 'original'])" target="_blank" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">PDF enviado</a>
                                </div>
                            </div>

                            <div v-if="doc.current" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <div class="px-4 py-1.5 text-[11px] text-slate-500">
                                    Enviado {{ doc.current.sent_at }}<template v-if="doc.current.sent_by"> por {{ doc.current.sent_by }}</template> · {{ deliveryLabel(doc.current.delivery_method) }}
                                </div>
                                <div v-for="(signer, i) in doc.current.signers" :key="i" class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ signer.role_label }}</div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">{{ signer.name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate">{{ signer.email || signer.phone || 'Assinatura por link' }}</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold" :class="signerStatus[signer.status]?.cls">
                                            {{ signerStatus[signer.status]?.label || signer.status }}<template v-if="signer.signed_at"> · {{ signer.signed_at }}</template>
                                        </span>
                                        <button v-if="signer.sign_url && signer.status !== 'signed' && doc.current.is_open" @click="copyLink(signer.sign_url, doc.type + i)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-brand-green/40 text-brand-green hover:bg-brand-green/10">
                                            {{ copied === doc.type + i ? 'Copiado!' : 'Copiar link' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="doc.last && doc.last.error_message" class="px-4 py-2 text-[11px] text-red-600 dark:text-red-400">{{ doc.last.error_message }}</div>
                        </div>

                        <p v-if="isListening" class="flex items-center gap-2 text-[11px] text-slate-500">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Acompanhando em tempo real — a janela atualiza sozinha a cada 15 segundos<template v-if="lastCheck"> (última verificação {{ lastCheck.toLocaleTimeString('pt-BR') }})</template>.
                        </p>
                    </div>

                    <!-- Formulário de envio -->
                    <div v-if="showForm" class="space-y-5" :class="{ 'border-t border-slate-100 dark:border-slate-800 pt-5': data.signatures.length }">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Documentos para enviar <span class="normal-case font-medium tracking-normal">(cada um vai como um envio separado)</span></label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="doc in documents"
                                    :key="doc.type"
                                    class="flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold transition-colors"
                                    :class="doc.current
                                        ? 'border-slate-200 dark:border-slate-800 text-slate-400 cursor-not-allowed'
                                        : (selectedTypes.includes(doc.type) ? 'border-brand-green bg-brand-green/5 text-slate-800 dark:text-slate-100 cursor-pointer' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 cursor-pointer')"
                                >
                                    <input type="checkbox" :value="doc.type" v-model="selectedTypes" :disabled="!!doc.current || !canManage" class="rounded text-brand-green focus:ring-brand-green">
                                    {{ doc.label }}
                                    <span v-if="doc.current" class="font-medium">({{ doc.current.is_open ? 'em andamento' : 'assinado' }})</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Canal de envio</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(label, key) in data.delivery_methods"
                                    :key="key"
                                    type="button"
                                    @click="deliveryMethod = key"
                                    class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors"
                                    :class="deliveryMethod === key ? 'bg-brand-green text-white border-brand-green' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-brand-green/40'"
                                >{{ label }}</button>
                            </div>
                            <p v-if="deliveryMethod === 'link'" class="text-[11px] text-slate-500 mt-2">Cada signatário recebe um link exclusivo por documento — copie e envie, ou abra no tablet para assinar na hora.</p>
                            <p v-else-if="selectedTypes.length > 1" class="text-[11px] text-slate-500 mt-2">O cliente receberá {{ selectedTypes.length }} mensagens, uma para cada documento.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Signatários</label>
                                <button v-if="canManage" type="button" @click="addWitness" class="text-xs font-bold text-brand-green hover:underline">+ Testemunha</button>
                            </div>

                            <div class="space-y-3">
                                <div v-for="(signer, index) in signers" :key="index" class="p-4 rounded-xl border transition-colors" :class="signer.include ? 'border-slate-200 dark:border-slate-700' : 'border-dashed border-slate-200 dark:border-slate-800 opacity-60'">
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input v-if="signer.role !== 'titular'" type="checkbox" v-model="signer.include" class="rounded text-brand-green focus:ring-brand-green">
                                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-500">{{ signer.role_label || ROLE_LABELS[signer.role] }}</span>
                                        </label>
                                        <button v-if="signer.removable" type="button" @click="removeSigner(index)" class="text-[11px] font-bold text-red-500 hover:underline">Remover</button>
                                    </div>
                                    <div v-if="signer.include" class="grid sm:grid-cols-4 gap-2">
                                        <input v-model="signer.name" type="text" placeholder="Nome completo" :disabled="!canManage" :class="inputClass" class="sm:col-span-2">
                                        <input v-model="signer.email" type="email" placeholder="E-mail" :disabled="!canManage" :class="inputClass">
                                        <input v-model="signer.phone" type="text" placeholder="Celular com DDD" :disabled="!canManage" :class="inputClass">
                                    </div>
                                    <p v-if="missingContact(signer)" class="text-[11px] text-amber-600 dark:text-amber-400 mt-2">{{ missingContact(signer) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="canManage" class="flex items-center justify-end gap-3 pt-2">
                            <span v-if="data.sandbox" class="text-[11px] text-amber-600">Modo teste: os documentos não têm validade jurídica.</span>
                            <button type="button" @click="send" :disabled="processing || !canSend" class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-green hover:bg-[#485638] disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ processing ? 'Gerando e enviando...' : (selectedTypes.length > 1 ? `Enviar ${selectedTypes.length} documentos` : 'Enviar para assinatura') }}
                            </button>
                        </div>
                        <p v-else class="text-xs text-slate-500 text-right">Você não tem permissão para enviar documentos para assinatura.</p>
                    </div>

                    <!-- Histórico -->
                    <div v-if="data.signatures.length" class="border-t border-slate-100 dark:border-slate-800 pt-4">
                        <button type="button" @click="showHistory = !showHistory" class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                            {{ showHistory ? 'Ocultar histórico' : 'Ver histórico' }}
                        </button>
                        <div v-if="showHistory" class="mt-3 space-y-3">
                            <div v-for="signature in data.signatures" :key="signature.id" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40">
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span>
                                        <strong class="text-slate-700 dark:text-slate-200">{{ signature.document_label }}</strong>
                                        <span class="px-2 py-0.5 rounded-md font-bold ml-2" :class="statusStyles[signature.status]">{{ signature.status_label }}</span>
                                        <span class="text-slate-500 ml-2">{{ signature.sent_at || '—' }}</span>
                                    </span>
                                    <a v-if="signature.has_signed_file" :href="route('after-sales.contract-signature.download', [signature.id, 'assinado'])" target="_blank" class="font-bold text-brand-green hover:underline shrink-0">PDF assinado</a>
                                </div>
                                <ul v-if="signature.log?.length" class="mt-2 space-y-1">
                                    <li v-for="(entry, i) in [...signature.log].reverse()" :key="i" class="text-[11px] text-slate-500">
                                        <span class="font-mono text-slate-400">{{ new Date(entry.at).toLocaleString('pt-BR') }}</span> — {{ entry.message }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </Modal>
</template>
