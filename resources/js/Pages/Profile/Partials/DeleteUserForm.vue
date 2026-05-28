<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
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
            class="px-8 py-2.5 bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500/20 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] transition-all duration-300 active:scale-95"
        >
            Excluir Conta Permanentemente
        </button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-8 bg-[#0d1117] border border-white/5 rounded-xl overflow-hidden">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-red-500/20 border border-red-500/30 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white uppercase tracking-tight">Confirmar Exclusão</h2>
                        <p class="text-[10px] text-red-400/60 font-bold uppercase tracking-widest mt-1">Esta ação não pode ser desfeita</p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 leading-relaxed mb-6">
                    Uma vez que sua conta for excluída, todos os seus recursos e dados serão permanentemente removidos. Por favor, insira sua senha para confirmar que deseja prosseguir.
                </p>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Sua Senha Atual</label>
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-red-500/40 focus:bg-white/[0.06] transition-all"
                        placeholder="••••••••"
                        @keyup.enter="deleteUser"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-8 flex gap-4">
                    <button 
                        @click="closeModal"
                        class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 text-gray-500 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all"
                    >
                        Cancelar
                    </button>

                    <button
                        @click="deleteUser"
                        :disabled="form.processing"
                        class="flex-[2] py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] transition-all duration-300 shadow-xl shadow-red-500/20 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Processando...' : 'Excluir Definitivamente' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>

