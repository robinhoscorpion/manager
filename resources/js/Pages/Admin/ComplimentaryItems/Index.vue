<script setup>
import { ref, computed } from 'vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import HtmlCodeEditor from '@/Components/HtmlCodeEditor.vue';
import ImageTemplateEditor from '@/Components/ImageTemplateEditor.vue';

const props = defineProps({
    items: Array,
});

const showEditModal = ref(false);
const editingItem = ref(null);
const showPreviewModal = ref(false);
const previewItem = ref(null);
const showDeleteConfirmModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    name: '',
    code: '',
    type: 'atendimento',
    description: '',
    content: '',
    template_type: 'html',
    file: null,
    metadata: {},
    is_active: true,
});

const imagePreviewUrl = ref('');

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
    }
};

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    imagePreviewUrl.value = '';
    showEditModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.code = item.code;
    form.type = item.type || 'atendimento';
    form.description = item.description || '';
    form.content = item.content || '';
    form.template_type = item.template_type || 'html';
    form.file = null; // Do not load the old file into the file input
    form.metadata = item.metadata || {};
    form.is_active = !!item.is_active;
    imagePreviewUrl.value = '';
    showEditModal.value = true;
};

const openPreview = (item) => {
    previewItem.value = item;
    showPreviewModal.value = true;
};

const mockData = {
    '[NOME_TITULAR]': 'João Carlos da Silva',
    '[DATA_NASCIMENTO]': '15/05/1985',
    '[CPF]': '123.456.789-00',
    '[EMAIL]': 'joao.silva@exemplo.com',
    '[CELULAR]': '(11) 98765-4321',
    '[NOME_CONJUGE]': 'Maria Oliveira da Silva',
    '[ID_ATENDIMENTO]': '04129',
    '[DATA]': new Date().toLocaleDateString('pt-BR'),
    '[LOCAL]': 'Resort Principal (Mesa 05)',
    '[CEP]': '01001-000',
    '[RUA]': 'Praça da Sé Principal',
    '[NUMERO]': '123',
    '[QR_CODE]': '<img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=Preview" class="qr-code-img" alt="QR Code" >',
    '[PROMOTOR]': 'João Promotor',
    '[CONSULTOR]': 'Maria Consultora',
    '[SUPERVISOR]': 'Pedro Supervisor'
};

const parsedPreviewContent = computed(() => {
    if (!previewItem.value) return '';
    let html = previewItem.value.content || '';
    
    if (previewItem.value.template_type !== 'html' && previewItem.value.template_type) {
        return ''; // Handled differently in template
    }
    
    for (const [tag, value] of Object.entries(mockData)) {
        // Wrap mock data in subtle yellow highlight for visual distinction
        const highlightedValue = `<span style="background-color: #fffac7; border-bottom: 2px solid #fce05d; color: #854d0e; font-weight: 700; padding: 0 2px; border-radius: 2px;" title="Tag: ${tag}">${value}</span>`;
        html = html.split(tag).join(highlightedValue);
    }

    return html;
});

