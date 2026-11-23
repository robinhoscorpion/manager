<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    amendments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const type = ref(props.filters?.type || '');

const can = (permission) => {
    const auth = usePage().props.auth;
    return auth.permissions.includes(permission) || auth.roles.includes('admin');
};

let timer;
const applyFilters = () => {
    router.get(route('after-sales.contract-amendments.index'), {
        search: search.value || undefined,
        type: type.value || undefined,
    }, { preserveState: true, replace: true });
};
watch(search, () => { clearTimeout(timer); timer = setTimeout(applyFilters, 400); });
watch(type, applyFilters);

const formatCurrency = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
const formatDate = (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '-';
</script>

<template>
    <Head title="Upgrade / Downgrade" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-16">

                <div class="relative overflow-hidden bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-[24px] mb-6 shadow-sm">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 dark:bg-cyan-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                    <div class="relative px-6 py-6 sm:px-8 sm:py-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-[16px] bg-gradient-to-br from-indigo-500/20 to-indigo-600/5 border border-indigo-500/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1.5 uppercase">Upgrade / Downgrade</h2>
                                <p class="text-[10px] sm:text-xs text-slate-500 font-bold uppercase tracking-[0.2em]">Aditivos de contrato · mesmo número, histórico preservado</p>
                            </div>
                        </div>
                        <Link v-if="can('pos_venda.alteracao_contrato.gerenciar')" :href="route('after-sales.contract-amendments.create')"
                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 dark:bg-cyan-600 dark:hover:bg-cyan-500 text-white rounded-xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                            Novo Aditivo
                        </Link>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-[20px] shadow-sm overflow-hidden">
                    <div class="p-4 flex flex-col sm:flex-row gap-3 border-b border-slate-100 dark:border-white/5">
                        <input v-model="search" type="text" placeholder="Contrato, cliente ou CPF..."
                            class="flex-1 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white">
                        <select v-model="type" class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white">
                            <option value="">Todos</option>
                            <option value="upgrade">Upgrade</option>
                            <option value="downgrade">Downgrade</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-500 bg-slate-50 dark:bg-black/20">
                                    <th class="px-5 py-3">Data</th>
                                    <th class="px-5 py-3">Contrato</th>
                                    <th class="px-5 py-3">Cliente</th>
                                    <th class="px-5 py-3">Tipo</th>
                                    <th class="px-5 py-3">Produto</th>
                                    <th class="px-5 py-3 text-right">Valor anterior → novo</th>
                                    <th class="px-5 py-3 text-right">Saldo credor</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="a in amendments.data" :key="a.id" class="border-t border-slate-100 dark:border-white/5 text-slate-700 dark:text-slate-300">
                                    <td class="px-5 py-3 whitespace-nowrap">{{ formatDate(a.created_at) }}</td>
                                    <td class="px-5 py-3 font-bold whitespace-nowrap">#{{ a.proposal?.contract_number }} <span class="text-[10px] text-slate-400">v{{ a.from_version }}→v{{ a.to_version }}</span></td>
                                    <td class="px-5 py-3">{{ a.proposal?.client?.nome }}</td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase"
                                            :class="a.type === 'upgrade' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'">
                                            {{ a.type_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-xs">{{ a.from_product?.name }} → <b>{{ a.to_product?.name }}</b></td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">{{ formatCurrency(a.old_total_value) }} → <b>{{ formatCurrency(a.new_total_value) }}</b></td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap" :class="Number(a.credit_balance) > 0 ? 'text-amber-600 font-bold' : 'text-slate-400'">{{ formatCurrency(a.credit_balance) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <Link :href="route('after-sales.contract-amendments.show', a.id)" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-cyan-400 hover:underline">Ver espelho</Link>
                                    </td>
                                </tr>
                                <tr v-if="!amendments.data.length">
                                    <td colspan="8" class="px-5 py-10 text-center text-slate-400 text-sm">Nenhum aditivo registrado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4"><Pagination :links="amendments.links" /></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
