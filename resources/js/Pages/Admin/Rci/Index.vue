<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

const props = defineProps({
    templates: Array
});

// --- MODAL DE UPLOAD ---
const isUploadModalOpen = ref(false);
const uploadForm = useForm({
    name: '',
    file: null,
});

const openUploadModal = () => {
    uploadForm.reset();
    isUploadModalOpen.value = true;
};

const closeUploadModal = () => {
    isUploadModalOpen.value = false;
    uploadForm.reset();
};

const submitUpload = () => {
    uploadForm.post(route('admin.rci.store'), {
        preserveScroll: true,
        onSuccess: () => closeUploadModal(),
    });
};

const handleFileChange = (e) => {
    uploadForm.file = e.target.files[0];
};

// --- MAPEADOR NATIVO (ACROFORM) ---
const isMapperOpen = ref(false);
const activeTemplate = ref(null);
const mappingConfig = ref([]);
const isSavingMapping = ref(false);
const isLoadingFields = ref(false);
const nativeFields = ref([]);
const isPreviewing = ref(false);

const systemVariables = [
    { value: 'cliente_nome', label: 'Nome do Cliente Completo' },
    { value: 'cliente_primeiro_nome', label: 'Primeiro Nome do Cliente' },
    { value: 'cliente_sobrenome', label: 'Sobrenome do Cliente' },
    { value: 'cliente_cpf_cnpj', label: 'CPF/CNPJ' },
    { value: 'cliente_rg', label: 'RG' },
    { value: 'cliente_data_nascimento', label: 'Data de Nascimento' },
    { value: 'cliente_nacionalidade', label: 'Nacionalidade' },
    { value: 'cliente_endereco', label: 'Endereço Completo' },
    { value: 'cliente_cidade', label: 'Cidade' },
    { value: 'cliente_estado', label: 'Estado (UF)' },
    { value: 'cliente_cep', label: 'CEP' },
    { value: 'cliente_telefone', label: 'Telefone do Cliente' },
    
    // Dados do 2º Titular (Cônjuge/Acompanhante)
    { value: 'conjuge_nome', label: 'Nome do 2º Titular Completo' },
    { value: 'conjuge_primeiro_nome', label: 'Primeiro Nome do 2º Titular' },
    { value: 'conjuge_sobrenome', label: 'Sobrenome do 2º Titular' },
    { value: 'conjuge_cpf', label: 'CPF do 2º Titular' },
    { value: 'conjuge_rg', label: 'RG do 2º Titular' },
    { value: 'conjuge_data_nascimento', label: 'Data de Nascimento do 2º Titular' },
    { value: 'conjuge_nacionalidade', label: 'Nacionalidade do 2º Titular' },
    { value: 'contrato_numero', label: 'Número do Contrato' },
    { value: 'data_assinatura', label: 'Data Atual (Assinatura)' },
    { value: 'venda_valor_total', label: 'Valor Total da Venda' },
    { value: 'servico_nome', label: 'Nome do Serviço Principal' },
    { value: 'forma_pagamento', label: 'Forma de Pagamento' },
    { value: 'resort_nome', label: 'Nome do Empreendimento/Resort' },
    { value: 'resort_id', label: 'Resort ID' },
];

import { PDFDocument, StandardFonts } from 'pdf-lib';

const openMapper = async (template) => {
    activeTemplate.value = template;
    isMapperOpen.value = true;
    isLoadingFields.value = true;
    nativeFields.value = [];
    
    const existingConfig = template.mapping_config ? JSON.parse(JSON.stringify(template.mapping_config)) : [];
    
    try {
        const response = await axios.get(route('admin.rci.file', template.id), {
            responseType: 'arraybuffer',
            withCredentials: true
        });
        
        const pdfBytes = response.data;
        const pdfDoc = await PDFDocument.load(pdfBytes);
        const form = pdfDoc.getForm();
        const fields = form.getFields();
        
        const extractedFields = fields.map(f => {
            return {
                name: f.getName(),
                type: f.constructor.name.replace('PDF', '')
            };
        });
        
        nativeFields.value = extractedFields;
        
        mappingConfig.value = nativeFields.value.map(field => {
            const saved = existingConfig.find(c => c.pdf_field === field.name);
            let system_var = saved ? saved.system_var : '';
            let custom_text = '';
            
            if (system_var && system_var.startsWith('CUSTOM:')) {
                custom_text = system_var.substring(7);
                system_var = 'CUSTOM_TEXT';
            }
            
            return {
                pdf_field: field.name,
                system_var: system_var,
                custom_text: custom_text
            };
        });
        
    } catch (error) {
        console.error("Erro ao carregar campos do PDF:", error);
        alert("Não foi possível extrair os campos deste PDF. Certifique-se que o PDF possui campos preenchíveis (AcroForm).");
    } finally {
        isLoadingFields.value = false;
    }
};

