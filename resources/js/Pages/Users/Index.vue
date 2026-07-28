<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const authUser = computed(() => usePage().props.auth.user);

const props = defineProps({
    users: Array,
    roles: Array
});

const isModalOpen = ref(false);
const editingUser = ref(null);
const updatingStatusId = ref(null);
const isDeleteModalOpen = ref(false);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    roles: [],
    photo: null,
});

const photoPreview = ref(null);
const photoInput = ref(null);
const cropperModal = ref(false);
const imageToCrop = ref(null);
const cropper = ref(null);
const isProcessing = ref(false);

const selectNewPhoto = () => {
    photoInput.value.click();
};

const handlePhotoSelection = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        imageToCrop.value = event.target.result;
        cropperModal.value = true;
        
        setTimeout(() => {
            const image = document.getElementById('image-editor');
            if (cropper.value) cropper.value.destroy();
            
            cropper.value = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                checkCrossOrigin: false,
                ready() {
                    const container = document.querySelector('.cropper-container');
                    if (container) container.style.borderRadius = '24px';
                    setTimeout(() => {
                        if (cropper.value) cropper.value.resize();
                    }, 50);
                }
            });
        }, 300);
    };
    reader.readAsDataURL(file);
};

const applyCrop = () => {
    isProcessing.value = true;
    
    setTimeout(() => {
        const canvas = cropper.value.getCroppedCanvas({
            width: 512,
            height: 512,
        });

        canvas.toBlob((blob) => {
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
            form.photo = file;
            photoPreview.value = canvas.toDataURL('image/jpeg');
            closeCropper();
            isProcessing.value = false;
        }, 'image/jpeg');
    }, 800);
};

const closeCropper = () => {
    cropperModal.value = false;
    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }
    if (photoInput.value) photoInput.value.value = '';
};

onUnmounted(() => {
    if (cropper.value) cropper.value.destroy();
});

const isConvertModalOpen = ref(false);
const convertingUser = ref(null);
const convertForm = useForm({
    position: '',
    salary: '',
    type: 'CLT',
    workload: '44 Horas',
    hired_at: new Date().toISOString().split('T')[0],
});

const openConvertModal = (user) => {
    convertingUser.value = user;
    convertForm.reset();
    isConvertModalOpen.value = true;
};

const submitConversion = () => {
    convertForm.post(route('employees.convert', convertingUser.value.id), {
        onSuccess: () => {
            isConvertModalOpen.value = false;
            convertingUser.value = null;
        },
    });
};

