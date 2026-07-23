<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

// Configura o worker do PDF.js via Vite local, evitando problemas de CDN ou versões mismatch.
pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

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


// --- MAPEADOR VISUAL (ESTÚDIO) ---
const isMapperOpen = ref(false);
const activeTemplate = ref(null);
const mappingConfig = ref([]);
const isSavingMapping = ref(false);
const pdfContainer = ref(null);

// Drag & Drop State
const isDragging = ref(false);
const draggedMarkerIndex = ref(null);
const activeMarkerId = ref(null);
const pdfCanvas = ref(null);
const isLoadingPdf = ref(false);
const pdfRenderError = ref(false);

const openMapper = async (template) => {
    activeTemplate.value = template;
    mappingConfig.value = template.mapping_config ? JSON.parse(JSON.stringify(template.mapping_config)) : [];
    isMapperOpen.value = true;
    pdfRenderError.value = false;
    
    // Renderiza o PDF via pdf.js
    await nextTick();
    if (pdfCanvas.value) {
        isLoadingPdf.value = true;
        try {
            const url = route('admin.rci.file', template.id);
            const loadingTask = pdfjsLib.getDocument(url);
            const pdf = await loadingTask.promise;
            const page = await pdf.getPage(1);
            
            // Renderiza com escala alta para boa qualidade (retina)
            const viewport = page.getViewport({ scale: 2.5 });
            
            const canvas = pdfCanvas.value;
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            await page.render(renderContext).promise;
        } catch (error) {
            console.error("Erro ao renderizar PDF:", error);
            pdfRenderError.value = true;
        } finally {
            isLoadingPdf.value = false;
        }
    }
};

const closeMapper = () => {
    isMapperOpen.value = false;
    activeTemplate.value = null;
    mappingConfig.value = [];
    activeMarkerId.value = null;
};

const handlePdfClick = (e) => {
    // Se clicou direto num marcador, não cria novo
    if (e.target.closest('.rci-marker')) return;
    
    const rect = e.currentTarget.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const clickY = e.clientY - rect.top;
    
    const xMm = (clickX / rect.width) * 210;
    const yMm = (clickY / rect.height) * 297;
    
    const newId = Date.now();
    mappingConfig.value.push({
        id: newId,
        tag: '${NOVO_CAMPO}',
        x: xMm,
        y: yMm,
        page: 1,
        fontSize: 9,
        uppercase: false,
    });
    
    activateMarker(newId);
};

