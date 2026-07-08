<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PermissionsMatrix from '@/Components/Roles/PermissionsMatrix.vue';

const props = defineProps({
    roles: Array,
    all_permissions: Object // Grouped by group
});

const isModalOpen = ref(false);
const isPermissionsModalOpen = ref(false);
const editingRole = ref(null);

const form = useForm({
    name: '',
    description: '',
});

const permissionsForm = useForm({
    permissions: [],
});

const openModal = (role = null) => {
    editingRole.value = role;
    if (role) {
        form.name = role.name;
        form.description = role.description || '';
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const openPermissionsModal = (role) => {
    editingRole.value = role;
    permissionsForm.permissions = role.permissions.map(p => p.id);
    isPermissionsModalOpen.value = true;
};

const submitRole = () => {
    if (editingRole.value) {
        form.put(route('roles.update', editingRole.value.id), {
            onSuccess: () => isModalOpen.value = false,
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => isModalOpen.value = false,
        });
    }
};

const submitPermissions = () => {
    permissionsForm.post(route('roles.permissions.update', editingRole.value.id), {
        onSuccess: () => isPermissionsModalOpen.value = false,
    });
};

const deleteRole = (role) => {
    if (confirm(`Tem certeza que deseja remover o cargo ${role.name}?`)) {
        router.delete(route('roles.destroy', role.id));
    }
};


</script>

<template>
    <Head title="Gerenciar Cargos" />

    <AuthenticatedLayout>


        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Cargos</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Gestão de Permissões
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <!-- Search Bar -->
                            <div class="relative group/search flex-1 sm:w-64">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within/search:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input 
                                    type="text" 
                                    placeholder="BUSCAR CARGO..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl pl-11 pr-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all"
                                >
                            </div>

                            <button 
                                @click="openModal()"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Cargo</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    
                    <!-- Table Header -->
                    <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                        <div class="w-12 text-center ledger-th !text-black hidden sm:block">#</div>
                        <div class="flex-1 min-w-[200px] text-left ledger-th !text-black">Nome do Cargo</div>
                        <div class="flex-[2] text-left ledger-th !text-black hidden lg:block">Descrição</div>
                        <div class="w-32 text-center ledger-th !text-black">Permissões</div>
                        <div class="w-32 text-right ledger-th !text-black">Ações</div>
                    </div>

                    <!-- Table Body -->
                    <div class="w-full">
                        <template v-if="roles && roles.length > 0">
                            <div 
                                v-for="role in roles" :key="role.id"
                                class="ledger-row flex items-center gap-3 px-6 py-3 group relative transition-colors duration-200"
                            >
                                <!-- Accent bar on hover -->
                                <div class="ledger-row-accent"></div>

                                <!-- 1. ID -->
                                <div class="w-12 text-center hidden sm:block">
                                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500">{{ role.id }}</span>
                                </div>

                                <!-- 2. Nome do Cargo -->
                                <div class="flex-1 min-w-[200px] flex items-center gap-3 truncate">
                                    <span class="ledger-client-name group-hover:text-brand-green transition-colors truncate uppercase" :title="role.name">
                                        {{ role.name }}
                                    </span>
                                </div>
                                
                                <!-- 3. Descrição -->
                                <div class="flex-[2] hidden lg:block">
                                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate block" :title="role.description || 'Sem descrição definida.'">
                                        {{ role.description || 'Sem descrição definida.' }}
                                    </span>
                                </div>

                                <!-- 4. Permissões -->
                                <div class="w-32 flex justify-center items-center">
                                    <span class="px-2 py-0.5 bg-brand-green/10 border border-brand-green/20 rounded-md text-[9px] font-black text-brand-green uppercase tracking-widest shadow-sm">
                                        {{ role.permissions.length }} IDs
                                    </span>
                                </div>

                                <!-- 5. Ações -->
                                <div class="w-32 flex items-center justify-end gap-1.5">
                                    <button 
                                        @click="openPermissionsModal(role)"
                                        class="p-1.5 text-slate-400 hover:text-cyan-500 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 rounded-md transition-all"
                                        title="Gerenciar Permissões"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="openModal(role)"
                                        class="p-1.5 text-slate-400 hover:text-brand-green hover:bg-brand-green/10 rounded-md transition-all"
                                        title="Editar Cargo"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="!['admin', 'promotor', 'consultor', 'supervisor'].includes(role.slug)" 
                                        @click="deleteRole(role)"
                                        class="p-1.5 rounded-md transition-all text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"
                                        title="Remover Cargo"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="px-8 py-20 text-center text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-sm">
                            Nenhum cargo encontrado no momento.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
            <div class="relative w-full max-w-md bg-[#1a1f2e] border border-white/10 rounded-2xl shadow-2xl overflow-hidden animate-slide-up">
                <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                    <h3 class="text-xl font-black text-white uppercase tracking-tight leading-none">
                        {{ editingRole ? 'Editar Cargo' : 'Novo Cargo' }}
                    </h3>
                    <button @click="isModalOpen = false" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitRole" class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 px-1">Nome do Cargo</label>
                        <input v-model="form.name" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-purple-500/50 transition-all font-bold text-sm" placeholder="Ex: Supervisor de Vendas" required>
                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 px-1">Descrição</label>
                        <textarea v-model="form.description" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-purple-500/50 transition-all text-xs min-h-[100px]" placeholder="O que este cargo pode fazer?"></textarea>
                    </div>
                    <button type="submit" :disabled="form.processing" class="w-full py-5 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-black uppercase text-xs tracking-[0.25em] shadow-xl shadow-purple-500/20 transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Salvando...' : 'Confirmar Alterações' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Permissions Matrix Modal -->
        <div v-if="isPermissionsModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isPermissionsModalOpen = false"></div>
            <div class="relative w-full max-w-4xl max-h-[85vh] bg-[#1a1f2e] border border-white/10 rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up">
                <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight leading-none">Matriz de Permissões</h3>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-2 flex items-center gap-2">
                            Cargo Selecionado: 
                            <span class="text-purple-400">{{ editingRole?.name }}</span>
                        </p>
                    </div>
                    <button @click="isPermissionsModalOpen = false" class="p-2 rounded-full hove:bg-white/5 text-gray-500 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-black/20">
                    <PermissionsMatrix 
                        :all-permissions="all_permissions"
                        v-model:selected-permissions="permissionsForm.permissions"
                        :is-admin="editingRole?.slug === 'admin'"
                    />
                </div>

                <div class="p-8 border-t border-white/5 bg-white/[0.01] shrink-0">
                    <button 
                        @click="submitPermissions"
                        :disabled="permissionsForm.processing || editingRole?.slug === 'admin'"
                        class="w-full py-5 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.25em] shadow-xl shadow-purple-500/20 disabled:opacity-50 active:scale-[0.98] transition-all"
                    >
                        {{ permissionsForm.processing ? 'Salvando...' : 'Salvar Configurações de Acesso' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(168, 85, 247, 0.2);
}

@keyframes slide-up {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
