<script setup>
import { ref, computed, nextTick } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import * as docx from 'docx-preview';

const props = defineProps({
    templates: Array,
    products: Array,
});

const groupedProducts = computed(() => {
    const groups = {};
    props.products.forEach(product => {
        const typeName = product.product_type?.name || 'Geral';
        if (!groups[typeName]) groups[typeName] = [];
        groups[typeName].push(product);
    });
    return groups;
});

const showEditModal = ref(false);
const editingItem = ref(null);
const showPreviewModal = ref(false);
const previewItem = ref(null);
const parsedPreviewContent = ref('');
const docxContainer = ref(null);
const isLoadingPreview = ref(false);
const showDeleteConfirmModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    name: '',
    is_default: false,
    product_ids: [],
    file: null,
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.is_default = !!item.is_default;
    form.product_ids = item.products ? item.products.map(p => p.id) : [];
    form.file = null;
    showEditModal.value = true;
};

const processHtmlTags = (html) => {
    const mockData = {
        '${CLIENTE_NOME}': 'João Carlos da Silva',
        '${CLIENTE_NACIONALIDADE}': 'Brasileiro(a)',
        '${CLIENTE_ESTADO_CIVIL}': 'Casado(a)',
        '${CLIENTE_PROFISSAO}': 'Engenheiro',
        '${CLIENTE_CPF}': '123.456.789-00',
        '${CLIENTE_RG}': '12.345.678-9',
        '${CLIENTE_NASCIMENTO}': '15/04/1985',
        '${CLIENTE_ENDERECO}': 'Av. Paulista, 1000, Bela Vista, São Paulo, SP',
        '${CLIENTE_EMAIL}': 'joao@email.com',
        '${CLIENTE_TELEFONE}': '(11) 98765-4321',
        '${CLIENTE_CIDADE_UF}': 'São Paulo / SP',

        '${CONJUNGE_NOME}': 'Maria Oliveira da Silva',
        '${CONJUNGE_NACIONALIDADE}': 'Brasileira',
        '${CONJUNGE_ESTADO_CIVIL}': 'Casada',
        '${CONJUNGE_PROFISSAO}': 'Arquiteta',
        '${CONJUNGE_CPF}': '987.654.321-00',
        '${CONJUNGE_RG}': '98.765.432-1',
        '${CONJUNGE_NASCIMENTO}': '20/10/1988',

        '${CONTRATO_PLANO}': 'Premium Plus',
        '${CONTRATO_CATEGORIA}': 'Exclusive',
        '${CONTRATO_PACOTE}': '7 Noites',
        '${CONTRATO_NUMERO}': '2026/001',
        '${CONTRATO_PONTOS}': '150.000 Pontos',
        '${CONTRATO_VALOR_TOTAL}': 'R$ 45.000,00',
        '${CONTRATO_ENTRADA}': 'R$ 5.000,00',
        '${CONTRATO_DATA_ENTRADA}': '16/07/2026',
        '${CONTRATO_SALDO}': 'R$ 40.000,00',
        '${CONTRATO_DATA_SALDO}': '16/08/2026',
        '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}': 'PIX',
        '${CONTRATO_FORMA_PAGAMENTO_SALDO}': 'Boleto Bancário',
        '${CONTRATO_FORMA_PAGAMENTO}': 'Cartão de Crédito',
        '${CONTRATO_TAXA}': 'R$ 250,00',
        '${CONTRATO_TAXA_MANUTENCAO}': 'R$ 1.200,00',
        '${CONTRATO_DATA}': new Date().toLocaleDateString('pt-BR'),
        '${CONTRATO_DATA_EXTENSO}': '16 de Julho de 2026',
        '${CONTRATO_VIGENCIA}': '5 (cinco) anos',

        '${EMPRESA_EMAIL}': 'contato@itacare.com.br',
        '${EMPRESA_WHATSAPP}': '(73) 9999-8888',
    };
    for (const [tag, value] of Object.entries(mockData)) {
        const highlightedValue = `<span style="background-color: #fffac7; border-bottom: 2px solid #fce05d; color: #854d0e; font-weight: 700; padding: 0 2px; border-radius: 2px;" title="Tag: ${tag}">${value}</span>`;
        html = html.split(tag).join(highlightedValue);
    }
    return html;
};

