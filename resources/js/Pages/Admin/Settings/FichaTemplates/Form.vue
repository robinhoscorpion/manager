<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Codemirror } from 'vue-codemirror';
import { html } from '@codemirror/lang-html';
import { oneDark } from '@codemirror/theme-one-dark';
import { ref } from 'vue';

const props = defineProps({
    template: Object
});

const form = useForm({
    name: props.template?.name || '',
    is_default: props.template?.is_default || false,
    is_active: props.template?.is_active ?? true,
    content: props.template?.content || ''
});

const extensions = [html(), oneDark];

const submit = () => {
    if (props.template?.id) {
        form.put(route('admin.settings.ficha_templates.update', props.template.id));
    } else {
        form.post(route('admin.settings.ficha_templates.store'));
    }
};
</script>

<template>
    <Head title="Ficha de Atendimento" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Modelo de Ficha de Atendimento</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Edite o HTML do modelo padrão da Ficha de Atendimento</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="submit" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-brand-green hover:bg-brand-green/90 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-brand-green/40 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50">
                        <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="p-4 border-b border-slate-200 dark:border-slate-800">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome do Modelo</label>
                                        <input type="text" v-model="form.name" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-brand-green focus:ring-brand-green" required />
                                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="flex items-center pt-6">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" v-model="form.is_default" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                                            <span class="text-sm text-slate-700 dark:text-slate-300">Definir como Modelo Padrão</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Editor HTML</h3>
                            </div>
                            <div class="h-[600px] w-full">
                                <Codemirror
                                    v-model="form.content"
                                    placeholder="Digite o código HTML aqui..."
                                    :style="{ height: '100%', width: '100%' }"
                                    :autofocus="true"
                                    :indent-with-tab="true"
                                    :tab-size="2"
                                    :extensions="extensions"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-5">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Variáveis Disponíveis</h3>
                            <p class="text-xs text-slate-500 mb-4">Clique na variável para copiar. Utilize estas chaves dentro do HTML para substituir dinamicamente pelos dados da venda.</p>
                            
                            <div class="space-y-2">
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{nome_cliente}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{cpf_cliente}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{email_cliente}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{celular_cliente}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{data_atendimento}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{local_atendimento}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{promotor}}</span>
                                </div>
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                    <span class="text-brand-green" v-pre>{{brindes}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
