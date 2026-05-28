<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />

        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-3 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-xs font-semibold leading-6 text-slate-700">E-mail corporativo</label>
                <div class="mt-1">
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm bg-white/50 backdrop-blur-sm transition-all duration-200"
                        placeholder="voce@empresa.com"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs font-semibold leading-6 text-slate-700">Senha</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
                    >
                        Esqueceu?
                    </Link>
                </div>
                <div class="mt-1">
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm bg-white/50 backdrop-blur-sm transition-all duration-200"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="flex items-center pt-1">
                <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 transition-colors cursor-pointer" />
                <label for="remember" class="ml-2 block text-xs leading-6 text-slate-600 cursor-pointer">
                    Manter conectado
                </label>
            </div>

            <div class="pt-3">
                <button
                    type="submit"
                    :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    class="flex w-full justify-center items-center rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-slate-900/20 transition-all duration-200 hover:bg-slate-800 hover:shadow-lg hover:shadow-slate-800/30 active:scale-[0.98]"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-else>Entrar</span>
                </button>
            </div>
            
            <div class="mt-4 text-center text-xs text-slate-500">
                Não possui conta?
                <Link :href="route('register')" class="font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                    Solicitar acesso
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
