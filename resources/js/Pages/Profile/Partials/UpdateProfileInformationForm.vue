<script setup>
import { ref, onUnmounted } from 'vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const photoInput = ref(null);
const photoPreview = ref(null);
const cropperModal = ref(false);
const imageToCrop = ref(null);
const cropper = ref(null);
const isProcessing = ref(false);

const form = useForm({
    _method: 'PATCH',
    name: user.name,
    email: user.email,
    profile_photo: null,
});

const updateProfileInformation = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoInput();
        },
    });
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const handlePhotoSelection = (e) => {
    const file = e.target.result || e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        imageToCrop.value = event.target.result;
        cropperModal.value = true;
        // Initialize cropper after modal opens
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
                    // Custom styling for cropper
                    const container = document.querySelector('.cropper-container');
                    container.style.borderRadius = '24px';
                    
                    // Force a recalculation to ensure full fill
                    setTimeout(() => {
                        if (cropper.value) {
                            cropper.value.resize();
                        }
                    }, 50);
                }
            });
        }, 300);
    };
    reader.readAsDataURL(file);
};

const applyCrop = () => {
    isProcessing.value = true;
    
    // Slight delay for "neural processing" effect
    setTimeout(() => {
        const canvas = cropper.value.getCroppedCanvas({
            width: 512,
            height: 512,
        });

        canvas.toBlob((blob) => {
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
            form.profile_photo = file;
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
};

const clearPhotoInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

onUnmounted(() => {
    if (cropper.value) cropper.value.destroy();
});
</script>

<template>
    <section>
        <form
            @submit.prevent="updateProfileInformation"
            class="space-y-6"
        >
            <!-- Avatar Picker -->
            <div class="flex flex-col items-center gap-6 mb-8">
                <div class="relative group">
                    <input
                        type="file"
                        class="hidden"
                        ref="photoInput"
                        @change="handlePhotoSelection"
                        accept="image/*"
                    />

                    <!-- Current Photo or Preview -->
                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white/5 shadow-2xl bg-white/[0.02] flex items-center justify-center relative">
                        <img 
                            v-if="photoPreview" 
                            :src="photoPreview" 
                            class="w-full h-full object-cover"
                        />
                        <img 
                            v-else 
                            :src="user.profile_photo_url" 
                            class="w-full h-full object-cover"
                        />
                        
                        <!-- Hover Overlay -->
                        <div 
                            @click="selectNewPhoto"
                            class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all cursor-pointer backdrop-blur-[2px]"
                        >
                            <svg class="w-6 h-6 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-[8px] font-black text-white uppercase tracking-widest">Neural Scan</span>
                        </div>
                    </div>

                    <!-- Status Ring -->
                    <div v-if="form.processing" class="absolute -inset-2 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                
                <InputError class="mt-2" :message="form.errors.profile_photo" />
            </div>

            <!-- Neural Cropper Modal -->
            <Modal :show="cropperModal" @close="closeCropper" max-width="2xl">
                <div class="p-8 bg-[#0d1117] border border-white/5 rounded-xl overflow-hidden relative">
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
                    <div class="relative w-full h-[500px] bg-black/40 rounded-xl overflow-hidden mb-8 border border-white/5 ring-1 ring-blue-500/10 group">
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

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nome de Exibição</label>
                <input
                    id="name"
                    type="text"
                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-blue-500/40 focus:bg-white/[0.06] transition-all"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Endereço de E-mail</label>
                <input
                    id="email"
                    type="email"
                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-blue-500/40 focus:bg-white/[0.06] transition-all"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-[10px] font-bold text-red-400 uppercase tracking-wider">
                    Seu e-mail não foi verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="ml-2 underline text-white hover:text-blue-400 transition-colors"
                    >
                        Re-enviar link de verificação.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-[10px] font-black text-green-400 uppercase tracking-widest"
                >
                    Um novo link foi enviado para seu e-mail.
                </div>
            </div>

            <div class="flex items-center gap-6 pt-2">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-10 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.25em] transition-all duration-300 shadow-xl shadow-blue-500/10 active:scale-95 disabled:opacity-50"
                >
                    Salvar Alterações
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-[10px] font-black text-green-400 uppercase tracking-widest flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        Atualizado com sucesso
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style scoped>
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
</style>