const openPreview = async (item) => {
    previewItem.value = item;
    
    if (item.original_filename) {
        isLoadingPreview.value = true;
        parsedPreviewContent.value = '';
        showPreviewModal.value = true;
        
        try {
            // Usa a rota test_print para já trazer o Word com os dados substituídos, mostrando a formatação exata.
            const response = await axios.get(route('admin.contract_templates.test_print', item.id), {
                responseType: 'arraybuffer'
            });
            
            await nextTick();
            if (docxContainer.value) {
                docxContainer.value.innerHTML = ''; // Limpa antes de renderizar
                await docx.renderAsync(response.data, docxContainer.value, null, {
                    className: 'docx', // Default className
                    inWrapper: true,
                    ignoreWidth: true,
                    ignoreHeight: false,
                    ignoreFonts: false,
                    breakPages: true,
                    ignoreLastRenderedPageBreak: true,
                    experimental: true,
                    trimXmlDeclaration: true,
                    debug: false,
                });
            }
        } catch (error) {
            console.error(error);
            parsedPreviewContent.value = `<div class="text-center text-red-500 py-10">
                <p>Erro ao carregar o documento Word para visualização na tela.</p>
                <a href="${route('admin.contract_templates.test_print', item.id)}" class="underline mt-4 inline-block font-bold">Baixar Arquivo Original</a>
            </div>`;
        } finally {
            isLoadingPreview.value = false;
        }
    } else {
        parsedPreviewContent.value = processHtmlTags(item.content || '');
        showPreviewModal.value = true;
    }
};

