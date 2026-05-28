<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const eventFilter = ref(props.filters.event);

watch([search, eventFilter], debounce(() => {
    router.get(route('admin.audit-logs.index'), { 
        search: search.value, 
        event: eventFilter.value 
    }, { 
        preserveState: true, 
        replace: true 
    });
}, 300));

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getEventColor = (event) => {
    switch (event) {
        case 'created': return 'text-green-400 bg-green-500/10 border-green-500/20';
        case 'updated': return 'text-blue-400 bg-blue-500/10 border-blue-500/20';
        case 'deleted': return 'text-red-400 bg-red-500/10 border-red-500/20';
        default: return 'text-gray-400 bg-gray-500/10 border-gray-500/20';
    }
};

const getModelName = (type) => {
    if (!type) return 'N/A';
    return type.split('\\').pop();
};

const selectedLog = ref(null);

const formatJson = (json) => {
    if (!json) return null;
    return typeof json === 'string' ? JSON.parse(json) : json;
};
</script>

<template>
    <Head title="Logs do Sistema" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Premium Header -->
                <div class="relative bg-white/[0.02] backdrop-blur-3xl border border-white/5 px-6 py-4 rounded-xl mb-6 shadow-2xl group transition-all duration-500">
                    <div class="absolute inset-0 overflow-hidden rounded-xl pointer-events-none">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-amber-500/5 rounded-full blur-[100px]"></div>
                    </div>

                    <div class="relative flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-white uppercase tracking-tighter leading-none">Logs do Sistema</h2>
                                <p class="text-[10px] text-amber-400/60 font-bold uppercase tracking-[0.3em] mt-1">Auditoria e Rastreamento</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                            <select v-model="eventFilter" class="bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2 text-[10px] font-black text-white uppercase tracking-widest focus:outline-none focus:border-amber-500/40 transition-all">
                                <option value="">Todos Eventos</option>
                                <option value="created">Criação</option>
                                <option value="updated">Edição</option>
                                <option value="deleted">Exclusão</option>
                            </select>

                            <div class="relative group/search">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within/search:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="BUSCAR LOG..." 
                                    class="w-full sm:w-64 bg-white/[0.03] border border-white/10 rounded-xl pl-11 pr-4 py-2.5 text-[10px] font-bold text-white uppercase tracking-widest placeholder:text-gray-600 focus:outline-none focus:border-amber-500/40 transition-all"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="bg-white/[0.02] backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/[0.03] border-b border-white/5">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Responsável</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Ação</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Módulo</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Data / Hora</th>
                                <th class="px-6 py-4 text-[10px] font-black text-right text-gray-400 uppercase tracking-widest">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="log in logs.data" :key="log.id" class="group hover:bg-white/[0.02] transition-colors border-b border-white/5">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-[10px] font-black text-gray-400 uppercase">
                                            {{ log.user ? log.user.name.charAt(0) : 'S' }}
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold text-white uppercase tracking-tight">{{ log.user ? log.user.name : 'Sistema' }}</div>
                                            <div class="text-[9px] text-gray-500 font-medium lowercase tracking-normal">{{ log.ip_address }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border', getEventColor(log.event)]">
                                        {{ log.event }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-black text-gray-300 uppercase tracking-tighter">{{ getModelName(log.auditable_type) }}</span>
                                        <span class="text-[9px] text-gray-600 font-bold">ID: #{{ log.auditable_id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[11px] font-medium text-gray-400 tabular-nums">{{ formatDate(log.created_at) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="selectedLog = log"
                                        class="p-2 text-gray-500 hover:text-amber-400 hover:bg-amber-500/10 rounded-lg transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="logs.data.length === 0" class="py-20 flex flex-col items-center justify-center">
                        <div class="w-20 h-20 rounded-full bg-white/5 flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em]">Nenhum registro encontrado</span>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links.length > 3" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-for="(link, i) in logs.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="[
                            link.active ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20' : 'bg-white/5 text-gray-500 hover:bg-white/10',
                            !link.url ? 'opacity-20 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="selectedLog" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-lg" @click="selectedLog = null"></div>
            
            <div class="relative w-full max-w-2xl bg-[#0d1117] border border-white/10 rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
                <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div :class="['w-10 h-10 rounded-xl border flex items-center justify-center shadow-lg', getEventColor(selectedLog.event)]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white uppercase tracking-tighter">Detalhes do Evento</h3>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Track ID #{{ selectedLog.id }}</p>
                        </div>
                    </div>
                    <button @click="selectedLog = null" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 max-h-[60vh] overflow-y-auto no-scrollbar">
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest mb-2">Responsável</span>
                            <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-black">{{ selectedLog.user ? selectedLog.user.name.charAt(0) : 'S' }}</div>
                                <span class="text-xs font-bold text-white uppercase">{{ selectedLog.user ? selectedLog.user.name : 'Sistema' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest mb-2">Data e Hora</span>
                            <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/5">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-xs font-bold text-white tabular-nums">{{ formatDate(selectedLog.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Value Diff Section -->
                    <div v-if="selectedLog.event === 'updated'" class="space-y-4">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest mb-2">Diferencial de Alterações</span>
                        <div class="grid gap-2">
                            <div v-for="(value, key) in formatJson(selectedLog.new_values)" :key="key" class="bg-white/[0.02] border border-white/5 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">{{ key }}</span>
                                    <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 text-[8px] font-black uppercase rounded">Alterado</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">Valor Anterior</span>
                                        <div class="p-2 bg-red-500/5 border border-red-500/10 rounded-lg text-[11px] text-red-400/80 break-words font-medium">
                                            {{ formatJson(selectedLog.old_values)[key] ?? 'Inexistente' }}
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">Novo Valor</span>
                                        <div class="p-2 bg-green-500/5 border border-green-500/10 rounded-lg text-[11px] text-green-400 break-words font-bold">
                                            {{ value }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Creation Details -->
                    <div v-else-if="selectedLog.event === 'created'" class="space-y-4">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest mb-2">Dados do Registro</span>
                        <div class="bg-white/5 rounded-2xl border border-white/5 p-6 overflow-x-auto">
                            <pre class="text-[10px] text-gray-400 font-mono">{{ JSON.stringify(formatJson(selectedLog.new_values), null, 4) }}</pre>
                        </div>
                    </div>

                    <!-- Deletion Details -->
                    <div v-else-if="selectedLog.event === 'deleted'" class="space-y-4">
                        <span class="block text-[9px] font-black text-gray-600 uppercase tracking-widest mb-2">Dados do Registro Excluído</span>
                        <div class="bg-red-500/5 rounded-2xl border border-red-500/10 p-6 overflow-x-auto">
                            <pre class="text-[10px] text-red-400/80 font-mono">{{ JSON.stringify(formatJson(selectedLog.old_values), null, 4) }}</pre>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="mt-8 pt-8 border-t border-white/5">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-black text-gray-600 uppercase tracking-widest">User Agent</span>
                                <span class="text-gray-400 font-medium line-clamp-1 max-w-[70%] select-all">{{ selectedLog.user_agent }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-white/[0.02] border-t border-white/5 flex justify-end">
                    <button 
                        @click="selectedLog = null"
                        class="px-8 py-2.5 bg-white/5 hover:bg-white/10 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all"
                    >
                        Fechar Visualizador
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