const openModal = (user = null) => {
    editingUser.value = user;
    if (user) {
        form.name = user.name;
        form.email = user.email;
        form.password = '';
        form.roles = user.roles.map(r => r.id);
        photoPreview.value = user.profile_photo_url;
    } else {
        form.reset();
        photoPreview.value = null;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    if (editingUser.value) {
        // Inertia file uploads work better with POST + _method: PUT
        // We must pass the method as an attribute in the data, not just an option
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('users.update', editingUser.value.id), {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDeleteUser = (user) => {
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const executeDeleteUser = () => {
    if (!userToDelete.value) return;
    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            userToDelete.value = null;
        }
    });
};

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

const toggleStatus = (user) => {
    if (updatingStatusId.value) return;

    const originalStatus = user.status;
    updatingStatusId.value = user.id;
    
    // Optimistic update
    user.status = !user.status;

    router.patch(route('users.toggle-status', user.id), {}, { 
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            updatingStatusId.value = null;
        },
        onError: () => {
            user.status = originalStatus;
        }
    });
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const impersonate = (user) => {
    router.post(route('users.impersonate', user.id));
};
</script>

<template>
    <Head title="Usuários do Sistema" />

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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Usuários</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Controle de Acessos
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
                                    placeholder="BUSCAR USUÁRIO..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl pl-11 pr-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all"
                                >
                            </div>

                            <button 
                                v-if="can('usuarios.criar')"
                                @click="openModal()"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Usuário</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    
                    <!-- Table Header -->
                    <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                        <div class="flex-1 min-w-[200px] text-left ledger-th !text-black">Usuário</div>
                        <div class="w-48 text-left ledger-th !text-black hidden sm:block">Cargo</div>
                        <div class="w-48 text-left ledger-th !text-black hidden md:block">Email</div>
                        <div class="w-32 text-center ledger-th !text-black hidden lg:block">Vínculo</div>
                        <div class="w-32 text-center ledger-th !text-black hidden xl:block">Criado em</div>
                        <div class="w-24 text-center ledger-th !text-black">Status</div>
                        <div class="w-32 text-right ledger-th !text-black">Ações</div>
                    </div>

                    <!-- Table Body -->
                    <div class="w-full">
                        <template v-if="users && users.length > 0">
                            <div 
                                v-for="user in users" :key="user.id"
                                class="ledger-row flex items-center gap-3 px-6 py-3 group relative transition-colors duration-200"
                                :class="user.status ? '' : 'opacity-60 grayscale-[30%] bg-slate-50 dark:bg-slate-800/30'"
                            >
                                <!-- Accent bar on hover -->
                                <div class="ledger-row-accent" v-if="user.status"></div>

                                <!-- 1. Usuário -->
                                <div class="flex-1 min-w-[200px] flex items-center gap-3 truncate">
                                    <div class="w-8 h-8 rounded-lg bg-brand-green/10 border border-brand-green/20 flex items-center justify-center overflow-hidden shadow-sm group-hover:scale-110 transition-transform shrink-0">
                                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full bg-brand-green/10 flex items-center justify-center text-brand-green font-bold text-xs">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                    <span class="ledger-client-name group-hover:text-brand-green transition-colors truncate" :title="user.name">
                                        {{ user.name }}
                                    </span>
                                </div>
                                
                                <!-- 2. Cargo -->
                                <div class="w-48 hidden sm:flex flex-wrap gap-1 items-center">
                                    <span v-for="role in user.roles" :key="role.id" 
                                        class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest border border-slate-200 dark:border-slate-700 rounded-md whitespace-nowrap"
                                    >
                                        {{ role.name }}
                                    </span>
                                    <span v-if="user.roles.length === 0" class="text-slate-400 text-[9px] font-black uppercase tracking-widest">Nenhum</span>
                                </div>

                                <!-- 3. Email -->
                                <div class="w-48 hidden md:flex items-center">
                                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate" :title="user.email">{{ user.email }}</span>
                                </div>

                                <!-- 4. Vínculo -->
                                <div class="w-32 hidden lg:flex justify-center items-center">
                                    <div v-if="user.is_employee" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-green/10 border border-brand-green/20 shadow-sm cursor-default">
                                        <div class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></div>
                                        <span class="text-[9px] font-black text-brand-green uppercase tracking-widest">Funcionário</span>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500"></div>
                                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Apenas Usuário</span>
                                    </div>
                                </div>

                                <!-- 5. Criado em -->
                                <div class="w-32 hidden xl:flex justify-center items-center">
                                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 tracking-wide">
                                        {{ formatDate(user.created_at) }}
                                    </span>
                                </div>

                                <!-- 6. Status -->
                                <div class="w-24 flex justify-center items-center">
                                    <div v-if="can('usuarios.status')" class="flex items-center justify-center gap-2">
                                        <button 
                                            @click="toggleStatus(user)"
                                            :disabled="updatingStatusId === user.id || user.id === authUser.id"
                                            class="relative inline-flex h-5 w-10 items-center rounded-full transition-all duration-300 focus:outline-none"
                                            :class="[
                                                user.status ? 'bg-brand-green shadow-sm shadow-brand-green/30' : 'bg-slate-300 dark:bg-slate-600',
                                                (updatingStatusId === user.id || user.id === authUser.id) ? 'opacity-50 cursor-not-allowed' : '',
                                                updatingStatusId === user.id ? 'cursor-wait' : ''
                                            ]"
                                            :title="user.id === authUser.id ? 'Você não pode desativar seu próprio acesso' : ''"
                                        >
                                            <span 
                                                class="inline-flex h-3 w-3 transform rounded-full bg-white transition-transform duration-300 items-center justify-center shadow-sm"
                                                :class="user.status ? 'translate-x-6' : 'translate-x-1'"
                                            >
                                                <svg v-if="updatingStatusId === user.id" class="animate-spin h-2 w-2 text-brand-green" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </span>
                                        </button>
                                        <span 
                                            class="text-[9px] font-black uppercase tracking-widest w-12 text-left transition-colors duration-300"
                                            :class="user.status ? 'text-brand-green' : 'text-slate-500'"
                                        >
                                            {{ user.status ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- 7. Ações -->
                                <div class="w-32 flex items-center justify-end gap-1.5">
                                    <button 
                                        v-if="can('usuarios.gerenciar')"
                                        @click="impersonate(user)"
                                        :disabled="user.id === authUser.id"
                                        class="p-1.5 rounded-md transition-all"
                                        :class="user.id === authUser.id ? 'opacity-20 cursor-not-allowed text-slate-400' : 'text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10'"
                                        title="Acessar conta deste Usuário"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="can('usuarios.editar')"
                                        @click="openModal(user)"
                                        class="p-1.5 text-slate-400 hover:text-brand-green hover:bg-brand-green/10 rounded-md transition-all"
                                        title="Editar Usuário"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="can('usuarios.editar') && !user.is_employee"
                                        @click="openConvertModal(user)"
                                        class="p-1.5 text-slate-400 hover:text-cyan-500 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 rounded-md transition-all"
                                        title="Tornar Funcionário"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                                        </svg>
                                    </button>
                                    <button 
                                        v-if="can('usuarios.deletar')"
                                        @click="confirmDeleteUser(user)"
                                        :disabled="user.id === authUser.id"
                                        class="p-1.5 rounded-md transition-all"
                                        :class="user.id === authUser.id ? 'opacity-20 cursor-not-allowed text-slate-400' : 'text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10'"
                                        :title="user.id === authUser.id ? 'Você não pode remover seu próprio acesso' : 'Remover Usuário'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="px-8 py-20 text-center text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-sm">
                            Nenhum usuário encontrado no momento.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
            <div class="absolute inset-0" @click="closeModal"></div>
            
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-2xl overflow-hidden animate-slide-up">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-[12px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-0.5">
                                {{ editingUser ? 'Editar Usuário' : 'Novo Usuário' }}
                            </h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                {{ editingUser ? 'Atualização de Cadastro' : 'Novo Acesso ao Sistema' }}
                            </p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <!-- Avatar Picker (Standardized) -->
                    <div class="flex flex-col items-center gap-4 mb-2">
                        <div class="relative group">
                            <input
                                type="file"
                                class="hidden"
                                ref="photoInput"
                                @change="handlePhotoSelection"
                                accept="image/*"
                            />

                            <!-- Photo Display -->
                            <div class="w-24 h-24 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm bg-slate-50 dark:bg-slate-800 flex items-center justify-center relative">
                                <img 
                                    v-if="photoPreview" 
                                    :src="photoPreview" 
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full bg-brand-green/10 flex items-center justify-center text-brand-green font-bold text-3xl">
                                    {{ form.name ? form.name.charAt(0).toUpperCase() : '?' }}
                                </div>
                                
                                <!-- Hover Overlay -->
                                <div 
                                    @click="selectNewPhoto"
                                    class="absolute inset-0 bg-slate-900/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all cursor-pointer backdrop-blur-[2px]"
                                >
                                    <svg class="w-6 h-6 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    </svg>
                                    <span class="text-[8px] font-black text-white uppercase tracking-widest">Alterar</span>
                                </div>
                            </div>
                            <!-- Status Ring -->
                            <div v-if="form.processing" class="absolute -inset-1.5 border-2 border-brand-green border-t-transparent rounded-3xl animate-spin"></div>
                        </div>
                        <InputError class="mt-1" :message="form.errors.photo" />
                    </div>
                    
                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1">Nome Completo</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs"
                            placeholder="Ex: João Silva"
                            required
                        >
                        <div class="h-[14px] mt-1">
                            <div v-if="form.errors.name" class="text-red-500 text-[10px] px-1">{{ form.errors.name }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1">Atribuir Cargos (Permissões)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="role in roles" :key="role.id"
                                @click="() => {
                                    const index = form.roles.indexOf(role.id);
                                    if (index > -1) form.roles.splice(index, 1);
                                    else form.roles.push(role.id);
                                }"
                                class="flex items-center gap-2 p-2.5 rounded-xl border transition-all cursor-pointer"
                                :class="form.roles.includes(role.id) ? 'bg-brand-green/10 border-brand-green/30' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700/50 hover:border-brand-green/30'"
                            >
                                <div class="w-4 h-4 rounded border flex items-center justify-center shrink-0 transition-colors"
                                    :class="form.roles.includes(role.id) ? 'bg-brand-green border-brand-green' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600'"
                                >
                                    <svg v-if="form.roles.includes(role.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight truncate">{{ role.name }}</span>
                            </div>
                        </div>
                        <div class="h-[14px] mt-1">
                            <div v-if="form.errors.roles" class="text-red-500 text-[10px] px-1">{{ form.errors.roles }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1">Endereço de Email</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs"
                            placeholder="email@exemplo.com"
                            required
                        >
                        <div class="h-[14px] mt-1">
                            <div v-if="form.errors.email" class="text-red-500 text-[10px] px-1">{{ form.errors.email }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1">
                            {{ editingUser ? 'Senha (Em branco para manter)' : 'Senha de Acesso' }}
                        </label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs"
                            :placeholder="editingUser ? '••••••••' : 'Mínimo 8 caracteres'"
                            :required="!editingUser"
                        >
                        <div class="h-[14px] mt-1">
                            <div v-if="form.errors.password" class="text-red-500 text-[10px] px-1">{{ form.errors.password }}</div>
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button type="button" @click="closeModal" class="flex-[1] py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl font-bold uppercase text-[10px] tracking-widest transition-colors">
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="flex-[2] py-3 bg-brand-green hover:bg-[#485638] text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-md disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Processando...' : (editingUser ? 'Salvar Alterações' : 'Criar Usuário') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
               <!-- Elite Member Conversion Modal (Optimized) -->
        <div v-if="isConvertModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
            <div class="absolute inset-0" @click="isConvertModalOpen = false"></div>
            
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-2xl overflow-hidden animate-slide-up">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-[12px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-0.5">
                                Vínculo Profissional
                            </h3>
                            <p class="text-[10px] text-brand-green font-bold uppercase tracking-widest flex items-center gap-1.5 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                {{ convertingUser?.name }}
                            </p>
                        </div>
                    </div>
                    <button @click="isConvertModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Balanced Form Section -->
                <form @submit.prevent="submitConversion" class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1">Cargo / Função</label>
                            <div class="relative group">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                                </svg>
                                <input v-model="convertForm.position" type="text" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl pl-11 pr-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs" placeholder="EX: VENDEDOR EXECUTIVO" required>
                            </div>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1 text-center">Salário Base</label>
                            <div class="relative group text-center">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 group-focus-within:text-brand-green transition-colors">R$</span>
                                <input v-model="convertForm.salary" type="number" step="0.01" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl pl-10 pr-4 py-2.5 text-slate-900 dark:text-white text-center font-bold focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs placeholder:text-slate-400" placeholder="0.00" required>
                            </div>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1 text-center">Admissão</label>
                            <input v-model="convertForm.hired_at" type="date" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-center font-bold focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs uppercase cursor-pointer">
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1 text-center">Contrato</label>
                            <select v-model="convertForm.type" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-center font-bold focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs uppercase cursor-pointer">
                                <option value="CLT">CLT</option>
                                <option value="PJ">PJ</option>
                                <option value="Freelancer">FREE</option>
                                <option value="Parceiro">PART</option>
                            </select>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 px-1 text-center">Carga Horária</label>
                            <select v-model="convertForm.workload" class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-center font-bold focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all text-xs uppercase cursor-pointer">
                                <option value="44 Horas">44H/S</option>
                                <option value="40 Horas">40H/S</option>
                                <option value="30 Horas">30H/S</option>
                                <option value="Escala 12x36">12x36</option>
                            </select>
                            <div class="h-[14px]"></div>
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button type="button" @click="isConvertModalOpen = false" class="flex-[1] py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl font-bold uppercase text-[10px] tracking-widest transition-colors">Cancelar</button>
                        <button type="submit" :disabled="convertForm.processing" class="flex-[2] py-3 bg-brand-green hover:bg-[#485638] text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-md disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="convertForm.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ convertForm.processing ? 'Processando...' : 'Confirmar Vínculo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Digital Identity Modal (Cropper) -->
        <Modal :show="cropperModal" @close="closeCropper" max-width="2xl" :z-index="110">
            <div class="p-8 bg-[#0d1117] border border-white/5 rounded-3xl overflow-hidden relative">
                <!-- Tech Background Decoration -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full blur-[100px] pointer-events-none"></div>
                
                <div class="relative flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-white uppercase tracking-tighter">Digital Identity</h2>
                            <p class="text-[10px] text-blue-400/60 font-bold uppercase tracking-widest mt-0.5">Ajuste de Precisão Neural</p>
                        </div>
                    </div>
                    <button @click="closeCropper" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Cropper Container -->
                <div class="relative w-full h-[500px] bg-black/40 rounded-2xl overflow-hidden mb-8 border border-white/5 ring-1 ring-blue-500/10 group">
                    <img id="image-editor" :src="imageToCrop" />
                    
                    <!-- Neural Scanning Effects (Visual Only) -->
                    <div v-show="!isProcessing" class="absolute inset-0 pointer-events-none opacity-40 group-hover:opacity-100 transition-opacity">
                        <!-- Scanning Line -->
                        <div class="absolute left-0 right-0 h-0.5 bg-blue-400 shadow-[0_0_15px_rgba(96,165,250,0.8)] animate-[scan_3s_infinite_linear]"></div>
                        
                        <!-- Brackets -->
                        <div class="absolute top-4 left-4 w-8 h-8 border-t-2 border-l-2 border-blue-500/50 rounded-tl-lg"></div>
                        <div class="absolute top-4 right-4 w-8 h-8 border-t-2 border-r-2 border-blue-500/50 rounded-tr-lg"></div>
                        <div class="absolute bottom-4 left-4 w-8 h-8 border-b-2 border-l-2 border-blue-500/50 rounded-bl-lg"></div>
                        <div class="absolute bottom-4 right-4 w-8 h-8 border-b-2 border-r-2 border-blue-500/50 rounded-br-lg"></div>
                    </div>

                    <!-- Processing Overlay -->
                    <div v-if="isProcessing" class="absolute inset-0 bg-[#0d1117]/90 flex flex-col items-center justify-center z-50 backdrop-blur-md">
                        <div class="w-20 h-20 relative flex items-center justify-center mb-6">
                            <div class="absolute inset-0 border-4 border-blue-500/10 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin shadow-[0_0_20px_rgba(59,130,246,0.4)]"></div>
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-xs font-black text-white uppercase tracking-[0.4em] animate-pulse">Sincronizando...</span>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button 
                        @click="closeCropper"
                        class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 text-gray-500 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all"
                    >
                        Abortar
                    </button>
                    <button 
                        @click="applyCrop"
                        :disabled="isProcessing"
                        class="flex-[2] py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl shadow-blue-500/20 active:scale-95 transition-all group overflow-hidden relative"
                    >
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            Finalizar Scanner
                        </span>
                        <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" max-width="md">
            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] shadow-2xl overflow-hidden animate-slide-up p-6">
                
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-4 shadow-sm">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 tracking-tight">Excluir Usuário?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Você está prestes a remover o usuário <strong class="text-slate-900 dark:text-white">"{{ userToDelete?.name }}"</strong>. Esta ação não pode ser desfeita.
                    </p>

                    <div class="flex w-full gap-3">
                        <button 
                            @click="isDeleteModalOpen = false"
                            class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold uppercase text-[10px] tracking-widest transition-colors shadow-sm"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="executeDeleteUser"
                            class="flex-1 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-md active:scale-95 flex items-center justify-center gap-2"
                        >
                            Sim, Excluir
                        </button>
                    </div>
                </div>

            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes scan {
    0% { top: 0% }
    50% { top: 100% }
    100% { top: 0% }
}

.animate-scan {
    animation: scan 3s infinite linear;
}

:deep(.cropper-view-box),
:deep(.cropper-face) {
    border-radius: 50%;
}

:deep(.cropper-view-box) {
    outline: 2px solid #3b82f6;
    outline-offset: -1px;
}

:deep(.cropper-line),
:deep(.cropper-point) {
    background-color: #3b82f6;
}

:deep(.cropper-bg) {
    background-image: none !important;
    background-color: #000 !important;
}

:deep(.cropper-modal) {
    background-color: rgba(13, 17, 23, 0.8) !important;
    opacity: 0.9 !important;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
