<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    cancellations: Object,
});

const viewingCancellation = ref(null);

const viewCancellation = (cancellation) => {
    viewingCancellation.value = cancellation;
};

const closeViewModal = () => {
    viewingCancellation.value = null;
};

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('pt-BR');
};
</script>

<template>
    <Head title="Distratos" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header -->
                <div class="relative overflow-hidden bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-[24px] mb-6 shadow-sm">
                    <!-- Decorativo de Fundo -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-red-500/10 dark:bg-red-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 bg-indigo-500/5 dark:bg-indigo-500/5 rounded-full blur-2xl translate-y-1/2 -translate-x-1/3"></div>
                    
                    <div class="relative px-6 py-6 sm:px-8 sm:py-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-5 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-14 h-14 rounded-[16px] bg-gradient-to-br from-red-500/20 to-red-600/5 dark:from-red-500/20 dark:to-red-500/5 border border-red-500/20 flex items-center justify-center shadow-inner">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1.5 uppercase">Distratos</h2>
                                <p class="text-[10px] sm:text-xs text-slate-500 font-bold uppercase tracking-[0.2em] flex items-center justify-center sm:justify-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)] animate-pulse"></span>
                                    Gestão de Cancelamentos
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link 
                                v-if="can('pos_venda.distratos.gerenciar')"
                                :href="route('after-sales.cancellations.create')"
                                class="group relative px-6 py-3 bg-red-600 hover:bg-red-500 dark:bg-red-500 dark:hover:bg-red-400 text-white rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(239,68,68,0.2)] hover:shadow-[0_0_25px_rgba(239,68,68,0.4)] flex items-center gap-2 overflow-hidden"
                            >
                                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"></div>
                                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="relative z-10">Novo Distrato</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Lista de Distratos -->
                <div class="bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-[24px] shadow-sm overflow-hidden mb-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-black/20 border-b border-slate-100 dark:border-white/5">
                                    <th class="px-6 py-5 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest w-[30%]">Cliente / Contrato</th>
                                    <th class="px-6 py-5 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest w-[25%]">Motivo</th>
                                    <th class="px-6 py-5 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Financeiro</th>
                                    <th class="px-6 py-5 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Data</th>
                                    <th class="px-6 py-5 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                <tr v-for="cancellation in cancellations.data" :key="cancellation.id" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-black/30 border border-slate-200 dark:border-white/5 shadow-inner text-center min-w-[70px]">
                                                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Contrato</span>
                                                <span class="block text-xs font-bold text-slate-900 dark:text-white">#{{ cancellation.proposal?.contract_number }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-tight">{{ cancellation.proposal?.client?.nome || 'Cliente não encontrado' }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                                    {{ cancellation.proposal?.client?.cpf || '-' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-black/30 border border-slate-200 dark:border-white/5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 dark:bg-cyan-500"></span>
                                            <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                                {{ cancellation.reason }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center justify-between gap-4 max-w-[160px]">
                                                <span class="text-[9px] text-slate-400 dark:text-slate-500 uppercase tracking-widest font-bold">Multa</span>
                                                <span class="text-xs font-bold text-red-500">{{ formatCurrency(cancellation.fine_amount) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-4 max-w-[160px]">
                                                <span class="text-[9px] text-slate-400 dark:text-slate-500 uppercase tracking-widest font-bold">Estorno</span>
                                                <span class="text-xs font-bold text-emerald-500">{{ formatCurrency(cancellation.refund_amount) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">{{ formatDate(cancellation.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-slate-400 dark:text-slate-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                <span class="text-[9px] font-medium uppercase tracking-widest truncate max-w-[120px]" :title="cancellation.user?.name">Por: {{ cancellation.user?.name || 'Sistema' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-end gap-3">
                                            <button @click="viewCancellation(cancellation)" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-black/30 border border-slate-200 dark:border-white/5 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-white hover:text-indigo-500 dark:hover:bg-white/10 dark:hover:text-cyan-400 transition-colors shadow-sm" title="Ver Detalhes">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <a :href="route('after-sales.cancellations.pdf', cancellation.id)" target="_blank" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-black/30 border border-slate-200 dark:border-white/5 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-white hover:text-indigo-500 dark:hover:bg-white/10 dark:hover:text-cyan-400 transition-colors shadow-sm" title="Imprimir Termo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            </a>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20 text-red-600 dark:text-red-400 text-[9px] font-bold uppercase tracking-widest shadow-sm">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Cancelado
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="cancellations.data.length === 0">
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="w-20 h-20 bg-slate-50 dark:bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-5 border border-slate-100 dark:border-white/5 shadow-inner">
                                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="1.5"/></svg>
                                        </div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">Nenhum distrato encontrado</p>
                                        <p class="text-xs text-slate-500 font-medium">O histórico de cancelamentos aparecerá aqui.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20" v-if="cancellations.data.length > 0">
                        <Pagination :links="cancellations.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Visualizar Distrato -->
        <Modal :show="viewingCancellation !== null" @close="closeViewModal" maxWidth="2xl">
            <div v-if="viewingCancellation" class="p-6 bg-white dark:bg-[#1a1f2e]">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center border border-red-100 dark:border-red-500/20">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Detalhes do Distrato</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Contrato #{{ viewingCancellation.proposal?.contract_number }}</p>
                        </div>
                    </div>
                    <button @click="closeViewModal" class="text-slate-400 hover:text-slate-500 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Cliente Info -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-black/20 p-4 rounded-xl border border-slate-100 dark:border-white/5">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cliente</span>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ viewingCancellation.proposal?.client?.nome }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">CPF</span>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ viewingCancellation.proposal?.client?.cpf }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">E-mail</span>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ viewingCancellation.proposal?.client?.email || '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Telefone</span>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ viewingCancellation.proposal?.client?.celular1 || '-' }}</span>
                        </div>
                    </div>

                    <!-- Distrato Info -->
                    <div>
                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Motivo do Distrato</span>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-cyan-500/10 border border-indigo-100 dark:border-cyan-500/20 text-indigo-700 dark:text-cyan-400 text-xs font-black uppercase tracking-widest">
                            {{ viewingCancellation.reason }}
                        </div>
                    </div>

                    <div v-if="viewingCancellation.details">
                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Observações / Detalhes</span>
                        <p class="text-sm text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-black/20 p-3 rounded-lg border border-slate-100 dark:border-white/5 leading-relaxed">
                            {{ viewingCancellation.details }}
                        </p>
                    </div>

                    <!-- Financeiro -->
                    <div>
                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Acerto Financeiro</span>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-slate-50 dark:bg-black/20 p-3 rounded-xl border border-slate-100 dark:border-white/5">
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Pago</span>
                                <span class="block text-sm font-black text-slate-900 dark:text-white">{{ formatCurrency(viewingCancellation.total_paid) }}</span>
                            </div>
                            <div class="bg-red-50 dark:bg-red-500/5 p-3 rounded-xl border border-red-100 dark:border-red-500/10">
                                <span class="block text-[9px] font-black text-red-400 dark:text-red-500 uppercase tracking-widest mb-1">Multa Aplicada</span>
                                <span class="block text-sm font-black text-red-600 dark:text-red-400">{{ formatCurrency(viewingCancellation.fine_amount) }}</span>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-500/5 p-3 rounded-xl border border-emerald-100 dark:border-emerald-500/10">
                                <span class="block text-[9px] font-black text-emerald-500 dark:text-emerald-500 uppercase tracking-widest mb-1">Valor do Estorno</span>
                                <span class="block text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(viewingCancellation.refund_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest">Cancelado por: {{ viewingCancellation.user?.name || 'Sistema' }} em {{ formatDate(viewingCancellation.created_at) }}</span>
                        </div>
                        <a :href="route('after-sales.cancellations.pdf', viewingCancellation.id)" target="_blank" class="px-4 py-2 bg-indigo-50 dark:bg-cyan-500/10 hover:bg-indigo-100 dark:hover:bg-cyan-500/20 border border-indigo-100 dark:border-cyan-500/20 rounded-lg text-indigo-600 dark:text-cyan-400 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Gerar PDF
                        </a>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
