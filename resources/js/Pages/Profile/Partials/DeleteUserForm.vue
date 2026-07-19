<script setup>
import DangerButton from '@/Components/DangerButton.vue';

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <button 
            @click="confirmUserDeletion"
            class="px-8 py-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-500 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-[12px] font-bold uppercase text-[10px] tracking-widest transition-all shadow-sm active:scale-95"
        >
            Excluir Conta Permanentemente
        </button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-8 bg-white dark:bg-[#0f1219] rounded-[20px] shadow-2xl relative">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-[14px] bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Confirmar Exclusão</h2>
                        <p class="text-[10px] text-red-600 dark:text-red-400 font-bold uppercase tracking-widest mt-1">Esta ação não pode ser desfeita</p>
                    </div>
                </div>

                <p class="text-sm text-slate-500 leading-relaxed mb-6">
                    Uma vez que sua conta for excluída, todos os seus recursos e dados serão permanentemente removidos. Por favor, insira sua senha para confirmar que deseja prosseguir.
                </p>

                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1">Sua Senha Atual</label>
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-red-500/40 focus:ring-1 focus:ring-red-500/40 transition-all shadow-sm"
                        placeholder="••••••••"
                        @keyup.enter="deleteUser"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-8 flex gap-4">
                    <button 
                        @click="closeModal"
                        class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-[12px] font-bold uppercase text-[10px] tracking-widest transition-all shadow-sm"
                    >
                        Cancelar
                    </button>

                    <button
                        @click="deleteUser"
                        :disabled="form.processing"
                        class="flex-[2] py-3 bg-red-600 hover:bg-red-500 text-white rounded-[12px] font-bold uppercase text-[10px] tracking-widest shadow-sm active:scale-95 transition-all flex items-center justify-center disabled:opacity-50"
                    >
                        {{ form.processing ? 'Processando...' : 'Excluir Definitivamente' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>

