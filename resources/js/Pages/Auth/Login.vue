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



        <div v-if="status" class="mb-6 rounded-xl bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <fieldset class="rounded-xl border-2 border-slate-200 px-3 pb-2 pt-0 transition-colors duration-200 bg-white focus-within:border-indigo-600">
                    <legend class="px-1 text-xs font-medium text-slate-700">Endereço de e-mail</legend>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="block w-full border-0 p-0 text-slate-900 placeholder:text-slate-400 focus:ring-0 focus:outline-none sm:text-sm sm:leading-6 bg-transparent"
                        placeholder="voce@exemplo.com"
                        style="box-shadow: none;"
                    />
                </fieldset>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <fieldset class="rounded-xl border-2 border-slate-200 px-3 pb-2 pt-0 transition-colors duration-200 bg-white focus-within:border-indigo-600">
                    <legend class="px-1 text-xs font-medium text-slate-700">Senha</legend>
                    <input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="block w-full border-0 p-0 text-slate-900 placeholder:text-slate-400 focus:ring-0 focus:outline-none sm:text-sm sm:leading-6 bg-transparent"
                        placeholder="••••••••"
                        style="box-shadow: none;"
                    />
                </fieldset>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600" />
                    <label for="remember" class="ml-3 block text-sm leading-6 text-slate-500">
                        Lembrar de mim
                    </label>
                </div>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors"
                >
                    Esqueceu a senha?
                </Link>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                    class="flex w-full justify-center items-center rounded-xl px-3 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 active:scale-[0.98] hover:opacity-90 bg-indigo-600"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Entrar
                </button>
            </div>
            
            <div class="mt-6 text-center text-sm text-slate-500">
                Não tem uma conta?
                <Link :href="route('register')" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                    Cadastre-se
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
