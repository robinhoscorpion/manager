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


        <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
            <!-- Premium Header Aligned with Table -->
            <div class="relative bg-white/[0.02] backdrop-blur-3xl border border-white/5 px-6 py-4 rounded-xl mb-6 shadow-2xl group transition-all duration-500 z-20">
                <!-- Decorative background glow container (Nested to allow dropdowns to escape) -->
                <div class="absolute inset-0 overflow-hidden rounded-xl pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 rounded-full blur-[100px] group-hover:bg-blue-500/15 transition-all duration-700"></div>
                    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500/10 rounded-full blur-[100px] group-hover:bg-purple-500/15 transition-all duration-700"></div>
                </div>

                <div class="relative flex flex-col xl:flex-row items-start xl:items-center justify-between gap-8">
                    <!-- Title Section -->
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-pink-500/20 border border-white/10 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-white uppercase tracking-tighter leading-none">Cargos</h2>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-1.5 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                                Gestão de Permissões
                            </p>
                        </div>
                    </div>

                    <!-- Actions & Search Hub -->
                    <div class="flex items-center gap-6 w-full sm:w-auto">
                        <!-- Search Bar -->
                        <div class="relative group/search flex-1 sm:w-64">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within/search:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                type="text" 
                                placeholder="NOTER CARGO..." 
                                class="w-full bg-white/[0.03] border border-white/10 rounded-xl pl-11 pr-4 py-3 text-[10px] font-bold text-white uppercase tracking-widest placeholder:text-gray-600 focus:outline-none focus:border-purple-500/40 focus:bg-white/[0.05] transition-all"
                            >
                        </div>

                        <!-- Deluxe Separator -->
                        <div class="hidden xl:block w-[1px] h-8 bg-white/10 mx-1"></div>

                        <!-- Add Button -->
                        <button 
                            @click="openModal()"
                            class="group relative px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] transition-all duration-300 shadow-xl shadow-purple-500/10 hover:shadow-purple-500/20 active:scale-95 flex items-center gap-3 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Novo Cargo
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-[#1a1f2e]/60 backdrop-blur-3xl border border-white/10 rounded-xl overflow-hidden shadow-2xl flex flex-col">
                <!-- High-Density Table Header -->
                <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-white/5 bg-white/[0.03] w-full text-[9px] font-black text-center text-gray-400 uppercase tracking-[0.2em]">
                    <div class="w-12">#</div>
                    <div class="flex-1 text-left px-4">Nome do Cargo</div>
                    <div class="flex-[2] text-left">Descrição</div>
                    <div class="w-32">Permissões</div>
                    <div class="w-32 text-right px-4">Ações</div>
                </div>

                <!-- Table Body -->
                <div class="w-full">
                    <template v-if="roles.length > 0">
                        <div v-for="(role, index) in roles" :key="role.id" 
                            class="flex items-center gap-1.5 px-4 py-1.5 transition-all duration-300 border-b border-white/[0.03] hover:bg-white/[0.04] group"
                        >
                            <div class="w-12 font-medium text-gray-500 text-center text-[10px]">{{ role.id }}</div>
                            <div class="flex-1 px-4">
                                <span class="text-[11px] font-bold text-white group-hover:text-purple-400 transition-colors uppercase tracking-tight">
                                    {{ role.name }}
                                </span>
                            </div>
                            <div class="flex-[2] text-[10px] text-gray-500 line-clamp-1">
                                {{ role.description || 'Sem descrição definida.' }}
                            </div>
                            <div class="w-32 flex justify-center">
                                <span class="px-2 py-0.5 bg-purple-500/10 border border-purple-500/20 rounded-md text-[9px] font-black text-purple-400 uppercase tracking-widest group-hover:bg-purple-500/20 transition-all">
                                    {{ role.permissions.length }} IDs
                                </span>
                            </div>
                            <div class="w-32 flex justify-end gap-1 px-4">
                                <button 
                                    @click="openPermissionsModal(role)"
                                    class="p-1.5 hover:bg-white/5 rounded-md transition-all text-gray-500 hover:text-cyan-400"
                                    title="Gerenciar Permissões"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </button>
                                <button 
                                    @click="openModal(role)"
                                    class="p-1.5 hover:bg-white/5 rounded-md transition-all text-gray-500 hover:text-white"
                                    title="Editar Cargo"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button 
                                    v-if="!['admin', 'promotor', 'consultor', 'supervisor'].includes(role.slug)" 
                                    @click="deleteRole(role)"
                                    class="p-1.5 hover:bg-red-500/10 rounded-md transition-all text-gray-500 hover:text-red-400"
                                    title="Remover Cargo"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-20 text-center">
                        <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Nenhum cargo encontrado.</p>
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
