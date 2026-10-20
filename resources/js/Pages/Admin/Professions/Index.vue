<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    professions: {
        type: Array,
        required: true
    }
});

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};
const canManage = computed(() => can('configuracoes.profissoes.gerenciar'));

// Filtros
const activeTab = ref(props.professions.some(p => !p.reviewed) ? 'pending' : 'all');
const search = ref('');

const pendingCount = computed(() => props.professions.filter(p => !p.reviewed).length);
const inactiveCount = computed(() => props.professions.filter(p => !p.active).length);

const tabs = computed(() => [
    { value: 'all', label: 'Todas', count: props.professions.length },
    { value: 'pending', label: 'A revisar', count: pendingCount.value },
    { value: 'inactive', label: 'Inativas', count: inactiveCount.value },
]);

const normalizeText = (text) => String(text ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const filteredProfessions = computed(() => {
    const term = normalizeText(search.value.trim());
    return props.professions.filter(p => {
        if (activeTab.value === 'pending' && p.reviewed) return false;
        if (activeTab.value === 'inactive' && p.active) return false;
        return !term || normalizeText(p.name).includes(term);
    });
});

// Criar / editar
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingItem = ref(null);
const form = useForm({ id: null, name: '', active: true });

const openCreateModal = () => {
    isEditing.value = false;
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (profession) => {
    isEditing.value = true;
    editingItem.value = profession;
    form.clearErrors();
    form.id = profession.id;
    form.name = profession.name;
    form.active = !!profession.active;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitForm = () => {
    const options = { preserveScroll: true, onSuccess: () => closeModal() };
    if (isEditing.value) {
        form.put(route('admin.professions.update', form.id), options);
    } else {
        form.post(route('admin.professions.store'), options);
    }
};

const toggleActive = (profession) => {
    router.put(route('admin.professions.update', profession.id), {
        name: profession.name,
        active: !profession.active
    }, { preserveScroll: true });
};

const approve = (profession) => {
    router.post(route('admin.professions.approve', profession.id), {}, { preserveScroll: true });
};

// Unificar
const isMergeModalOpen = ref(false);
const mergeSource = ref(null);
const mergeForm = useForm({ target_id: null });

const mergeOptions = computed(() => props.professions
    .filter(p => p.id !== mergeSource.value?.id)
    .map(p => ({ label: p.reviewed ? p.name : `${p.name} (a revisar)`, value: p.id })));

const openMergeModal = (profession) => {
    mergeSource.value = profession;
    mergeForm.reset();
    mergeForm.clearErrors();
    isMergeModalOpen.value = true;
};

const submitMerge = () => {
    mergeForm.post(route('admin.professions.merge', mergeSource.value.id), {
        preserveScroll: true,
        onSuccess: () => { isMergeModalOpen.value = false; }
    });
};

// Excluir
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (profession) => {
    itemToDelete.value = profession;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    deleteForm.delete(route('admin.professions.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            itemToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Profissões" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="relative bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 pb-6 mb-8 mt-6 rounded-3xl shadow-sm overflow-hidden mx-6">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-green/5 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative px-8 pt-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-green/20 to-brand-green/5 flex items-center justify-center border border-brand-green/20 shadow-inner">
                            <svg class="w-7 h-7 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Profissões</h2>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                                Lista usada nos atendimentos e agendamentos. Quando a equipe digita uma profissão que não existe, ela aparece aqui como <span class="font-bold text-amber-600">a revisar</span> para você aprovar, corrigir ou unificar.
                            </p>
                        </div>
                    </div>

                    <button v-if="canManage" @click="openCreateModal" class="px-5 py-2.5 bg-brand-green hover:bg-brand-green/90 text-white text-sm font-bold rounded-xl transition-all shadow-sm hover:-translate-y-0.5 flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Nova Profissão
                    </button>
                </div>
            </div>
        </div>

        <div class="px-6 pb-6">
            <!-- Aviso de pendências -->
            <div v-if="pendingCount && activeTab !== 'pending'" class="mb-4 flex items-center justify-between gap-3 px-5 py-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30">
                <span class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ pendingCount }} profissão(ões) digitada(s) pela equipe aguardando revisão.</span>
                <button @click="activeTab = 'pending'" class="text-xs font-bold text-amber-700 dark:text-amber-300 hover:underline shrink-0">Revisar agora</button>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center justify-between mb-4">
                <div class="flex gap-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1 rounded-xl w-fit">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        @click="activeTab = tab.value"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2"
                        :class="activeTab === tab.value ? 'bg-brand-green text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                    >
                        {{ tab.label }}
                        <span class="px-1.5 rounded-md text-[10px]" :class="activeTab === tab.value ? 'bg-white/20' : (tab.value === 'pending' && tab.count ? 'bg-amber-500 text-white' : 'bg-slate-100 dark:bg-slate-800')">{{ tab.count }}</span>
                    </button>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Pesquisar profissão..."
                    class="w-full sm:w-72 h-10 px-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green"
                >
            </div>

            <!-- Tabela -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-200/60 dark:border-slate-800/60 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800/80">
                                <th class="py-4 px-8 text-xs font-bold text-slate-400 uppercase tracking-widest w-full">Profissão</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Em uso</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Ativa</th>
                                <th class="py-4 px-8 text-xs font-bold text-slate-400 uppercase tracking-widest text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-for="profession in filteredProfessions" :key="profession.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors" :class="{ 'bg-amber-50/40 dark:bg-amber-500/5': !profession.reviewed }">
                                <td class="py-3 px-8">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-bold text-sm text-slate-700 dark:text-slate-200">{{ profession.name }}</span>
                                        <span v-if="!profession.reviewed" class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">A revisar</span>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center text-sm tabular-nums text-slate-500">{{ profession.usage_count }}</td>
                                <td class="py-3 px-6 text-center">
                                    <button
                                        @click="toggleActive(profession)"
                                        :disabled="!canManage"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="profession.active ? 'bg-brand-green' : 'bg-slate-200 dark:bg-slate-700'"
                                        :title="profession.active ? 'Desativar' : 'Ativar'"
                                    >
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" :class="profession.active ? 'translate-x-[18px]' : 'translate-x-0.5'"></span>
                                    </button>
                                </td>
                                <td class="py-3 px-8">
                                    <div v-if="canManage" class="flex items-center justify-end gap-1">
                                        <button v-if="!profession.reviewed" @click="approve(profession)" class="h-8 px-3 flex items-center gap-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 transition-colors" title="Aprovar como está">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            Aprovar
                                        </button>
                                        <button @click="openEditModal(profession)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 transition-colors" title="Editar / corrigir nome">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button @click="openMergeModal(profession)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-violet-50 dark:hover:bg-violet-500/10 hover:text-violet-600 transition-colors" title="Unificar com outra profissão">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </button>
                                        <button v-if="!profession.usage_count" @click="confirmDelete(profession)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 transition-colors" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredProfessions.length">
                                <td colspan="4" class="py-12 text-center text-sm text-slate-400">
                                    {{ activeTab === 'pending' ? 'Nenhuma profissão aguardando revisão.' : 'Nenhuma profissão encontrada.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Criar / editar -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <form @submit.prevent="submitForm" class="p-6 space-y-5">
                <h3 class="text-lg font-black text-slate-800 dark:text-white">{{ isEditing ? 'Editar Profissão' : 'Nova Profissão' }}</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Nome <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Ex: Piloto de Drone"
                        class="w-full h-11 px-4 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green"
                    >
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.name }}</div>
                    <p v-if="isEditing && editingItem?.usage_count && form.name !== editingItem.name" class="text-[11px] text-amber-600 mt-2">
                        Os {{ editingItem.usage_count }} cadastro(s) que usam "{{ editingItem.name }}" também serão atualizados.
                    </p>
                </div>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input v-model="form.active" type="checkbox" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Ativa (aparece na lista dos formulários)</span>
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700 rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-green hover:bg-brand-green/90 rounded-xl disabled:opacity-50">
                        {{ isEditing ? 'Salvar' : 'Criar' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Unificar -->
        <Modal :show="isMergeModalOpen" @close="isMergeModalOpen = false" maxWidth="md">
            <form @submit.prevent="submitMerge" class="p-6 space-y-5">
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Unificar Profissão</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        <span class="font-bold text-slate-700 dark:text-slate-200">"{{ mergeSource?.name }}"</span>
                        será removida e os {{ mergeSource?.usage_count || 0 }} cadastro(s) que a usam passarão a usar a profissão escolhida abaixo.
                    </p>
                </div>

                <SearchableSelect v-model="mergeForm.target_id" :options="mergeOptions" label="Unificar em" placeholder="SELECIONE" :error="mergeForm.errors.target_id" required />

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="isMergeModalOpen = false" class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700 rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="mergeForm.processing || !mergeForm.target_id" class="px-6 py-2.5 text-sm font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl disabled:opacity-50">Unificar</button>
                </div>
            </form>
        </Modal>

        <!-- Excluir -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" maxWidth="md">
            <div class="p-6 space-y-5">
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Excluir Profissão</h3>
                    <p class="text-sm text-slate-500 mt-1">Tem certeza que deseja excluir <span class="font-bold text-slate-700 dark:text-slate-200">"{{ itemToDelete?.name }}"</span>? Ela não está sendo usada em nenhum cadastro.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="isDeleteModalOpen = false" class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700 rounded-xl">Cancelar</button>
                    <button @click="executeDelete" :disabled="deleteForm.processing" class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl disabled:opacity-50">Sim, Excluir</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
