<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import HtmlCodeEditor from '@/Components/HtmlCodeEditor.vue';

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

const form = useForm({
    name: '',
    content: '',
    is_default: false,
    product_ids: [],
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.content = item.content || '';
    form.is_default = !!item.is_default;
    form.product_ids = item.products ? item.products.map(p => p.id) : [];
    showEditModal.value = true;
};

const openPreview = (item) => {
    previewItem.value = item;
    showPreviewModal.value = true;
};

const parsedPreviewContent = computed(() => {
    if (!previewItem.value) return '';
    let html = previewItem.value.content || '';
    
    const mockData = {
        '[NOME_TITULAR]': 'João Carlos da Silva',
        '[CPF]': '123.456.789-00',
        '[DATA]': new Date().toLocaleDateString('pt-BR'),
        '[PRODUTO]': 'Cota Resort GTR (Fração A-102)',
        '[VALOR_TOTAL]': 'R$ 45.000,00',
        '[ENTRADA]': 'R$ 5.000,00',
        '[PARCELAS]': '48x de R$ 833,33',
        '[LOCAL]': 'Resort Principal (Mesa 05)',
    };

    for (const [tag, value] of Object.entries(mockData)) {
        const highlightedValue = `<span style="background-color: #fffac7; border-bottom: 2px solid #fce05d; color: #854d0e; font-weight: 700; padding: 0 2px; border-radius: 2px;" title="Tag: ${tag}">${value}</span>`;
        html = html.split(tag).join(highlightedValue);
    }

    return html;
});

const submit = () => {
    if (editingItem.value) {
        form.put(route('admin.contract_templates.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.contract_templates.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Tem certeza que deseja remover este modelo de contrato?')) {
        form.delete(route('admin.contract_templates.destroy', id));
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="item in templates" 
                        :key="item.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-brand-green/30 dark:hover:border-brand-green/30 transition-all overflow-hidden flex flex-col shadow-sm hover:shadow-md relative"
                    >
                        <!-- Ribbon for Default -->
                        <div v-if="item.is_default" class="absolute top-0 right-0 z-10 overflow-hidden w-20 h-20">
                            <div class="bg-brand-green text-[8px] font-black uppercase tracking-widest text-white px-8 py-1 rotate-45 translate-x-[15px] translate-y-[10px] shadow-sm text-center">
                                Global
                            </div>
                        </div>

                        <div class="p-6 space-y-4 flex-1">
                            <div class="flex justify-between items-start relative z-20">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3 text-left">
                                        <div class="w-12 h-12 flex-shrink-0 rounded-xl flex items-center justify-center text-xs font-black text-brand-green bg-brand-green/10 border border-brand-green/20 uppercase shadow-sm group-hover:scale-105 transition-transform">
                                            CNTR
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span 
                                                class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest w-fit border"
                                                :class="item.is_default ? 'bg-brand-green/10 text-brand-green border-brand-green/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'"
                                            >
                                                {{ item.is_default ? 'Padrão Geral' : 'Específico' }}
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-400 line-clamp-1 uppercase tracking-widest">Contrato / Jurídico</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button v-if="item.content" @click="openPreview(item)" class="text-slate-400 hover:text-brand-green p-1.5 transition-colors rounded-md hover:bg-brand-green/10" title="Visualizar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="openEditModal(item)" class="text-slate-400 hover:text-brand-green p-1.5 transition-colors rounded-md hover:bg-brand-green/10" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteItem(item.id)" class="text-slate-400 hover:text-red-500 p-1.5 transition-colors rounded-md hover:bg-red-50 dark:hover:bg-red-500/10" title="Remover">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-left mt-2">
                                <h3 class="text-slate-900 dark:text-white font-bold text-lg tracking-tight group-hover:text-brand-green transition-colors line-clamp-2 leading-tight min-h-[3rem]">{{ item.name }}</h3>
                            </div>

                            <div v-if="item.content" class="flex items-center gap-2 px-3 py-2 bg-brand-green/5 border border-brand-green/10 rounded-lg text-center justify-center">
                                <svg class="w-3.5 h-3.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-[9px] font-black text-brand-green uppercase tracking-widest italic">Texto Jurídico Definido</span>
                            </div>

                            <div v-if="!item.is_default && item.products?.length" class="space-y-1.5">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-brand-green"></div>
                                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Produtos Vinculados ({{ item.products.length }})</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="p in item.products.slice(0, 3)" 
                                        :key="p.id"
                                        class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[8px] font-bold text-slate-500 uppercase tracking-tighter"
                                    >
                                        {{ p.name }}
                                    </span>
                                    <span v-if="item.products.length > 3" class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">
                                        + {{ item.products.length - 3 }} mais
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-6 mt-auto relative z-20">
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Aplicação</span>
                                    <span class="text-[9px] font-black uppercase text-brand-green tracking-widest">
                                        {{ item.is_default ? 'Todos os Produtos' : 'Opcional p/ Produto' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl h-full min-h-[200px] flex flex-col items-center justify-center p-8 group hover:border-brand-green/30 transition-all hover:bg-slate-50 dark:hover:bg-slate-800/30"
                    >
                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover:bg-brand-green/10 transition-colors mb-3">
                            <svg class="w-6 h-6 text-slate-400 group-hover:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest group-hover:text-brand-green transition-colors">Novo Modelo de Contrato</p>
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
                                            class="w-4 h-4 rounded border flex items-center justify-center transition-all bg-white dark:bg-slate-900"
                                            :class="form.product_ids.includes(product.id) ? 'bg-brand-green border-brand-green' : 'border-slate-300 dark:border-slate-600'"
                                        >
                                            <svg v-if="form.product_ids.includes(product.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </div>
                                        <span class="text-[9px] font-bold uppercase tracking-tight truncate flex-1" :class="form.product_ids.includes(product.id) ? 'text-slate-900 dark:text-white' : 'text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300'">
                                            {{ product.name }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rich Text Editor -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Corpo do Contrato (HTML)</label>
                            <span class="text-[9px] text-brand-green uppercase tracking-widest font-bold">Suporta Tags dinâmicas</span>
                        </div>
                        <div class="rounded-xl overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                            <HtmlCodeEditor 
                                v-model="form.content" 
                                :height="600" 
                            />
                        </div>
                    </div>

                    <!-- Magic Tags Cheatsheet -->
                    <div class="bg-brand-green/5 border border-brand-green/10 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <h4 class="text-xs font-bold uppercase text-slate-900 dark:text-white tracking-wider">Tags Jurídicas Disponíveis</h4>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 mb-4 text-left">As tags abaixo serão substituídas pelos dados reais no momento da emissão.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[NOME_TITULAR]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[CPF]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[PRODUTO]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[VALOR_TOTAL]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[ENTRADA]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[PARCELAS]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[DATA]</code>
                            <code class="px-2 py-2 bg-white dark:bg-slate-800 text-brand-green border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-mono font-medium shadow-sm select-all text-center">[LOCAL]</code>
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
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ previewItem.name }}</h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Pré-visualização Jurídica</p>
                        </div>
                    </div>
                    <button @click="showPreviewModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <!-- Browser Shell -->
                <div class="p-12 overflow-y-auto flex-1 preview-content bg-white dark:bg-slate-50 text-slate-900" v-html="parsedPreviewContent"></div>
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