const activateMarker = (id) => {
    activeMarkerId.value = id;
    nextTick(() => {
        const el = document.getElementById('config-marker-' + id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
};

const startDrag = (e, index) => {
    isDragging.value = true;
    draggedMarkerIndex.value = index;
    activateMarker(mappingConfig.value[index].id);
};

const onDrag = (e) => {
    if (!isDragging.value || draggedMarkerIndex.value === null || !pdfContainer.value) return;
    
    const rect = pdfContainer.value.getBoundingClientRect();
    let moveX = e.clientX - rect.left;
    let moveY = e.clientY - rect.top;
    
    moveX = Math.max(0, Math.min(moveX, rect.width));
    moveY = Math.max(0, Math.min(moveY, rect.height));

    mappingConfig.value[draggedMarkerIndex.value].x = (moveX / rect.width) * 210;
    mappingConfig.value[draggedMarkerIndex.value].y = (moveY / rect.height) * 297;
};

const endDrag = () => {
    isDragging.value = false;
    draggedMarkerIndex.value = null;
};

onMounted(() => {
    window.addEventListener('mousemove', onDrag);
    window.addEventListener('mouseup', endDrag);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('mouseup', endDrag);
});

const removeMarker = (index) => {
    mappingConfig.value.splice(index, 1);
};

const saveMapping = () => {
    isSavingMapping.value = true;
    axios.post(route('admin.rci.update_mapping', activeTemplate.value.id), {
        mapping_config: mappingConfig.value
    }).then(() => {
        alert('Mapeamento salvo com sucesso!');
        // Atualiza a prop local
        const idx = props.templates.findIndex(t => t.id === activeTemplate.value.id);
        if (idx !== -1) {
            props.templates[idx].mapping_config = mappingConfig.value;
        }
    }).catch(error => {
        console.error(error);
        alert('Erro ao salvar mapeamento.');
    }).finally(() => {
        isSavingMapping.value = false;
    });
};


// --- MODAL DE TESTE ---
const isTestModalOpen = ref(false);
const testData = ref({});
const isGeneratingTest = ref(false);

const openTestModal = () => {
    testData.value = {};
    if (activeTemplate.value && activeTemplate.value.mapping_config) {
        activeTemplate.value.mapping_config.forEach(m => {
            if (!testData.value[m.tag]) {
                testData.value[m.tag] = '';
            }
        });
    }
    isTestModalOpen.value = true;
};

const closeTestModal = () => {
    isTestModalOpen.value = false;
};

const submitTest = () => {
    isGeneratingTest.value = true;
    axios.post(route('admin.rci.generate'), {
        template_id: activeTemplate.value.id,
        tags: testData.value
    }, {
        responseType: 'blob'
    }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        window.open(url, '_blank');
        closeTestModal();
    }).catch(error => {
        console.error("Erro ao gerar PDF:", error);
        alert("Ocorreu um erro ao gerar o PDF de teste.");
    }).finally(() => {
        isGeneratingTest.value = false;
    });
};


// --- AÇÕES DA TABELA ---
const setAsDefault = (template) => {
    router.post(route('admin.rci.set_default', template.id), {}, { preserveScroll: true });
};

const isDeleteModalOpen = ref(false);
const templateToDelete = ref(null);

const confirmDelete = (template) => {
    templateToDelete.value = template;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    templateToDelete.value = null;
};

const executeDelete = () => {
    if (!templateToDelete.value) return;
    router.delete(route('admin.rci.destroy', templateToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal()
    });
};
</script>

<template>
    <Head title="Modelos RCI" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-4 w-full">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Motor Dinâmico RCI</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gestão e Mapeamento
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <button 
                                @click="openUploadModal"
                                class="flex items-center justify-center gap-1.5 bg-brand-green hover:bg-[#485638] text-white px-4 py-2 rounded-[10px] transition-all duration-300 shadow-sm whitespace-nowrap"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span class="text-sm font-semibold tracking-wide">Novo Modelo</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Modelos -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-800/20 border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4 font-semibold text-slate-500 text-xs uppercase tracking-wider">Modelo</th>
                                    <th class="px-6 py-4 font-semibold text-slate-500 text-xs uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 font-semibold text-slate-500 text-xs uppercase tracking-wider text-center">Tags Mapeadas</th>
                                    <th class="px-6 py-4 font-semibold text-slate-500 text-xs uppercase tracking-wider text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="!templates || templates.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                        Nenhum modelo RCI cadastrado. Faça upload do primeiro.
                                    </td>
                                </tr>
                                <tr v-for="template in templates" :key="template.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-500/10 text-red-500 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                            </div>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ template.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="template.is_default" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase bg-brand-green/10 text-brand-green border border-brand-green/20">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Padrão
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200">
                                            Alternativo
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ template.mapping_config ? template.mapping_config.length : 0 }} tags
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 transition-opacity">
                                            <button 
                                                v-if="!template.is_default"
                                                @click="setAsDefault(template)"
                                                class="text-xs text-brand-green hover:text-[#485638] font-semibold px-2 py-1 rounded hover:bg-brand-green/10 transition-colors"
                                            >
                                                Tornar Padrão
                                            </button>
                                            <button 
                                                @click="openMapper(template)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-semibold px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-colors border border-blue-200 dark:border-blue-900 flex items-center gap-1"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" /></svg>
                                                Mapear
                                            </button>
                                            <button 
                                                @click="confirmDelete(template)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                                                title="Excluir"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL UPLOAD -->
        <Modal :show="isUploadModalOpen" @close="closeUploadModal" maxWidth="md">
            <div class="p-6 bg-white dark:bg-[#0f1219]">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Cadastrar Novo Modelo (PDF)</h3>
                
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div>
                        <label for="name" class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nome de Identificação</label>
                        <input type="text" id="name" v-model="uploadForm.name" required placeholder="Ex: RCI 2 Anos - 2026" class="w-full h-10 bg-slate-50 dark:bg-[#151a23] border border-slate-200 dark:border-slate-800 rounded-xl px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all">
                        <InputError :message="uploadForm.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <label for="file" class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Arquivo PDF Base</label>
                        <input type="file" id="file" accept=".pdf" @change="handleFileChange" required class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-brand-green/10 file:text-brand-green hover:file:bg-brand-green/20 file:transition-colors cursor-pointer border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#151a23] p-1.5" />
                        <InputError :message="uploadForm.errors.file" class="mt-1" />
                        <p class="text-[10px] text-slate-400 mt-2">O arquivo será convertido para v1.4 e uma imagem de preview será gerada para o mapeamento.</p>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800 mt-6">
                        <button type="button" @click="closeUploadModal" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-100 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="uploadForm.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-brand-green hover:bg-[#485638] disabled:opacity-50 transition-colors">{{ uploadForm.processing ? 'Processando...' : 'Salvar Modelo' }}</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL DE EXCLUSÃO -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="sm">
            <div class="p-6 bg-white dark:bg-[#0f1219]">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div><h3 class="text-lg font-bold text-slate-900 dark:text-white">Excluir Modelo</h3></div>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Tem certeza que deseja excluir o modelo <span class="font-bold text-slate-700">"{{ templateToDelete?.name }}"</span>?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeDeleteModal" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-100 transition-colors">Cancelar</button>
                    <button @click="executeDelete" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors">Excluir</button>
                </div>
            </div>
        </Modal>

        <!-- MAPPER MODAL (FULL SCREEN) -->
        <Teleport to="body">
            <div v-if="isMapperOpen" class="fixed inset-0 z-[9999] flex bg-slate-100 dark:bg-slate-900 overflow-hidden">
            <!-- Sidebar -->
            <div class="w-80 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col shadow-xl z-10">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Estúdio de Tags</h3>
                        <p class="text-xs text-slate-500">Clique no documento para adicionar</p>
                    </div>
                    <button @click="closeMapper" class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-if="mappingConfig.length === 0" class="text-center py-8 text-slate-400 text-sm">
                        Nenhuma tag mapeada.<br/>Clique na imagem do contrato para iniciar.
                    </div>
                    
                    <!-- Lista de Marcadores -->
                    <div v-for="(marker, index) in mappingConfig" :key="marker.id" 
                         :id="'config-marker-' + marker.id"
                         @click="activateMarker(marker.id)"
                         :class="['p-3 rounded-xl relative group cursor-pointer transition-all border', 
                                  activeMarkerId === marker.id ? 'bg-brand-green/10 border-brand-green ring-1 ring-brand-green' : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700']">
                        <button @click.stop="removeMarker(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm z-10 hover:scale-110">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Tag (Variável)</label>
                                <input type="text" v-model="marker.tag" placeholder="${NOME}" class="w-full h-8 bg-white dark:bg-[#151a23] border border-slate-200 dark:border-slate-800 rounded-lg px-2 text-xs font-mono text-brand-green focus:ring-1 focus:ring-brand-green transition-all">
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Tamanho (pt)</label>
                                    <input type="number" v-model="marker.fontSize" class="w-full h-8 bg-white dark:bg-[#151a23] border border-slate-200 dark:border-slate-800 rounded-lg px-2 text-xs text-slate-700 dark:text-slate-300">
                                </div>
                                <div class="flex-1 flex items-end">
                                    <label class="flex items-center gap-2 cursor-pointer h-8">
                                        <input type="checkbox" v-model="marker.uppercase" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                                        <span class="text-[10px] font-bold text-slate-600 uppercase">Tudo Maiúsculo</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-3">
                    <button @click="openTestModal" class="w-full py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-sm transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Testar Tags (PDF)
                    </button>
                    <button @click="saveMapping" :disabled="isSavingMapping" class="w-full py-2.5 rounded-xl bg-brand-green hover:bg-[#485638] text-white font-semibold text-sm transition-colors shadow-sm disabled:opacity-50">
                        {{ isSavingMapping ? 'Salvando...' : 'Salvar Mapeamento' }}
                    </button>
                </div>
            </div>

            <!-- Workspace -->
            <div class="flex-1 relative overflow-auto bg-slate-200/50 dark:bg-[#0f1219] p-8 flex justify-center items-start">
                <div class="relative shadow-2xl bg-white" style="max-width: 1000px; width: 100%;" ref="pdfContainer">
                    
                    <!-- Loading State -->
                    <div v-if="isLoadingPdf" class="absolute inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm">
                        <div class="flex flex-col items-center text-brand-green">
                            <svg class="w-8 h-8 animate-spin mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="font-bold text-sm">Carregando visualização do PDF...</span>
                        </div>
                    </div>

                    <!-- Canvas do PDF (Renderizado via pdf.js) -->
                    <canvas 
                        ref="pdfCanvas" 
                        @click="handlePdfClick"
                        class="w-full h-auto cursor-crosshair border border-slate-300"
                        style="display: block;"
                    ></canvas>
                    
                    <!-- Error State -->
                    <div v-if="pdfRenderError" class="absolute inset-0 bg-white flex items-center justify-center flex-col p-8 text-center cursor-crosshair" @click="handlePdfClick">
                        <p class="text-red-500 font-bold mb-2">Erro ao carregar o PDF</p>
                        <p class="text-sm text-slate-500">Não foi possível carregar a visualização do arquivo PDF. O mapeamento ainda funciona com a tela em branco, mas pode ser difícil acertar as posições.</p>
                    </div>

                    <!-- Overlay markers -->
                    <div 
                        v-for="(marker, index) in mappingConfig" 
                        :key="marker.id"
                        class="absolute flex items-center rci-marker cursor-move group select-none transition-all duration-75"
                        @mousedown.stop.prevent="startDrag($event, index)"
                        :class="{ 'z-20 scale-105': activeMarkerId === marker.id, 'z-10': activeMarkerId !== marker.id }"
                        :style="{ 
                            left: (marker.x / 210 * 100) + '%', 
                            top: (marker.y / 297 * 100) + '%',
                            transform: 'translate(0, -100%)' // Alinha a base do texto com o clique
                        }"
                    >
                        <div class="w-3 h-3 rounded-full -ml-1.5 shadow-md border-2 border-white flex-shrink-0 transition-colors"
                             :class="activeMarkerId === marker.id ? 'bg-brand-green' : 'bg-red-500'"></div>
                        
                        <div class="ml-1 px-2 py-1 text-white font-mono rounded text-[10px] whitespace-nowrap shadow-sm backdrop-blur-sm transition-colors border"
                             :class="activeMarkerId === marker.id ? 'bg-brand-green border-brand-green' : 'bg-slate-900/80 border-slate-700'">
                            {{ marker.tag }}
                        </div>

                        <!-- Delete button on hover/active -->
                        <button @click.stop.prevent="removeMarker(index)" 
                                class="absolute -top-3 -right-3 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm hover:scale-110"
                                :class="{ 'opacity-100': activeMarkerId === marker.id }">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </Teleport>

        <!-- MODAL DE TESTE DO MAPEADOR -->
        <Modal :show="isTestModalOpen" @close="closeTestModal" maxWidth="md">
            <div class="p-6 bg-white dark:bg-[#0f1219] max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Testar Mapeamento</h3>
                <p class="text-xs text-slate-500 mb-6">Preencha os valores para simular as tags criadas.</p>
                
                <form @submit.prevent="submitTest" class="space-y-4">
                    <div v-for="(val, tag) in testData" :key="tag">
                        <label class="block text-[11px] font-black text-brand-green uppercase tracking-widest mb-1.5">{{ tag }}</label>
                        <input type="text" v-model="testData[tag]" class="w-full h-10 bg-slate-50 dark:bg-[#151a23] border border-slate-200 dark:border-slate-800 rounded-xl px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all" placeholder="Valor de teste...">
                    </div>

                    <div v-if="Object.keys(testData).length === 0" class="text-sm text-slate-500 py-4">Nenhuma tag foi adicionada ainda.</div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800 mt-6">
                        <button type="button" @click="closeTestModal" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-100 transition-colors">Fechar</button>
                        <button type="submit" :disabled="isGeneratingTest" class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            {{ isGeneratingTest ? 'Gerando...' : 'Gerar PDF' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
