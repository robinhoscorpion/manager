<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    season: {
        type: Object,
        default: null
    }
});

const isEditing = !!props.season;

const form = useForm({
    name: props.season?.name || '',
    advance_days: props.season?.advance_days || 0,
    period_description: props.season?.period_description || '',
});

const submit = () => {
    if (isEditing) {
        form.put(route('admin.seasons.update', props.season.id));
    } else {
        form.post(route('admin.seasons.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Editar Temporada' : 'Nova Temporada'" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12">
                
                <!-- Premium Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <Link :href="route('admin.seasons.index')" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            </Link>
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">
                                    {{ isEditing ? 'Editar Temporada' : 'Nova Temporada' }}
                                </h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Configuração de Períodos
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Panel -->
                <div class="max-w-3xl">
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <form @submit.prevent="submit" class="p-6 md:p-8 space-y-6">
                            
                            <div>
                                <label for="name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Nome da Temporada</label>
                                <input
                                    id="name"
                                    type="text"
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all uppercase"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="Ex: Alta Temporada"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <label for="advance_days" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Antecedência (Em dias)</label>
                                <input
                                    id="advance_days"
                                    type="number"
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                                    v-model="form.advance_days"
                                    required
                                    min="0"
                                />
                                <p class="text-xs text-slate-400 mt-1.5 px-1 font-medium">Quantos dias de antecedência para esta tabela valer (ex: 30, 60).</p>
                                <InputError class="mt-2" :message="form.errors.advance_days" />
                            </div>

                            <div>
                                <label for="period_description" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Período (Descrição em texto)</label>
                                <textarea
                                    id="period_description"
                                    class="w-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400 outline-none shadow-sm transition-all resize-y uppercase cursor-not-allowed opacity-80"
                                    v-model="form.period_description"
                                    rows="3"
                                    readonly
                                    placeholder="Preenchido automaticamente via mapeamento"
                                ></textarea>
                                <p class="text-xs text-brand-green/80 mt-1.5 px-1 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Este campo é preenchido automaticamente ao realizar o Mapeamento.
                                </p>
                                <InputError class="mt-2" :message="form.errors.period_description" />
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800 pt-6 mt-8">
                                <Link :href="route('admin.seasons.index')" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm">
                                    Cancelar
                                </Link>
                                <button type="submit" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="px-6 py-2.5 bg-brand-green hover:bg-[#485638] text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm hover:shadow-md active:scale-[0.98] flex items-center gap-2">
                                    <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    {{ isEditing ? 'Salvar Alterações' : 'Criar Temporada' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