const closeMapper = () => {
    isMapperOpen.value = false;
    activeTemplate.value = null;
};

const saveMapping = () => {
    if (!activeTemplate.value) return;
    isSavingMapping.value = true;
    
    // Processa os custom texts antes de enviar
    const processedConfig = mappingConfig.value.map(c => {
        return {
            pdf_field: c.pdf_field,
            system_var: c.system_var === 'CUSTOM_TEXT' ? `CUSTOM:${c.custom_text || ''}` : c.system_var
        };
    });

    router.post(route('admin.rci.update_mapping', activeTemplate.value.id), {
        mapping_config: processedConfig
    }, {
        onSuccess: () => {
            alert('Mapeamento salvo com sucesso!');
            const idx = props.templates.findIndex(t => t.id === activeTemplate.value.id);
            if (idx !== -1) {
                props.templates[idx].mapping_config = processedConfig;
            }
            closeMapper();
        },
        onError: (error) => {
            console.error(error);
            alert('Erro ao salvar mapeamento.');
        },
        onFinish: () => {
            isSavingMapping.value = false;
        }
    });
};

const deleteTemplate = (id) => {
    if (confirm('Tem certeza que deseja excluir este modelo RCI?')) {
        router.delete(route('admin.rci.destroy', id), {
            preserveScroll: true
        });
    }
};



