<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Senha Atual</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-indigo-500/40 focus:bg-white/[0.06] transition-all"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nova Senha</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-indigo-500/40 focus:bg-white/[0.06] transition-all"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Confirmar Nova Senha</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-6 py-2.5 text-white focus:outline-none focus:border-indigo-500/40 focus:bg-white/[0.06] transition-all"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-6 pt-2">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-10 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.25em] transition-all duration-300 shadow-xl shadow-indigo-500/10 active:scale-95 disabled:opacity-50"
                >
                    Atualizar Senha
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
                        Senha alterada
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

