<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import HtmlCodeEditor from '@/Components/HtmlCodeEditor.vue';

const props = defineProps({
    items: Array,
});

const showEditModal = ref(false);
const editingItem = ref(null);
const showPreviewModal = ref(false);
const previewItem = ref(null);

const form = useForm({
    name: '',
    code: '',
    type: 'atendimento',
    description: '',
    content: '',
    is_active: true,
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.code = item.code;
    form.type = item.type || 'atendimento';
    form.description = item.description || '';
    form.content = item.content || '';
    form.is_active = !!item.is_active;
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
        '[NUMERO]': '123'
    };

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

const deleteItem = (id) => {
    if (confirm('Tem certeza que deseja remover esta cortesia?')) {
        form.delete(route('admin.complimentary_items.destroy', id));
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};

</script>

<template>
    <Head title="Gestão de Cortesias" />

    <AuthenticatedLayout>
        <div class="py-12 bg-[#020617] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Gestão de Cortesias</h1>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-[0.3em] mt-1">Cadastro de Itens Cortesia para Propostas</p>
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="bg-cyan-600 hover:bg-cyan-500 text-white font-black uppercase text-[10px] tracking-widest px-6 py-3 rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-95 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova Cortesia
                    </button>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="item in items" 
                        :key="item.id"
                        class="bg-[#0d1117] border border-white/5 rounded-2xl group hover:border-cyan-500/30 transition-all overflow-hidden flex flex-col shadow-2xl relative"
                    >
                        <div class="p-6 space-y-4 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-12 flex-shrink-0 rounded-2xl flex items-center justify-center text-xs font-black text-white shadow-lg bg-gradient-to-br from-cyan-600 to-blue-700 uppercase">
                                            {{ item.code }}
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span v-if="item.type === 'atendimento'" class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-blue-500/10 text-blue-400 border border-blue-500/20 w-fit">Atendimento</span>
                                            <span v-else-if="item.type === 'contrato'" class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-purple-500/10 text-purple-400 border border-purple-500/20 w-fit">Contrato</span>
                                            <span class="text-[9px] font-bold text-gray-500 line-clamp-1 uppercase tracking-widest">Cód Interno</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <a v-if="item.content" :href="route('admin.complimentary_items.pdf', item.id)" target="_blank" class="text-gray-600 hover:text-red-400 p-1 transition-colors" title="Visualizar em PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3v5a2 2 0 002 2h5" /></svg>
                                    </a>
                                    <button v-if="item.content" @click="openPreview(item)" class="text-gray-600 hover:text-cyan-400 p-1 transition-colors" title="Visualizar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="openEditModal(item)" class="text-gray-600 hover:text-cyan-400 p-1 transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteItem(item.id)" class="text-gray-600 hover:text-red-400 p-1 transition-colors" title="Remover">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-black text-lg uppercase tracking-tighter group-hover:text-cyan-400 transition-colors">{{ item.name }}</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1 line-clamp-2 min-h-[2.5em]">{{ item.description || 'Sem descrição' }}</p>
                            </div>

                            <!-- Content preview indicator -->
                            <div v-if="item.content" class="flex items-center gap-2 px-3 py-2 bg-cyan-500/5 border border-cyan-500/10 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-[9px] font-black text-cyan-500 uppercase tracking-widest">Modelo criado</span>
                            </div>
                        </div>

                        <div class="px-6 pb-6">
                            <div class="pt-4 border-t border-white/5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-600">Status</span>
                                    <div 
                                        class="px-2 py-1 rounded text-[8px] font-black uppercase tracking-widest border"
                                        :class="item.is_active ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border-red-500/20 text-red-400'"
                                    >
                                        {{ item.is_active ? 'Ativo' : 'Inativo' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-white/5 rounded-2xl h-full min-h-[280px] flex flex-col items-center justify-center p-8 group hover:border-cyan-500/30 transition-all hover:bg-white/[0.01]"
                    >
                        <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-cyan-500/10 transition-colors mb-3">
                            <svg class="w-5 h-5 text-gray-600 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest group-hover:text-gray-300 transition-colors">Nova Cortesia</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="7xl">
            <div class="bg-[#0d1117] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-white/5 bg-gradient-to-r from-cyan-600/20 to-transparent flex items-center justify-between flex-shrink-0">
                    <h2 class="text-xl font-black text-white uppercase tracking-tighter">{{ editingItem ? 'Editar Cortesia' : 'Nova Cortesia' }}</h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nome da Cortesia</label>
                            <input v-model="form.name" type="text" required class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Ex: Isenção de Taxa de Adesão">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1 text-center">Sigla/Cód</label>
                            <input v-model="form.code" type="text" required maxlength="10" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-2 py-2.5 text-white text-sm text-center font-black outline-none focus:border-cyan-500/50" placeholder="TAXA">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Tipo de Aplicação</label>
                            <select v-model="form.type" required class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50 appearance-none cursor-pointer">
                                <option value="atendimento" class="bg-[#1a1f2e]">Cortesia de Atendimento</option>
                                <option value="contrato" class="bg-[#1a1f2e]">Cortesia de Contrato</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Descrição Resumida</label>
                            <input v-model="form.description" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Descrição curta para exibição em cards">
                        </div>
                        <div class="flex items-end">
                            <div class="w-full flex items-center gap-3 bg-white/[0.02] p-2.5 px-4 rounded-xl border border-white/5 h-[42px]">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    <span class="ml-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Ativo</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Rich Text Editor -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Modelo da Cortesia</label>
                            <span class="text-[9px] text-gray-600 uppercase tracking-widest">Editor Visual</span>
                        </div>
                        <HtmlCodeEditor 
                            v-model="form.content" 
                            :height="600" 
                        />
                    </div>

                    <!-- Magic Tags Cheatsheet -->
                    <div class="bg-[#1a1f2e]/50 border border-white/5 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <h4 class="text-[10px] font-black uppercase text-white tracking-widest">Variáveis Dinâmicas (Mágicas)</h4>
                        </div>
                        <p class="text-[11px] text-gray-400 mb-3">Copie as tags abaixo e cole no seu HTML. Elas serão preenchidas automaticamente pelo formato de Atendimento na hora de gerar o PDF do cliente.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <code class="px-2 py-1.5 bg-[#0d1117] text-cyan-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[NOME_TITULAR]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-cyan-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[DATA_NASCIMENTO]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-cyan-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[CPF]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-cyan-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[EMAIL]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-cyan-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[CELULAR]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-pink-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[NOME_CONJUGE]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-orange-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[ID_ATENDIMENTO]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-orange-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[DATA]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-orange-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[LOCAL]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-blue-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[CEP]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-blue-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[RUA]</code>
                            <code class="px-2 py-1.5 bg-[#0d1117] text-blue-400 border border-white/10 rounded-lg text-[10px] select-all text-center">[NUMERO]</code>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeModal" class="flex-1 py-3.5 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg shadow-cyan-500/20">{{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Preview Modal -->
        <Modal :show="showPreviewModal" @close="showPreviewModal = false" maxWidth="7xl">
            <div v-if="previewItem" class="bg-[#0d1117] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-white/5 bg-gradient-to-r from-cyan-600/20 to-transparent flex items-center justify-between flex-shrink-0">
                    <div>
                        <h2 class="text-xl font-black text-white uppercase tracking-tighter">{{ previewItem.name }}</h2>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Pré-visualização do Modelo</p>
                    </div>
                    <button @click="showPreviewModal = false" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <!-- Browser Shell -->
                <div class="p-8 overflow-y-auto flex-1 preview-content bg-white" v-html="parsedPreviewContent"></div>
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