const submit = () => {
    // Para envio de arquivo no método PUT, no Inertia usamos POST com _method
    if (editingItem.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('admin.contract_templates.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.contract_templates.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteConfirmModal.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        form.delete(route('admin.contract_templates.destroy', itemToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirmModal.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};

</script>

<template>
    <Head title="Modelos de Contrato" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Modelos de Contrato</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gestão de Textos Jurídicos
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <button 
                                @click="openCreateModal"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Modelo</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="item in templates" 
                        :key="item.id"
                        class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/60 dark:border-slate-800/60 rounded-2xl group hover:border-brand-green/50 dark:hover:border-brand-green/50 transition-all duration-500 overflow-hidden flex flex-col shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] dark:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.2)] hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.15)] dark:hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.25)] relative hover:-translate-y-1"
                    >
                        <!-- Glow Effect on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-brand-green/0 to-brand-green/5 dark:from-brand-green/0 dark:to-brand-green/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                        <!-- Ribbon for Default -->
                        <div v-if="item.is_default" class="absolute top-0 right-0 z-10 overflow-hidden w-24 h-24 pointer-events-none">
                            <div class="bg-gradient-to-r from-brand-green to-[#5c6e46] text-[9px] font-black uppercase tracking-[0.2em] text-white px-8 py-1.5 rotate-45 translate-x-[22px] translate-y-[16px] shadow-lg text-center">
                                Global
                            </div>
                        </div>

                        <div class="p-6 flex flex-col flex-1 relative z-20">
                            <!-- Header (Icon + Title) -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="relative w-12 h-12 flex-shrink-0 rounded-[14px] flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                                    <svg v-if="item.original_filename" class="w-6 h-6 text-blue-500 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3h6"/></svg>
                                    <svg v-else class="w-6 h-6 text-brand-green drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div class="flex flex-col pt-1">
                                    <span 
                                        class="text-[9px] font-black uppercase tracking-widest mb-1"
                                        :class="item.is_default ? 'text-brand-green' : 'text-slate-400'"
                                    >
                                        {{ item.is_default ? 'Padrão Global' : 'Específico' }}
                                    </span>
                                    <h3 class="text-slate-900 dark:text-white font-bold text-lg tracking-tight line-clamp-2 leading-tight">{{ item.name }}</h3>
                                </div>
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <!-- Source Indicator -->
                                <div v-if="item.original_filename || item.content" 
                                     class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50"
                                >
                                    <div class="w-6 h-6 rounded flex items-center justify-center shrink-0" :class="item.original_filename ? 'bg-blue-100/50 text-blue-600' : 'bg-brand-green/10 text-brand-green'">
                                        <svg v-if="item.original_filename" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ item.original_filename ? 'Arquivo Word' : 'Editor Interno' }}</span>
                                        <span class="text-[11px] font-semibold truncate text-slate-700 dark:text-slate-300">{{ item.original_filename || 'Texto HTML' }}</span>
                                    </div>
                                </div>

                                <!-- Products -->
                                <div v-if="!item.is_default && item.products?.length" class="space-y-2">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block">Vinculado a ({{ item.products.length }}) Produtos</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span 
                                            v-for="p in item.products.slice(0, 3)" 
                                            :key="p.id"
                                            class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[9px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-tight flex flex-col min-w-0"
                                        >
                                            <span class="truncate">{{ p.name }}</span>
                                            <span v-if="p.category || p.package" class="text-[7.5px] text-slate-400 mt-0.5 truncate">
                                                {{ [p.category, p.package].filter(Boolean).join(' / ') }}
                                            </span>
                                        </span>
                                        <span v-if="item.products.length > 3" class="px-2 py-1 flex items-center rounded bg-slate-50 dark:bg-slate-900 text-[9px] font-semibold text-slate-400 uppercase tracking-tight">
                                            +{{ item.products.length - 3 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-auto border-t border-slate-100 dark:border-slate-800/60 p-3 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-1 relative z-20">
                            <button @click="openPreview(item)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-brand-green hover:bg-brand-green/10 transition-colors" title="Visualizar HTML">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>

                            <a v-if="item.original_filename" :href="route('admin.contract_templates.test_print', item.id)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-purple-500 hover:bg-purple-50 dark:hover:bg-purple-500/10 transition-colors" title="Testar Impressão (Baixar Word com tags preenchidas)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                            
                            <a v-if="item.original_filename" :href="route('admin.contract_templates.download_pdf', item.id)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Gerar PDF (Exige LibreOffice no Servidor)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 13l2-2 2 2m-2-2v6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>

                            <button @click="openEditModal(item)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button @click="confirmDelete(item)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Remover">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add New Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="bg-transparent border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl h-full min-h-[280px] flex flex-col items-center justify-center p-8 group hover:border-brand-green hover:bg-brand-green/5 dark:hover:bg-brand-green/10 transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-center group-hover:bg-brand-green group-hover:border-brand-green transition-colors duration-300 mb-4 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-brand-green transition-colors">Cadastrar Novo Modelo</p>
                        <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase tracking-widest text-center">Inicie enviando um documento Word</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="7xl">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ editingItem ? 'Editar Contrato' : 'Novo Contrato' }}</h2>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6 overflow-y-auto flex-1 custom-scrollbar bg-slate-50 dark:bg-transparent">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-3 space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1 text-left block">Título do Contrato</label>
                            <input v-model="form.name" type="text" required class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm" placeholder="Ex: Contrato Padrão de Time-Sharing">
                        </div>
                        <div class="flex items-end">
                            <div class="w-full flex items-center gap-3 bg-white dark:bg-slate-800/50 p-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700 h-[42px] shadow-sm">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_default" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-green"></div>
                                    <span class="ml-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Global Padrão</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Product Selection (Only if NOT default) -->
                    <div v-if="!form.is_default" class="space-y-3 bg-white dark:bg-slate-800/50 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Produtos Associados (Agrupados por Modalidade)</label>
                            <span class="text-[10px] text-brand-green font-bold uppercase tracking-widest">{{ form.product_ids.length }} Selecionado(s)</span>
                        </div>
                        <div class="space-y-6 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="(typeProducts, typeName) in groupedProducts" :key="typeName" class="space-y-2">
                                <div class="flex items-center gap-2 px-1">
                                    <div class="h-[1px] flex-1 bg-slate-200 dark:bg-slate-700"></div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ typeName }}</span>
                                    <div class="h-[1px] flex-1 bg-slate-200 dark:bg-slate-700"></div>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <label 
                                        v-for="product in typeProducts" 
                                        :key="product.id"
                                        class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all active:scale-95 group"
                                        :class="form.product_ids.includes(product.id) ? 'bg-brand-green/10 border-brand-green/30' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-brand-green/30'"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="product.id" 
                                            v-model="form.product_ids"
                                            class="sr-only"
                                        >
                                        <div 
                                            class="w-4 h-4 rounded border flex items-center justify-center transition-all bg-white dark:bg-slate-900 shrink-0"
                                            :class="form.product_ids.includes(product.id) ? 'bg-brand-green border-brand-green' : 'border-slate-300 dark:border-slate-600'"
                                        >
                                            <svg v-if="form.product_ids.includes(product.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </div>
                                        <div class="flex flex-col min-w-0 flex-1">
                                            <span class="text-[9px] font-bold uppercase tracking-tight truncate w-full" :class="form.product_ids.includes(product.id) ? 'text-slate-900 dark:text-white' : 'text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300'">
                                                {{ product.name }}
                                            </span>
                                            <span v-if="product.category || product.package" class="text-[8px] text-slate-400 uppercase tracking-widest truncate w-full mt-0.5">
                                                {{ [product.category, product.package].filter(Boolean).join(' / ') }}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Word Upload -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Arquivo do Contrato (Word .docx)</label>
                            <span class="text-[9px] text-brand-green uppercase tracking-widest font-bold">Mantém formatação original</span>
                        </div>
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center bg-white dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer relative">
                            <input 
                                type="file" 
                                accept=".docx" 
                                @change="e => form.file = e.target.files[0]" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            >
                            <div class="flex flex-col items-center justify-center gap-2">
                                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 text-blue-500 rounded-full flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                </div>
                                <p v-if="form.file" class="text-sm font-bold text-slate-900 dark:text-white">{{ form.file.name }}</p>
                                <p v-else class="text-sm font-medium text-slate-500 dark:text-slate-400">Clique ou arraste um arquivo Word (.docx)</p>
                                <p class="text-xs text-slate-400 mt-1" v-if="editingItem?.original_filename && !form.file">Arquivo atual: {{ editingItem.original_filename }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Magic Tags Cheatsheet -->
                    <div class="bg-brand-green/5 border border-brand-green/10 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="font-bold text-slate-800 dark:text-slate-200">Tags Mágicas Disponíveis</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Substitua as informações do seu Word pelas tags abaixo. O sistema as preencherá automaticamente. <br>Exemplo: troque <span class="font-mono bg-slate-100 dark:bg-slate-800 rounded px-1">[Nome completo]</span> por <span class="font-mono text-brand-green bg-brand-green/10 rounded px-1 font-bold">${CLIENTE_NOME}</span>.</p>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-6 mt-4">
                            <!-- Titular -->
                            <div>
                                <h4 class="text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-3 border-b-2 border-brand-green/30 pb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Titular
                                </h4>
                                <ul class="text-[10px] text-slate-600 dark:text-slate-400 font-mono flex flex-col">
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_NOME}</span> <span class="font-sans text-slate-400 truncate pl-2">João Carlos da Silva</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_CPF}</span> <span class="font-sans text-slate-400 truncate pl-2">123.456.789-00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_RG}</span> <span class="font-sans text-slate-400 truncate pl-2">12.345.678-9</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_NASCIMENTO}</span> <span class="font-sans text-slate-400 truncate pl-2">15/04/1985</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_ESTADO_CIVIL}</span> <span class="font-sans text-slate-400 truncate pl-2">Casado(a)</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_PROFISSAO}</span> <span class="font-sans text-slate-400 truncate pl-2">Engenheiro</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_NACIONALIDADE}</span> <span class="font-sans text-slate-400 truncate pl-2">Brasileiro(a)</span></li>
                                    <li class="flex flex-col p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_ENDERECO}</span> <span class="font-sans text-slate-400 text-[9px] mt-1 leading-tight">Av. Paulista, 1000, Apto 2, Centro, SP, 01310-100</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_CIDADE_UF}</span> <span class="font-sans text-slate-400 truncate pl-2">São Paulo / SP</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_TELEFONE}</span> <span class="font-sans text-slate-400 truncate pl-2">(11) 98765-4321</span></li>
                                    <li class="flex items-center justify-between p-2 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CLIENTE_EMAIL}</span> <span class="font-sans text-slate-400 truncate pl-2">joao@email.com</span></li>
                                </ul>
                            </div>
                            
                            <!-- Cônjuge -->
                            <div>
                                <h4 class="text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-3 border-b-2 border-brand-green/30 pb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    Cônjuge
                                </h4>
                                <ul class="text-[10px] text-slate-600 dark:text-slate-400 font-mono flex flex-col">
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_NOME}</span> <span class="font-sans text-slate-400 truncate pl-2">Maria O. da Silva</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_CPF}</span> <span class="font-sans text-slate-400 truncate pl-2">987.654.321-00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_RG}</span> <span class="font-sans text-slate-400 truncate pl-2">98.765.432-1</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_NASCIMENTO}</span> <span class="font-sans text-slate-400 truncate pl-2">20/10/1988</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_ESTADO_CIVIL}</span> <span class="font-sans text-slate-400 truncate pl-2">Casada</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_PROFISSAO}</span> <span class="font-sans text-slate-400 truncate pl-2">Arquiteta</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONJUNGE_NACIONALIDADE}</span> <span class="font-sans text-slate-400 truncate pl-2">Brasileira</span></li>
                                </ul>
                            </div>

                            <!-- Contrato -->
                            <div>
                                <h4 class="text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-3 border-b-2 border-brand-green/30 pb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Dados do Contrato
                                </h4>
                                <ul class="text-[10px] text-slate-600 dark:text-slate-400 font-mono flex flex-col">
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_NUMERO}</span> <span class="font-sans text-slate-400 truncate pl-2">2026/001</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_PLANO}</span> <span class="font-sans text-slate-400 truncate pl-2">Premium Plus</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_CATEGORIA}</span> <span class="font-sans text-slate-400 truncate pl-2">Exclusive</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_PACOTE}</span> <span class="font-sans text-slate-400 truncate pl-2">7 Noites</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_PONTOS}</span> <span class="font-sans text-slate-400 truncate pl-2">150.000 Pontos</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_VALOR_TOTAL}</span> <span class="font-sans text-slate-400 truncate pl-2">R$ 45.000,00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_ENTRADA}</span> <span class="font-sans text-slate-400 truncate pl-2">R$ 5.000,00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_DATA_ENTRADA}</span> <span class="font-sans text-slate-400 truncate pl-2">16/07/2026</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_RESUMO_ENTRADA}</span> <span class="font-sans text-slate-400 truncate pl-2">5x Boleto + 5x PIX</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_SALDO}</span> <span class="font-sans text-slate-400 truncate pl-2">R$ 40.000,00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_DATA_SALDO}</span> <span class="font-sans text-slate-400 truncate pl-2">16/08/2026</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_RESUMO_SALDO}</span> <span class="font-sans text-slate-400 truncate pl-2">20x Boleto + 15x PIX</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_FORMA_PAGAMENTO}</span> <span class="font-sans text-slate-400 truncate pl-2">Cartão</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_FORMA_PAGAMENTO_ENTRADA}</span> <span class="font-sans text-slate-400 truncate pl-2">PIX</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_FORMA_PAGAMENTO_SALDO}</span> <span class="font-sans text-slate-400 truncate pl-2">Boleto</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_TAXA}</span> <span class="font-sans text-slate-400 truncate pl-2">R$ 250,00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_RESUMO_TAXA}</span> <span class="font-sans text-slate-400 truncate pl-2">1x PIX + 1x Dinheiro</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_TAXA_MANUTENCAO}</span> <span class="font-sans text-slate-400 truncate pl-2">R$ 1.200,00</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_RESUMO_MANUTENCAO}</span> <span class="font-sans text-slate-400 truncate pl-2">Anual Boleto + Anual Cartão</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_VIGENCIA}</span> <span class="font-sans text-slate-400 truncate pl-2">5 (cinco) anos</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_DATA}</span> <span class="font-sans text-slate-400 truncate pl-2">16/07/2026</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${CONTRATO_DATA_EXTENSO}</span> <span class="font-sans text-slate-400 truncate pl-2">16 de Julho</span></li>
                                    <li class="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-800 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${EMPRESA_EMAIL}</span> <span class="font-sans text-slate-400 truncate pl-2">contato@itacare</span></li>
                                    <li class="flex items-center justify-between p-2 hover:bg-brand-green/5 transition-colors"><span class="text-brand-green font-bold">${EMPRESA_WHATSAPP}</span> <span class="font-sans text-slate-400 truncate pl-2">(73) 9999-8888</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeModal" class="flex-1 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-[12px] font-semibold text-sm transition-all shadow-sm">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3 bg-brand-green hover:bg-[#485638] text-white rounded-[12px] font-semibold text-sm transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ form.processing ? 'Salvando...' : 'Salvar Modelo' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Preview Modal -->
        <Modal :show="showPreviewModal" @close="showPreviewModal = false" maxWidth="7xl">
            <div v-if="previewItem" class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                {{ previewItem.name }}
                                <span v-if="previewItem.original_filename" class="px-2 py-0.5 rounded text-[10px] bg-blue-100 text-blue-600 font-bold uppercase tracking-wider border border-blue-200">Word</span>
                            </h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Pré-visualização Jurídica</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <a v-if="previewItem?.original_filename" :href="route('admin.contract_templates.download', previewItem.id)" class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-slate-100 px-4 py-2 rounded-xl hover:bg-slate-200 transition-colors border border-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Baixar Word
                        </a>
                        <button @click="showPreviewModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
                <!-- Browser Shell -->
                <div class="overflow-y-auto flex-1 preview-content bg-slate-200/80 dark:bg-slate-900 text-slate-900 min-h-[500px] relative">
                    <div v-if="isLoadingPreview" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm z-10">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-brand-green mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm font-bold text-slate-600 animate-pulse">Renderizando Formatação Original do Word...</p>
                    </div>
                    <div v-show="!previewItem?.original_filename" v-html="parsedPreviewContent" class="p-12"></div>
                    <div v-show="previewItem?.original_filename" ref="docxContainer" class="w-full px-[200px] py-8 docx-preview-wrapper"></div>
                </div>
            </div>
        </Modal>
        <!-- Delete Confirm Modal -->
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6 bg-white dark:bg-[#0f1219]">
                <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mx-auto mb-5 border-4 border-red-100 dark:border-red-500/20">
                    <svg class="w-8 h-8 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                
                <h3 class="text-xl font-black text-slate-900 dark:text-white text-center mb-2 tracking-tight">
                    Excluir Modelo?
                </h3>
                
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-8 px-4 leading-relaxed">
                    Você está prestes a excluir o modelo <br>
                    <strong class="text-slate-700 dark:text-slate-300">"{{ itemToDelete?.name }}"</strong>. <br>
                    <span class="text-red-500 font-bold block mt-3">Esta ação não pode ser desfeita.</span> Todos os produtos vinculados perderão este modelo.
                </p>

                <div class="flex gap-3 w-full">
                    <button @click="showDeleteConfirmModal = false" class="flex-1 px-4 py-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all shadow-sm">
                        Cancelar
                    </button>
                    <button @click="executeDelete" :disabled="form.processing" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0">
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ form.processing ? 'Excluindo...' : 'Sim, excluir' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.preview-content :deep(h1), .preview-content :deep(h2) {
    font-size: 1.5rem;
    font-weight: 900;
    color: #111827;
    margin-bottom: 1rem;
    text-align: center;
    text-transform: uppercase;
}
.preview-content :deep(p) {
    color: #374151;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    text-align: justify;
}
.preview-content :deep(strong) {
    color: #111827;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
</style>

<style>
/* Diminui a página toda em 10% */
.docx-preview-wrapper {
    zoom: 0.9;
}
/* Global CSS para forçar o tamanho da tabela gerada dinamicamente pelo docx-preview */
.docx-preview-wrapper table {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 100% !important;
    table-layout: auto !important;
}
/* Remove metadados de colunas que travam a largura */
.docx-preview-wrapper table colgroup {
    display: none !important;
}
.docx-preview-wrapper table td,
.docx-preview-wrapper table th {
    width: auto !important;
    min-width: 0 !important;
    max-width: none !important;
    word-break: break-word;
}
</style>