const submit = () => {
    if (editingItem.value) {
        form.put(route('admin.complimentary_items.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.complimentary_items.store'), {
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
        form.delete(route('admin.complimentary_items.destroy', itemToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirmModal.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const closeModal = () => {
    showEditModal.value = false;
    imagePreviewUrl.value = '';
    form.reset();
};

</script>

<template>
    <Head title="Gestão de Cortesias" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Gestão de Cortesias</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Cadastro de Itens Cortesia para Propostas
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button v-if="can('configuracoes.cortesias.gerenciar')" 
                                @click="openCreateModal"
                                class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Nova Cortesia
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-12">
                    <div 
                        v-for="item in items" 
                        :key="item.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] group hover:border-brand-green/30 dark:hover:border-brand-green/40 transition-all overflow-hidden flex flex-col shadow-sm hover:shadow-md relative"
                    >
                        <div class="p-6 space-y-4 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-12 flex-shrink-0 rounded-[14px] flex items-center justify-center text-xs font-bold text-white shadow-sm bg-gradient-to-br from-brand-green to-[#485638] uppercase">
                                            {{ item.code }}
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span v-if="item.type === 'atendimento'" class="px-2 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-widest bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 w-fit">Atendimento</span>
                                            <span v-else-if="item.type === 'contrato'" class="px-2 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-widest bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20 w-fit">Contrato</span>
                                            <span class="text-[9px] font-bold text-slate-500 line-clamp-1 uppercase tracking-widest">Cód Interno</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <a v-if="item.content || item.file_path" :href="route('admin.complimentary_items.pdf', item.id)" target="_blank" class="p-1.5 bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors" title="Visualizar em PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3v5a2 2 0 002 2h5" /></svg>
                                    </a>
                                    <button v-if="item.content || item.template_type === 'image' || item.template_type === 'docx'" @click="openPreview(item)" class="p-1.5 bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-lg text-slate-500 hover:text-brand-green transition-colors" title="Visualizar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="openEditModal(item)" class="p-1.5 bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-lg text-slate-500 hover:text-brand-green transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="confirmDelete(item)" class="p-1.5 bg-slate-50 dark:bg-slate-800/50 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg text-slate-500 hover:text-red-500 transition-colors" title="Remover">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-slate-900 dark:text-white font-bold text-lg uppercase tracking-tight group-hover:text-brand-green transition-colors">{{ item.name }}</h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1 line-clamp-2 min-h-[2.5em]">{{ item.description || 'Sem descrição' }}</p>
                            </div>

                            <!-- Content preview indicator -->
                            <div v-if="item.content" class="flex items-center gap-2 px-3 py-2 bg-brand-green/5 border border-brand-green/10 rounded-[10px]">
                                <svg class="w-3.5 h-3.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-[9px] font-bold text-brand-green uppercase tracking-widest">Modelo criado</span>
                            </div>
                        </div>

                        <div class="px-6 pb-6">
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Status</span>
                                    <div 
                                        class="px-2.5 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border"
                                        :class="item.is_active ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400'"
                                    >
                                        {{ item.is_active ? 'Ativo' : 'Inativo' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button v-if="can('configuracoes.cortesias.gerenciar')"
                        @click="openCreateModal"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[20px] h-full min-h-[280px] flex flex-col items-center justify-center p-8 group hover:border-brand-green/30 transition-all bg-slate-50/50 dark:bg-slate-900/40 hover:bg-brand-green/5 dark:hover:bg-brand-green/5"
                    >
                        <div class="w-12 h-12 rounded-[14px] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center group-hover:bg-brand-green/10 group-hover:border-brand-green/20 transition-colors mb-3">
                            <svg class="w-6 h-6 text-slate-400 group-hover:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest group-hover:text-brand-green transition-colors">Nova Cortesia</p>
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
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                                {{ editingItem ? 'Editar Cortesia' : 'Nova Cortesia' }}
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Gestão de Itens</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50 dark:bg-transparent">
                    <!-- Error Banner -->
                    <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                        <h4 class="text-xs font-bold text-red-800 dark:text-red-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Por favor, corrija os erros abaixo:
                        </h4>
                        <ul class="list-disc pl-5 space-y-1">
                            <li v-for="(error, field) in form.errors" :key="field" class="text-xs text-red-600 dark:text-red-300">
                                <strong>{{ field }}:</strong> {{ error }}
                            </li>
                        </ul>
                    </div>

                    <!-- Basic Info -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Nome da Cortesia</label>
                            <input v-model="form.name" type="text" required class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all" placeholder="Ex: Isenção de Taxa de Adesão">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1 text-center block">Sigla/Cód</label>
                            <input v-model="form.code" type="text" required maxlength="10" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-3 text-slate-900 dark:text-white text-sm text-center font-bold outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all uppercase" placeholder="TAXA">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Tipo de Aplicação</label>
                            <select v-model="form.type" required class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                                <option value="atendimento" class="bg-white dark:bg-[#1a1f2e]">Cortesia de Atendimento</option>
                                <option value="contrato" class="bg-white dark:bg-[#1a1f2e]">Cortesia de Contrato</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Formato do Modelo</label>
                            <select v-model="form.template_type" required class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all">
                                <option value="html" class="bg-white dark:bg-[#1a1f2e]">Editor de Texto (HTML)</option>
                                <option value="image" class="bg-white dark:bg-[#1a1f2e]">Imagem com Textos (Visual)</option>
                                <option value="docx" class="bg-white dark:bg-[#1a1f2e]">Documento Word (DOCX)</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Descrição Resumida</label>
                            <input v-model="form.description" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 shadow-sm transition-all" placeholder="Descrição curta para exibição em cards">
                        </div>
                        <div class="flex items-end">
                            <div class="w-full flex items-center gap-3 bg-white dark:bg-slate-800/50 p-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm h-[46px]">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                                    <span class="ml-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ativo</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Template Specifics -->
                    
                    <!-- 1. HTML Type -->
                    <div v-if="form.template_type === 'html'" class="space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">Modelo da Cortesia</label>
                            <span class="text-[9px] text-slate-500 uppercase tracking-widest">Editor Visual HTML</span>
                        </div>
                        <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                            <HtmlCodeEditor 
                                v-model="form.content" 
                                :height="500" 
                            />
                        </div>
                    </div>

                    <!-- 2. DOCX Type -->
                    <div v-else-if="form.template_type === 'docx'" class="space-y-4">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-300 font-bold mb-2 uppercase tracking-widest">Instruções para DOCX:</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400">1. Crie seu documento no Microsoft Word.</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400">2. Escreva as variáveis mágicas (ex: [NOME_TITULAR]) no texto onde deseja que os dados apareçam.</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400">3. Salve o arquivo como .docx e faça o upload abaixo.</p>
                        </div>
                        
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Arquivo Base (.docx)</label>
                            <input 
                                type="file" 
                                accept=".docx"
                                @change="e => form.file = e.target.files[0]"
                                class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm"
                            >
                            <p v-if="editingItem && editingItem.file_path && !form.file" class="text-[10px] text-slate-500 font-bold px-2 mt-1">Arquivo atual salvo: {{ editingItem.file_path.split('/').pop() }} (Faça upload apenas se quiser substituir)</p>
                        </div>
                    </div>

                    <!-- 3. Image Type -->
                    <div v-else-if="form.template_type === 'image'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1">Imagem Base do Voucher (JPEG/PNG)</label>
                            <input 
                                type="file" 
                                accept="image/jpeg,image/png,image/webp"
                                @change="handleImageUpload"
                                class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm"
                            >
                            <p v-if="editingItem && editingItem.file_path && !imagePreviewUrl" class="text-[10px] text-slate-500 font-bold px-2 mt-1">Imagem salva no servidor carregada.</p>
                        </div>

                        <div v-if="imagePreviewUrl || (editingItem && editingItem.file_path)">
                            <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest px-1 mb-2 block">Editor Visual (Arraste as Tags)</label>
                            <ImageTemplateEditor 
                                v-model="form.metadata" 
                                :imageUrl="imagePreviewUrl || ('/storage/' + editingItem.file_path)"
                            />
                        </div>
                    </div>

                    <!-- Magic Tags Cheatsheet (Only for HTML and DOCX) -->
                    <div v-if="form.template_type !== 'image'" class="bg-white dark:bg-[#1a1f2e]/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <h4 class="text-[10px] font-bold uppercase text-slate-900 dark:text-white tracking-widest">Variáveis Dinâmicas (Mágicas)</h4>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4">Copie as tags abaixo e cole no seu HTML. Elas serão preenchidas automaticamente pelo formato de Atendimento na hora de gerar o PDF do cliente.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-slate-700 dark:text-brand-green border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[NOME_TITULAR]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-slate-700 dark:text-brand-green border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[DATA_NASCIMENTO]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-slate-700 dark:text-brand-green border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[CPF]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-slate-700 dark:text-brand-green border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[EMAIL]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-slate-700 dark:text-brand-green border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[CELULAR]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-pink-600 dark:text-pink-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[NOME_CONJUGE]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-orange-600 dark:text-orange-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[ID_ATENDIMENTO]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-orange-600 dark:text-orange-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[DATA]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-orange-600 dark:text-orange-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[LOCAL]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-blue-600 dark:text-blue-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[CEP]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-blue-600 dark:text-blue-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[RUA]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-blue-600 dark:text-blue-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[NUMERO]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[PROMOTOR]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[CONSULTOR]</code>
                            <code class="px-2 py-1.5 bg-slate-50 dark:bg-[#0d1117] text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-slate-700 rounded-[10px] text-[10px] font-bold select-all text-center">[SUPERVISOR]</code>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6 border-t border-slate-200 dark:border-slate-800 mt-6">
                        <button type="button" @click="closeModal" class="flex-1 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold uppercase text-xs tracking-wider rounded-[12px] transition-all shadow-sm">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-brand-green hover:bg-[#485638] text-white font-bold uppercase text-xs tracking-wider rounded-[12px] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
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
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">{{ previewItem.name }}</h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Pré-visualização do Modelo</p>
                        </div>
                    </div>
                    <button @click="showPreviewModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <!-- Browser Shell -->
                <div class="p-8 overflow-y-auto flex-1 preview-content bg-white dark:bg-slate-50">
                    <!-- HTML Preview -->
                    <div v-if="previewItem.template_type === 'html' || !previewItem.template_type" v-html="parsedPreviewContent"></div>

                    <!-- Image Preview -->
                    <div v-else-if="previewItem.template_type === 'image'" class="flex justify-center bg-slate-100 dark:bg-slate-900 p-8 rounded-xl">
                        <div class="relative inline-block border border-slate-300 dark:border-slate-600 shadow-xl overflow-hidden rounded-md" style="max-width: 100%;">
                            <img :src="'/storage/' + previewItem.file_path" class="max-w-full h-auto block select-none pointer-events-none" />
                            <div 
                                v-for="(el, index) in (previewItem.metadata?.elements || [])" 
                                :key="index"
                                class="absolute px-1 whitespace-nowrap"
                                :style="{ 
                                    left: el.x + '%', 
                                    top: el.y + '%',
                                    fontSize: el.fontSize + 'px',
                                    color: el.color,
                                    fontWeight: el.bold ? 'bold' : 'normal',
                                    textAlign: el.align || 'left',
                                    lineHeight: 1.2
                                }"
                            >
                                <template v-if="el.isStatic">
                                    <span class="px-1 rounded-sm block" style="white-space: pre-wrap;">{{ el.content }}</span>
                                </template>
                                <template v-else-if="el.tag === '[QR_CODE]'">
                                    <div class="bg-white p-1 rounded inline-block shadow-sm">
                                        <div class="w-20 h-20 bg-slate-200 flex items-center justify-center text-[10px] text-slate-500 font-bold uppercase">QR Code</div>
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="bg-yellow-200/50 outline outline-1 outline-yellow-400 text-yellow-900 px-1 rounded-sm">{{ mockData[el.tag] || el.tag }}</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- DOCX Preview -->
                    <div v-else-if="previewItem.template_type === 'docx'" class="text-center py-12">
                        <svg class="w-16 h-16 text-blue-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Modelo Microsoft Word (DOCX)</h3>
                        <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">Este modelo é baseado em um arquivo DOCX. O sistema não pode renderizar visualmente arquivos Word no navegador. O preenchimento ocorrerá automaticamente no momento da geração.</p>
                        <a :href="route('admin.complimentary_items.pdf', previewItem.id)" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-lg text-sm transition-colors shadow-sm" target="_blank">
                            Testar Geração (Baixar)
                        </a>
                    </div>
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
                    Excluir Cortesia?
                </h3>
                
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-8 px-4 leading-relaxed">
                    Você está prestes a excluir a cortesia <br>
                    <strong class="text-slate-700 dark:text-slate-300">"{{ itemToDelete?.name }}"</strong>. <br>
                    <span class="text-red-500 font-bold block mt-3">Esta ação não pode ser desfeita.</span>
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
.preview-content :deep(h1) {
    font-size: 2rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: -0.05em;
}
.preview-content :deep(h2) {
    font-size: 1.5rem;
    font-weight: 800;
    color: #f1f5f9;
    margin-bottom: 0.5rem;
}
.preview-content :deep(h3) {
    font-size: 1.15rem;
    font-weight: 700;
    color: #cbd5e1;
    margin-bottom: 0.5rem;
}
.preview-content :deep(p) {
    color: #e5e7eb;
    font-size: 0.875rem;
    line-height: 1.75;
    margin-bottom: 0.75rem;
}
.preview-content :deep(ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
    color: #e5e7eb;
}
.preview-content :deep(ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
    color: #e5e7eb;
}
.preview-content :deep(li) {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}
.preview-content :deep(blockquote) {
    border-left: 4px solid #0891b2;
    padding-left: 1rem;
    color: #94a3b8;
    font-style: italic;
    margin-bottom: 0.75rem;
}
.preview-content :deep(img) {
    max-width: 100%;
    border-radius: 0.75rem;
    margin: 1rem 0;
    border: 1px solid rgba(255,255,255,0.05);
}
.preview-content :deep(hr) {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin: 1.5rem 0;
}
.preview-content :deep(strong) {
    font-weight: 700;
    color: #fff;
}
.preview-content :deep(em) {
    font-style: italic;
}
.preview-content :deep(u) {
    text-decoration: underline;
}
.preview-content :deep(s) {
    text-decoration: line-through;
}
</style>
