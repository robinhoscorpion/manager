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

const deleteUser = (user) => {
    if (confirm(`Tem certeza que deseja remover o usuário ${user.name}?`)) {
        router.delete(route('users.destroy', user.id));
    }
};

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
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
</script>

<template>
    <Head title="Usuários do Sistema" />

    <AuthenticatedLayout>


        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Premium Header Aligned with Table -->
                <div class="relative bg-white/[0.02] backdrop-blur-3xl border border-white/5 px-6 py-4 rounded-xl mb-6 shadow-2xl group transition-all duration-500 z-20">
                    <!-- Decorative background glow container (Nested to allow dropdowns to escape) -->
                    <div class="absolute inset-0 overflow-hidden rounded-xl pointer-events-none">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 rounded-full blur-[100px] group-hover:bg-blue-500/15 transition-all duration-700"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px] group-hover:bg-indigo-500/15 transition-all duration-700"></div>
                    </div>

                    <div class="relative flex flex-col lg:flex-row items-center lg:items-center justify-between gap-4 sm:gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-3 shrink-0 w-full lg:w-auto justify-center lg:justify-start">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500/20 to-indigo-500/20 border border-white/10 flex items-center justify-center shadow-lg">
                                <svg class="w-4.5 h-4.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-center lg:text-left">
                                <h2 class="text-lg font-black text-white uppercase tracking-tighter leading-none">Usuários</h2>
                                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-1 flex items-center justify-center lg:justify-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-blue-500 animate-pulse"></span>
                                    Controle de Acessos
                                </p>
                            </div>
                        </div>

                        <!-- Actions & Search Hub -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <!-- Search Bar -->
                            <div class="relative group/search flex-1 sm:w-64">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within/search:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input 
                                    type="text" 
                                    placeholder="BUSCAR USUÁRIO..." 
                                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl pl-11 pr-4 py-2.5 text-[9px] font-bold text-white uppercase tracking-widest placeholder:text-gray-600 focus:outline-none focus:border-blue-500/40 focus:bg-white/[0.05] transition-all"
                                >
                            </div>

                            <!-- Deluxe Separator -->
                            <div class="hidden lg:block w-[1px] h-6 bg-white/10 mx-1"></div>

                            <!-- Add Button -->
                            <button 
                                v-if="can('usuarios.criar')"
                                @click="openModal()"
                                class="group relative px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-black uppercase text-[9px] tracking-[0.2em] transition-all duration-300 shadow-xl shadow-blue-500/10 hover:shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-3 shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Novo Usuário
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Table -->
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px] sm:min-w-0">
                        <thead>
                            <tr class="bg-white/[0.03] border-b border-white/5">
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">Usuário</th>
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden sm:table-cell">Cargo</th>
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden md:table-cell">Email</th>
                                <th class="px-5 py-3 text-[9px] font-black text-center text-gray-400 uppercase tracking-[0.2em] hidden lg:table-cell">Vínculo</th>
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden xl:table-cell">Criado em</th>
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
                                <th class="px-5 py-3 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="user in users" :key="user.id" 
                                class="group transition-all duration-300"
                                :class="user.status ? 'hover:bg-white/[0.02]' : 'opacity-40 grayscale-[50%] hover:opacity-60 bg-black/20'"
                            >
                                <td class="px-5 py-2.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg border border-white/10 flex items-center justify-center overflow-hidden shadow-lg group-hover:scale-110 transition-transform bg-white/5">
                                            <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full bg-gradient-to-br from-blue-500/10 to-indigo-500/10 flex items-center justify-center text-blue-400 font-bold text-xs">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <span class="text-white font-bold tracking-tight uppercase text-xs group-hover:text-blue-400 transition-colors line-clamp-1">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 hidden sm:table-cell">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="role in user.roles" :key="role.id" 
                                            class="px-1.5 py-0.5 bg-blue-500/10 border border-blue-500/20 rounded text-[8px] font-black text-blue-400 uppercase tracking-widest whitespace-nowrap"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-gray-600 text-[8px] font-black uppercase tracking-widest">Nenhum</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 hidden md:table-cell">
                                    <span class="text-gray-400 font-medium tracking-tight text-xs">{{ user.email }}</span>
                                </td>
                                <td class="px-5 py-2.5 hidden lg:table-cell">
                                    <div class="flex justify-center">
                                        <div v-if="user.is_employee" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 group/badge hover:bg-cyan-500/20 transition-all cursor-default shadow-[0_0_15px_-5px_rgba(6,182,212,0.3)]">
                                            <div class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></div>
                                            <span class="text-[9px] font-black text-cyan-400 uppercase tracking-widest">Funcionário</span>
                                        </div>
                                        <div v-else class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-500/5 border border-white/5 opacity-40 grayscale group/badge">
                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-500"></div>
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Apenas Usuário</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5">
                                    <div v-if="can('usuarios.status')" class="flex items-center justify-center gap-3">
                                        <button 
                                            @click="toggleStatus(user)"
                                            :disabled="updatingStatusId === user.id || user.id === authUser.id"
                                            class="relative inline-flex h-5 w-10 items-center rounded-full transition-all duration-300 focus:outline-none"
                                            :class="[
                                                user.status ? 'bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.3)]' : 'bg-gray-600',
                                                (updatingStatusId === user.id || user.id === authUser.id) ? 'opacity-50 cursor-not-allowed' : '',
                                                updatingStatusId === user.id ? 'cursor-wait' : ''
                                            ]"
                                            :title="user.id === authUser.id ? 'Você não pode desativar seu próprio acesso' : ''"
                                        >
                                            <span 
                                                class="inline-flex h-3 w-3 transform rounded-full bg-white transition-transform duration-300 items-center justify-center"
                                                :class="user.status ? 'translate-x-6' : 'translate-x-1'"
                                            >
                                                <svg v-if="updatingStatusId === user.id" class="animate-spin h-2 w-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </span>
                                        </button>
                                        <span 
                                            class="text-[9px] font-black uppercase tracking-widest w-12 text-left transition-colors duration-300"
                                            :class="user.status ? 'text-green-400' : 'text-gray-500'"
                                        >
                                            {{ user.status ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 text-gray-500 font-medium text-xs hidden xl:table-cell">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-5 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            v-if="can('usuarios.editar')"
                                            @click="openModal(user)"
                                            class="p-1.5 text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 rounded-md transition-all"
                                            title="Editar Usuário"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            v-if="can('usuarios.editar') && !user.is_employee"
                                            @click="openConvertModal(user)"
                                            class="p-1.5 text-gray-500 hover:text-cyan-400 hover:bg-cyan-500/10 rounded-md transition-all"
                                            title="Tornar Funcionário"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                                            </svg>
                                        </button>
                                        <button 
                                            v-if="can('usuarios.deletar')"
                                            @click="deleteUser(user)"
                                            :disabled="user.id === authUser.id"
                                            class="p-1.5 rounded-md transition-all"
                                            :class="user.id === authUser.id ? 'opacity-20 cursor-not-allowed text-gray-600' : 'text-gray-500 hover:text-red-400 hover:bg-red-500/10'"
                                            :title="user.id === authUser.id ? 'Você não pode remover seu próprio acesso' : 'Remover Usuário'"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] border border-white/10 rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
                <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">
                        {{ editingUser ? 'Editar Usuário' : 'Novo Usuário' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-5">
                    <!-- Avatar Picker (Standardized) -->
                    <div class="flex flex-col items-center gap-4 mb-4">
                        <div class="relative group">
                            <input
                                type="file"
                                class="hidden"
                                ref="photoInput"
                                @change="handlePhotoSelection"
                                accept="image/*"
                            />

                            <!-- Photo Display -->
                            <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-white/10 shadow-2xl bg-white/[0.02] flex items-center justify-center relative">
                                <img 
                                    v-if="photoPreview" 
                                    :src="photoPreview" 
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full bg-gradient-to-br from-blue-500/10 to-indigo-500/10 flex items-center justify-center text-blue-400 font-bold text-3xl">
                                    {{ form.name ? form.name.charAt(0).toUpperCase() : '?' }}
                                </div>
                                
                                <!-- Hover Overlay -->
                                <div 
                                    @click="selectNewPhoto"
                                    class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all cursor-pointer backdrop-blur-[2px]"
                                >
                                    <svg class="w-6 h-6 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-[8px] font-black text-white uppercase tracking-widest">Scanner</span>
                                </div>
                            </div>
                            <!-- Status Ring -->
                            <div v-if="form.processing" class="absolute -inset-1.5 border-2 border-blue-500 border-t-transparent rounded-3xl animate-spin"></div>
                        </div>
                        <InputError class="mt-1" :message="form.errors.photo" />
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 px-1 h-[11px]">Nome Completo</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-1.5 h-[38px] text-white focus:outline-none focus:border-blue-500/50 focus:bg-white/[0.08] transition-all text-sm"
                            placeholder="Ex: João Silva"
                            required
                        >
                        <div class="h-[14px]">
                            <div v-if="form.errors.name" class="text-red-400 text-[10px] px-1">{{ form.errors.name }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 px-1 h-[11px]">Atribuir Cargos (Permissões)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="role in roles" :key="role.id"
                                @click="() => {
                                    const index = form.roles.indexOf(role.id);
                                    if (index > -1) form.roles.splice(index, 1);
                                    else form.roles.push(role.id);
                                }"
                                class="flex items-center gap-2 p-2 rounded-xl border transition-all cursor-pointer h-[38px]"
                                :class="form.roles.includes(role.id) ? 'bg-blue-500/10 border-blue-500/30' : 'bg-white/5 border-white/5 hover:border-white/10'"
                            >
                                <div class="w-3.5 h-3.5 rounded border flex items-center justify-center shrink-0"
                                    :class="form.roles.includes(role.id) ? 'bg-blue-500 border-blue-400' : 'bg-white/5 border-white/10'"
                                >
                                    <svg v-if="form.roles.includes(role.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[9px] font-black text-white uppercase tracking-tight truncate">{{ role.name }}</span>
                            </div>
                        </div>
                        <div class="h-[14px]">
                            <div v-if="form.errors.roles" class="text-red-400 text-[10px] px-1">{{ form.errors.roles }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 px-1 h-[11px]">Endereço de Email</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-1.5 h-[38px] text-white focus:outline-none focus:border-blue-500/50 focus:bg-white/[0.08] transition-all text-sm"
                            placeholder="email@exemplo.com"
                            required
                        >
                        <div class="h-[14px]">
                            <div v-if="form.errors.email" class="text-red-400 text-[10px] px-1">{{ form.errors.email }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 px-1 h-[11px]">
                            {{ editingUser ? 'Senha (Em branco para manter)' : 'Senha de Acesso' }}
                        </label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-1.5 h-[38px] text-white focus:outline-none focus:border-blue-500/50 focus:bg-white/[0.08] transition-all text-sm"
                            :placeholder="editingUser ? '••••••••' : 'Mínimo 8 caracteres'"
                            :required="!editingUser"
                        >
                        <div class="h-[14px]">
                            <div v-if="form.errors.password" class="text-red-400 text-[10px] px-1">{{ form.errors.password }}</div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-2xl font-black uppercase text-sm tracking-[0.25em] transition-all duration-300 shadow-xl shadow-blue-500/20 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Processando...' : (editingUser ? 'Salvar Alterações' : 'Criar Usuário') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Elite Member Conversion Modal (Optimized) -->
        <div v-if="isConvertModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="isConvertModalOpen = false"></div>
            
            <div class="relative w-full max-w-lg bg-[#0d1117]/95 border border-white/10 rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
                <!-- High-Intensity Header -->
                <div class="bg-gradient-to-r from-cyan-600/10 to-transparent px-8 py-6 border-b border-white/5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4">
                        <button @click="isConvertModalOpen = false" class="text-gray-600 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center shadow-inner">
                            <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tighter leading-none">Vínculo Profissional</h3>
                            <p class="text-[9px] text-cyan-400/60 font-bold uppercase tracking-[0.3em] mt-1.5 flex items-center gap-2">
                                <span class="w-1 h-1 rounded-full bg-cyan-500 animate-pulse"></span>
                                {{ convertingUser?.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Balanced Form Section -->
                <form @submit.prevent="submitConversion" class="p-8 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col col-span-2">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 mb-1 h-[11px]">Cargo / Função</label>
                            <div class="relative group">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600 group-focus-within:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h3m10 0v-3a2 2 0 012-2h3" />
                                </svg>
                                <input v-model="convertForm.position" type="text" class="w-full bg-white/[0.03] border border-white/5 rounded-xl pl-11 pr-5 py-1.5 h-[38px] text-[11px] text-white placeholder:text-gray-700 focus:outline-none focus:border-cyan-500/30 focus:bg-white/[0.06] transition-all" placeholder="EX: VENDEDOR EXECUTIVO" required>
                            </div>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 text-center mb-1 h-[11px]">Salário Base</label>
                            <div class="relative group text-center">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-600 group-focus-within:text-cyan-400 transition-colors">R$</span>
                                <input v-model="convertForm.salary" type="number" step="0.01" class="w-full bg-white/[0.03] border border-white/5 rounded-xl pl-10 pr-4 py-1.5 h-[38px] text-[11px] text-white text-center font-bold focus:outline-none focus:border-cyan-500/30 transition-all placeholder:text-gray-700" placeholder="0.00" required>
                            </div>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 text-center mb-1 h-[11px]">Admissão</label>
                            <input v-model="convertForm.hired_at" type="date" class="w-full bg-white/[0.03] border border-white/5 rounded-xl px-4 py-1.5 h-[38px] text-[11px] text-white text-center font-bold focus:outline-none focus:border-cyan-500/30 transition-all uppercase appearance-none cursor-pointer">
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 text-center mb-1 h-[11px]">Contrato</label>
                            <select v-model="convertForm.type" class="w-full bg-white/[0.03] border border-white/5 rounded-xl px-4 py-1.5 h-[38px] text-[11px] text-white text-center font-bold focus:outline-none focus:border-cyan-500/30 transition-all uppercase appearance-none cursor-pointer">
                                <option value="CLT">CLT</option>
                                <option value="PJ">PJ</option>
                                <option value="Freelancer">FREE</option>
                                <option value="Parceiro">PART</option>
                            </select>
                            <div class="h-[14px]"></div>
                        </div>

                        <div class="flex flex-col">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 text-center mb-1 h-[11px]">Carga Horária</label>
                            <select v-model="convertForm.workload" class="w-full bg-white/[0.03] border border-white/5 rounded-xl px-4 py-1.5 h-[38px] text-[11px] text-white text-center font-bold focus:outline-none focus:border-cyan-500/30 transition-all uppercase appearance-none cursor-pointer">
                                <option value="44 Horas">44H/S</option>
                                <option value="40 Horas">40H/S</option>
                                <option value="30 Horas">30H/S</option>
                                <option value="Escala 12x36">12x36</option>
                            </select>
                            <div class="h-[14px]"></div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="isConvertModalOpen = false" class="flex-1 py-4 bg-white/5 hover:bg-white/10 text-gray-500 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all">Cancelar</button>
                        <button type="submit" :disabled="convertForm.processing" class="flex-[2] py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] transition-all duration-300 shadow-xl shadow-cyan-500/10 disabled:opacity-50">
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
