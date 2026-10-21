<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object
});

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};
const canManage = computed(() => can('configuracoes.colunas.gerenciar'));

const form = useForm({
    relationship_field: props.settings?.relationship_field !== false,
});

const submit = () => {
    form.put(route('admin.settings.service_form.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Ficha de Atendimento" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full max-w-5xl mx-auto sm:px-6 lg:px-8 h-auto pt-8">

                <!-- Header -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none">
                    <div class="px-6 py-5 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Ficha de Atendimento</h2>
                            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                Defina como a ficha se comporta
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Opções -->
                <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none overflow-hidden mb-12">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5 shrink-0" :class="{ 'opacity-50 cursor-not-allowed': !canManage }">
                                <input type="checkbox" v-model="form.relationship_field" :disabled="!canManage" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                            </label>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Exibir "Relação com o 1º Titular"</h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    Campo do 2º titular que informa o vínculo dele com o 1º titular (casal, irmãos, pais e filhos, sócios...).
                                </p>

                                <div class="mt-4 grid sm:grid-cols-2 gap-3">
                                    <div class="p-4 rounded-xl border transition-colors" :class="form.relationship_field ? 'border-brand-green/40 bg-brand-green/5' : 'border-slate-200 dark:border-slate-800'">
                                        <div class="text-[11px] font-black uppercase tracking-widest mb-2" :class="form.relationship_field ? 'text-brand-green' : 'text-slate-400'">Ligado</div>
                                        <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc pl-4">
                                            <li>O 2º titular pode ser cônjuge, irmão, filho, sócio etc.</li>
                                            <li>A relação é obrigatória quando houver 2º titular.</li>
                                            <li>O estado civil de cada titular é informado separadamente.</li>
                                        </ul>
                                    </div>
                                    <div class="p-4 rounded-xl border transition-colors" :class="!form.relationship_field ? 'border-amber-400/60 bg-amber-50 dark:bg-amber-500/5' : 'border-slate-200 dark:border-slate-800'">
                                        <div class="text-[11px] font-black uppercase tracking-widest mb-2" :class="!form.relationship_field ? 'text-amber-600' : 'text-slate-400'">Desligado</div>
                                        <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc pl-4">
                                            <li>O campo some da ficha e o 2º titular é tratado como <strong>casal</strong>.</li>
                                            <li>O estado civil do 2º titular <strong>copia automaticamente</strong> o do 1º.</li>
                                            <li>Se não for casal, cadastre somente o 1º titular.</li>
                                            <li>Atendimentos antigos com outra relação mantêm o que foi gravado.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="canManage" class="px-6 sm:px-8 py-4 bg-slate-50/60 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit" :disabled="form.processing || !form.isDirty" class="px-6 py-2.5 text-sm font-semibold text-white bg-brand-green hover:bg-[#485638] rounded-[12px] transition-all shadow-sm disabled:opacity-50">
                            Salvar configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
