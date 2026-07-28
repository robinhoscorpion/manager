<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    subjects: {
        type: Array,
        required: true
    }
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    id: null,
    name: '',
    active: true,
});

const deleteForm = useForm({});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (subject) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = subject.id;
    form.name = subject.name;
    form.active = subject.active == 1;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.protocol_subjects.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.protocol_subjects.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const toggleActive = (subject) => {
    router.put(route('admin.protocol_subjects.update', subject.id), {
        name: subject.name,
        active: !subject.active
    }, {
        preserveScroll: true,
    });
};

const confirmDelete = (subject) => {
    itemToDelete.value = subject;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        deleteForm.delete(route('admin.protocol_subjects.destroy', itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                itemToDelete.value = null;
            }
        });
    }
};
</script>

<template>
    <Head title="Assuntos de Protocolo" />

    <AuthenticatedLayout>
        <!-- Header Premium -->
        <div class="relative bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 pb-6 mb-8 mt-6 rounded-3xl shadow-sm overflow-hidden mx-6">
            <!-- Background Decoration -->
            <div class="absolute inset-0 bg-gradient-to-br from-brand-green/5 via-transparent to-transparent pointer-events-none"></div>
            <div class="absolute -right-24 -top-24 w-96 h-96 bg-brand-green/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative px-8 pt-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-green/20 to-brand-green/5 flex items-center justify-center border border-brand-green/20 shadow-inner">
                            <svg class="w-7 h-7 text-brand-green drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Assuntos de Protocolos</h2>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                                Gerencie e categorize os motivos pelos quais os protocolos de atendimento e pós-venda são abertos.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button v-if="$page.props.auth.permissions.includes('configuracoes.assuntos_protocolo.gerenciar')" @click="openCreateModal" class="group relative px-5 py-2.5 bg-brand-green hover:bg-brand-green/90 text-white text-sm font-bold rounded-xl transition-all shadow-[0_0_20px_rgba(34,197,94,0.3)] hover:shadow-[0_0_25px_rgba(34,197,94,0.5)] hover:-translate-y-0.5 flex items-center gap-2 overflow-hidden">
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"></div>
                            <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span class="relative z-10">Novo Assunto</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 pb-6">
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.1)] border border-slate-200/60 dark:border-slate-800/60 overflow-hidden relative">
                
                <div v-if="subjects.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 rounded-full bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center mb-6 shadow-inner border border-slate-100 dark:border-slate-700">
                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">Nenhum assunto cadastrado</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">Cadastre os primeiros assuntos para que sua equipe possa categorizar os protocolos de atendimento adequadamente.</p>
                    
                    <button v-if="$page.props.auth.permissions.includes('configuracoes.assuntos_protocolo.gerenciar')" @click="openCreateModal" class="mt-8 px-6 py-2.5 bg-brand-green/10 text-brand-green hover:bg-brand-green hover:text-white text-sm font-bold rounded-xl transition-all border border-brand-green/20 hover:border-transparent flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Adicionar o Primeiro
                    </button>
                </div>

                <div v-else class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800/80">
                                <th class="py-4 px-8 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest w-full">Nome do Assunto</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center whitespace-nowrap">Status</th>
                                <th class="py-4 px-8 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-for="subject in subjects" :key="subject.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-200 group">
                                <td class="py-4 px-8">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center border border-slate-200/60 dark:border-slate-700/60 group-hover:bg-white dark:group-hover:bg-slate-700 group-hover:border-brand-green/30 group-hover:text-brand-green transition-all shadow-sm">
                                            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 group-hover:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                        </div>
                                        <span class="font-bold text-sm text-slate-700 dark:text-slate-200 group-hover:text-brand-green transition-colors">{{ subject.name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <button 
                                        @click="toggleActive(subject)"
                                        :disabled="!$page.props.auth.permissions.includes('configuracoes.assuntos_protocolo.gerenciar')"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/50 disabled:opacity-50 disabled:cursor-not-allowed group/switch transition-all shadow-inner"
                                    >
                                        <span class="sr-only">Toggle status</span>
                                        <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-transparent"></span>
                                        <span 
                                            aria-hidden="true" 
                                            :class="[subject.active ? 'bg-brand-green' : 'bg-slate-200 dark:bg-slate-700']"
                                            class="pointer-events-none absolute mx-auto h-5 w-11 rounded-full transition-colors duration-300 ease-in-out"
                                        ></span>
                                        <span 
                                            aria-hidden="true" 
                                            :class="[subject.active ? 'translate-x-[10px]' : '-translate-x-[10px]']"
                                            class="pointer-events-none absolute left-1/2 -ml-2.5 inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-300 ease-in-out flex items-center justify-center"
                                        >
                                            <svg v-if="subject.active" class="w-2.5 h-2.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            <svg v-else class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </span>
                                    </button>
                                </td>
                                <td class="py-4 px-8">
                                    <div class="flex items-center justify-end gap-1.5 transition-opacity">
                                        <button v-if="$page.props.auth.permissions.includes('configuracoes.assuntos_protocolo.gerenciar')" @click="openEditModal(subject)" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 bg-transparent hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-500/20 shadow-sm hover:shadow" title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button v-if="$page.props.auth.permissions.includes('configuracoes.assuntos_protocolo.gerenciar')" @click="confirmDelete(subject)" class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 bg-transparent hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 transition-all border border-transparent hover:border-red-200 dark:hover:border-red-500/20 shadow-sm hover:shadow" title="Excluir">
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

        <!-- Form Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <form @submit.prevent="submitForm">
                <div class="relative overflow-hidden rounded-t-2xl">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-green/10 to-transparent pointer-events-none"></div>
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 relative z-10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-200/60 dark:border-slate-700 flex items-center justify-center text-brand-green">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="isEditing" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white">
                                {{ isEditing ? 'Editar Assunto' : 'Novo Assunto' }}
                            </h3>
                        </div>
                        <button type="button" @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">
                            Nome do Assunto <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="form.name"
                                class="w-full h-12 pl-11 pr-4 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all shadow-sm"
                                placeholder="Ex: Cancelamento de Contrato"
                                required
                            >
                        </div>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.name }}</div>
                    </div>

                    <div class="flex items-center gap-4 bg-slate-50/80 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/80 transition-colors hover:border-brand-green/30 group/active">
                        <button 
                            type="button"
                            @click="form.active = !form.active"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green/50 transition-all shadow-inner"
                        >
                            <span class="sr-only">Toggle status</span>
                            <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-transparent"></span>
                            <span 
                                aria-hidden="true" 
                                :class="[form.active ? 'bg-brand-green' : 'bg-slate-300 dark:bg-slate-600']"
                                class="pointer-events-none absolute mx-auto h-5 w-11 rounded-full transition-colors duration-300 ease-in-out"
                            ></span>
                            <span 
                                aria-hidden="true" 
                                :class="[form.active ? 'translate-x-[10px]' : '-translate-x-[10px]']"
                                class="pointer-events-none absolute left-1/2 -ml-2.5 inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-300 ease-in-out flex items-center justify-center"
                            >
                                <svg v-if="form.active" class="w-2.5 h-2.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                            </span>
                        </button>
                        <div class="flex-1 cursor-pointer" @click="form.active = !form.active">
                            <label class="text-sm font-bold text-slate-800 dark:text-slate-200 block cursor-pointer">
                                Assunto Ativo
                            </label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Define se este assunto aparecerá na lista para abertura de novos protocolos.</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 rounded-b-2xl">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 dark:text-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-slate-700 rounded-xl transition-all shadow-sm">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-green hover:bg-brand-green/90 rounded-xl transition-all shadow-[0_4px_14px_0_rgba(34,197,94,0.39)] hover:shadow-[0_6px_20px_rgba(34,197,94,0.23)] hover:-translate-y-0.5 disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isEditing ? 'Salvar Alterações' : 'Criar Assunto' }}</span>
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gradient-to-br from-red-100 to-red-50 rounded-2xl dark:from-red-500/20 dark:to-red-500/5 mb-6 border border-red-200 dark:border-red-500/20 shadow-inner">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                
                <h3 class="text-xl font-black text-center text-slate-900 dark:text-white mb-2">Excluir Assunto</h3>
                <p class="text-sm text-center text-slate-500 dark:text-slate-400 mb-8 max-w-sm mx-auto">
                    Tem certeza que deseja excluir o assunto <span class="font-bold text-slate-800 dark:text-slate-200" v-if="itemToDelete">"{{ itemToDelete.name }}"</span>? Esta ação não pode ser desfeita e pode afetar protocolos que estejam utilizando este assunto.
                </p>

                <div class="flex items-center gap-3 w-full">
                    <button @click="isDeleteModalOpen = false" class="flex-1 px-4 py-3 text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 dark:text-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-slate-700 rounded-xl transition-all shadow-sm">
                        Cancelar
                    </button>
                    <button @click="executeDelete" :disabled="deleteForm.processing" class="flex-1 px-4 py-3 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-[0_4px_14px_0_rgba(220,38,38,0.39)] hover:shadow-[0_6px_20px_rgba(220,38,38,0.23)] hover:-translate-y-0.5 disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg v-if="deleteForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-else>Sim, Excluir</span>
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
