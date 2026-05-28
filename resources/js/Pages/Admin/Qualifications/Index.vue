<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    qualifications: Array,
});

const showEditModal = ref(false);
const editingQualification = ref(null);

const form = useForm({
    name: '',
    code: '',
    color: 'bg-blue-500',
    description: '',
    is_active: true,
});

const colors = [
    { name: 'Azul', class: 'bg-blue-500' },
    { name: 'Esmeralda', class: 'bg-emerald-500' },
    { name: 'Ciano', class: 'bg-cyan-500' },
    { name: 'Roxo', class: 'bg-purple-500' },
    { name: 'Rosa', class: 'bg-pink-500' },
    { name: 'Laranja', class: 'bg-orange-500' },
    { name: 'Âmbar', class: 'bg-amber-500' },
    { name: 'Rosa Choque', class: 'bg-rose-500' },
    { name: 'Slate', class: 'bg-slate-500' },
];

const openCreateModal = () => {
    editingQualification.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (qual) => {
    editingQualification.value = qual;
    form.name = qual.name;
    form.code = qual.code;
    form.color = qual.color;
    form.description = qual.description || '';
    form.is_active = !!qual.is_active;
    showEditModal.value = true;
};

const submit = () => {
    if (editingQualification.value) {
        form.put(route('admin.qualifications.update', editingQualification.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.qualifications.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteQualification = (id) => {
    if (confirm('Tem certeza que deseja remover este tipo de qualificação?')) {
        form.delete(route('admin.qualifications.destroy', id));
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Tipos de Qualificação" />

    <AuthenticatedLayout>
        <div class="py-12 bg-[#020617] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Tipos de Qualificação</h1>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-[0.3em] mt-1">Personalização do Fluxo de Atendimento</p>
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="bg-cyan-600 hover:bg-cyan-500 text-white font-black uppercase text-[10px] tracking-widest px-6 py-3 rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-95 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova Qualificação
                    </button>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="qual in qualifications" 
                        :key="qual.id"
                        class="bg-[#0d1117] border border-white/5 rounded-2xl group hover:border-cyan-500/30 transition-all overflow-hidden flex flex-col shadow-2xl relative"
                    >
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-start">
                                <div 
                                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl font-black text-white shadow-lg"
                                    :class="qual.color"
                                >
                                    {{ qual.code }}
                                </div>
                                <div class="flex gap-2">
                                    <button @click="openEditModal(qual)" class="text-gray-600 hover:text-cyan-400 p-1 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteQualification(qual.id)" class="text-gray-600 hover:text-red-400 p-1 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-black text-lg uppercase tracking-tighter group-hover:text-cyan-400 transition-colors">{{ qual.name }}</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">{{ qual.description || 'Sem descrição' }}</p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-white/5">
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-600">Status</span>
                                <div 
                                    class="px-2 py-1 rounded text-[8px] font-black uppercase tracking-widest border"
                                    :class="qual.is_active ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border-red-500/20 text-red-400'"
                                >
                                    {{ qual.is_active ? 'Ativo' : 'Inativo' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-white/5 rounded-2xl h-full min-h-[180px] flex flex-col items-center justify-center p-8 group hover:border-cyan-500/30 transition-all hover:bg-white/[0.01]"
                    >
                        <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-cyan-500/10 transition-colors mb-3">
                            <svg class="w-5 h-5 text-gray-600 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest group-hover:text-gray-300 transition-colors">Novo Tipo</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showEditModal" @close="closeModal">
            <div class="bg-[#0d1117] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col">
                <div class="p-6 border-b border-white/5 bg-gradient-to-r from-cyan-600/20 to-transparent flex items-center justify-between">
                    <h2 class="text-xl font-black text-white uppercase tracking-tighter">{{ editingQualification ? 'Editar Qualificação' : 'Nova Qualificação' }}</h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-3 space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nome</label>
                            <input v-model="form.name" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Ex: Qualificado">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1 text-center">Código</label>
                            <input v-model="form.code" type="text" maxlength="5" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-2 py-2.5 text-white text-sm text-center font-black outline-none focus:border-cyan-500/50" placeholder="Q">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Cor Associada</label>
                        <div class="grid grid-cols-9 gap-2">
                            <button 
                                v-for="color in colors" 
                                :key="color.class"
                                type="button"
                                @click="form.color = color.class"
                                class="w-full aspect-square rounded-lg border-2 transition-all p-0.5"
                                :class="[color.class, form.color === color.class ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-60 hover:opacity-100']"
                                :title="color.name"
                            ></button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Descrição</label>
                        <textarea v-model="form.description" rows="2" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50 resize-none" placeholder="Ex: Cliente atende aos requisitos básicos..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 bg-white/[0.02] p-4 rounded-xl border border-white/5">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Qualificação Ativa</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeModal" class="flex-1 py-3.5 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg shadow-cyan-500/20">{{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