const previewFakeData = async () => {
    if (!activeTemplate.value) return;
    
    const hasMapping = mappingConfig.value.some(m => m.system_var);
    if (!hasMapping) {
        alert("Por favor, vincule pelo menos uma variável do sistema a um campo antes de testar.");
        return;
    }
    
    isPreviewing.value = true;
    
    // Abre a janela imediatamente para contornar bloqueadores de pop-up
    const newWindow = window.open('', '_blank');
    if (newWindow) {
        newWindow.document.write(`
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <title>Gerando PDF...</title>
                <style>
                    body { 
                        margin: 0; padding: 0; display: flex; flex-direction: column; 
                        align-items: center; justify-content: center; height: 100vh; 
                        background-color: #f8fafc; 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
                        color: #334155; 
                    }
                    .spinner { 
                        width: 50px; height: 50px; 
                        border: 4px solid #e2e8f0; border-top-color: #f59e0b; 
                        border-radius: 50%; animation: spin 1s linear infinite; 
                        margin-bottom: 20px; 
                    }
                    @keyframes spin { to { transform: rotate(360deg); } }
                    h2 { font-weight: 600; font-size: 1.25rem; margin: 0; }
                    p { font-size: 0.95rem; color: #64748b; margin-top: 8px; }
                </style>
            </head>
            <body>
                <div class="spinner"></div>
                <h2>Gerando documento...</h2>
                <p>Aplicando os dados de teste no PDF, por favor aguarde.</p>
            </body>
            </html>
        `);
    }
    
    try {
        const response = await axios.post(route('admin.rci.preview', activeTemplate.value.id), {
            mapping_config: mappingConfig.value
        }, {
            responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        
        if (newWindow) {
            newWindow.location.href = url;
        } else {
            // Fallback caso pop-ups estejam completamente bloqueados
            window.location.href = url;
        }
        
    } catch (error) {
        if (newWindow) newWindow.close();
        console.error("Erro ao gerar preview:", error);
        alert("Erro ao gerar a visualização com dados fictícios. Verifique os logs do sistema.");
    } finally {
        isPreviewing.value = false;
    }
};

</script>

<template>
    <Head title="Modelos de RCI" />

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
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Modelos de RCI</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Contratos PDF Interativos
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <button @click="openUploadModal" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Modelo PDF</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="$page.props.flash.success" class="mb-4 bg-emerald-50 text-emerald-600 p-4 rounded-lg font-medium border border-emerald-100 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ $page.props.flash.success }}
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="template in templates" 
                        :key="template.id"
                        class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/60 dark:border-slate-800/60 rounded-2xl group hover:border-brand-green/50 dark:hover:border-brand-green/50 transition-all duration-500 overflow-hidden flex flex-col shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] dark:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.2)] hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.15)] dark:hover:shadow-[0_8px_30px_-4px_rgba(72,86,56,0.25)] relative hover:-translate-y-1"
                        :class="template.is_default ? 'ring-1 ring-brand-green border-brand-green/50' : ''"
                    >
                        <!-- Glow Effect on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-brand-green/0 to-brand-green/5 dark:from-brand-green/0 dark:to-brand-green/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                        <!-- Ribbon for Default -->
                        <div v-if="template.is_default" class="absolute top-0 right-0 z-10 overflow-hidden w-24 h-24 pointer-events-none">
                            <div class="bg-gradient-to-r from-brand-green to-[#5c6e46] text-[9px] font-black uppercase tracking-[0.2em] text-white px-8 py-1.5 rotate-45 translate-x-[22px] translate-y-[16px] shadow-lg text-center">
                                Padrão
                            </div>
                        </div>

                        <div class="p-6 flex flex-col flex-1 relative z-20">
                            <!-- Header (Icon + Title) -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="relative w-12 h-12 flex-shrink-0 rounded-[14px] flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                                    <svg class="w-6 h-6 text-red-500 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3h6"/></svg>
                                </div>
                                <div class="flex flex-col pt-1">
                                    <span 
                                        class="text-[9px] font-black uppercase tracking-widest mb-1"
                                        :class="template.is_default ? 'text-brand-green' : 'text-slate-400'"
                                    >
                                        {{ template.is_default ? 'Modelo Padrão' : 'Alternativo' }}
                                    </span>
                                    <h3 class="text-slate-900 dark:text-white font-bold text-lg tracking-tight line-clamp-2 leading-tight">{{ template.name }}</h3>
                                </div>
                            </div>
                            
                            <div class="flex-1 space-y-4">
                                <!-- Source Indicator -->
                                <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                                    <div class="w-6 h-6 rounded flex items-center justify-center shrink-0 bg-red-100/50 text-red-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">AcroForm Dinâmico</span>
                                        <span class="text-[11px] font-semibold truncate text-slate-700 dark:text-slate-300">PDF Nativo Preenchível</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-auto border-t border-slate-100 dark:border-slate-800/60 p-3 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-1 relative z-20">
                            
                            <button v-if="!template.is_default" @click="router.post(route('admin.rci.set_default', template.id))" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors" title="Definir como Padrão">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>

                            <button @click="openMapper(template)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors" title="Vincular Campos (Mapper)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            </button>

                            <a :href="route('admin.rci.file', template.id)" target="_blank" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Ver Arquivo Original">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                            
                            <button @click="deleteTemplate(template.id)" class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Excluir Modelo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add New Placeholder -->
                    <button 
                        @click="openUploadModal"
                        class="bg-transparent border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl h-full min-h-[280px] flex flex-col items-center justify-center p-8 group hover:border-brand-green hover:bg-brand-green/5 dark:hover:bg-brand-green/10 transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-center group-hover:bg-brand-green group-hover:border-brand-green transition-colors duration-300 mb-4 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-brand-green transition-colors">Cadastrar Novo Modelo RCI</p>
                        <p class="text-[10px] text-slate-400 mt-2 font-medium uppercase tracking-widest text-center">Inicie enviando um documento PDF</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- UPLOAD MODAL -->
        <Modal :show="isUploadModalOpen" @close="closeUploadModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Novo Modelo RCI (Contrato PDF)</h2>
                
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nome do Modelo</label>
                        <input type="text" v-model="uploadForm.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700" placeholder="Ex: Contrato Padrão 2026" required>
                        <InputError :message="uploadForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Arquivo PDF (Preenchível/AcroForm)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-md dark:border-slate-700">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-slate-600 justify-center">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none dark:bg-transparent">
                                        <span>Anexar um arquivo</span>
                                        <input type="file" class="sr-only" accept="application/pdf" @change="handleFileChange" required>
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ uploadForm.file ? uploadForm.file.name : 'Apenas PDF com formulários preenchíveis (máx. 10MB)' }}
                                </p>
                            </div>
                        </div>
                        <InputError :message="uploadForm.errors.file" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="closeUploadModal" class="mr-3 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="uploadForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            {{ uploadForm.processing ? 'Enviando...' : 'Salvar Modelo' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MAPPER MODAL (NATIVE FIELDS) -->
        <Teleport to="body">
            <div v-if="isMapperOpen" class="fixed inset-0 z-[9999] flex bg-slate-100 dark:bg-slate-900 overflow-hidden">
                
                <!-- MAINFRAME -->
                <div class="flex-1 flex flex-col max-w-4xl mx-auto my-8 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
                    
                    <!-- HEADER -->
                    <div class="h-16 border-b border-slate-200 bg-slate-50 px-6 flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="font-bold text-slate-800">Vinculação de Campos Nativos</h2>
                            <p class="text-xs text-slate-500">{{ activeTemplate?.name }}</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button @click="previewFakeData" :disabled="isPreviewing || isLoadingFields" class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 text-white rounded font-medium shadow transition flex items-center disabled:opacity-50">
                                <svg v-if="isPreviewing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Testar com Dados Fakes
                            </button>
                            <button @click="closeMapper" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-200 rounded font-medium">Cancelar</button>
                            <button @click="saveMapping" :disabled="isSavingMapping || isLoadingFields" class="px-5 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium shadow transition flex items-center disabled:opacity-50">
                                <svg v-if="isSavingMapping" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Salvar Vínculos
                            </button>
                        </div>
                    </div>

                    <!-- CONTENT -->
                    <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
                        
                        <div v-if="isLoadingFields" class="flex flex-col items-center justify-center py-20 text-slate-500">
                            <svg class="animate-spin h-10 w-10 text-indigo-500 mb-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p>Analisando formulário PDF...</p>
                        </div>
                        
                        <div v-else-if="nativeFields.length === 0" class="text-center py-16">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Nenhum campo preenchível encontrado</h3>
                            <p class="text-slate-500 max-w-md mx-auto mt-2">Este PDF não possui formulários AcroForm. Para usar a vinculação inteligente, edite o PDF no Adobe Acrobat ou similar e adicione "Campos de Texto" antes de fazer o upload.</p>
                        </div>
                        
                        <div v-else class="space-y-6">
                            <div class="bg-blue-50 text-blue-800 p-4 rounded-lg text-sm border border-blue-100 flex items-start">
                                <svg class="w-5 h-5 mr-2 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    Encontramos <strong>{{ nativeFields.length }}</strong> campos preenchíveis no seu PDF. Escolha qual informação do sistema deve ser inserida em cada campo automaticamente.
                                </div>
                            </div>
                            
                            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-sm font-semibold text-slate-600">
                                            <th class="p-4 w-1/2">Campo no Arquivo PDF</th>
                                            <th class="p-4 w-1/2">Vincular à Variável do Sistema</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(field, index) in mappingConfig" :key="index" class="hover:bg-slate-50 transition">
                                            <td class="p-4">
                                                <div class="font-mono text-sm text-slate-800 font-medium bg-slate-100 px-2 py-1 rounded inline-block">
                                                    {{ field.pdf_field }}
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <select v-model="field.system_var" class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                    <option value="">-- Não preencher (Deixar em branco) --</option>
                                                    <option value="CUSTOM_TEXT">✏️ Digitar Valor Fixo Manualmente</option>
                                                    <option v-for="sysVar in systemVariables" :key="sysVar.value" :value="sysVar.value">
                                                        {{ sysVar.label }}
                                                    </option>
                                                </select>
                                                
                                                <!-- Campo de Texto Manual -->
                                                <div v-if="field.system_var === 'CUSTOM_TEXT'" class="mt-2 relative animate-in fade-in slide-in-from-top-1">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                    </div>
                                                    <input type="text" v-model="field.custom_text" placeholder="Digite o texto que será preenchido..." class="pl-9 w-full rounded-md border-indigo-200 bg-indigo-50/30 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-indigo-900 placeholder:text-indigo-300">
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
