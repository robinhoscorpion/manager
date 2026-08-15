<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';

const props = defineProps({
    groups: Object,
});

// ─── Local reactive state ────────────────────────────────────────────────────

const promotores  = ref([...( props.groups.promotor   || [])]);
const consultores = ref([...( props.groups.consultor  || [])]);
const supervisores= ref([...( props.groups.supervisor || [])]);

const callingGroup = ref(null);   // which group is being "called" right now (animation)
const toast        = ref({ show: false, message: '' });

// ─── Groups config ───────────────────────────────────────────────────────────

const groupConfig = {
    promotor: {
        label:       'Promotores',
        sublabel:    'OPC / Captador',
        key:         'promotor',
        list:        promotores,
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>`,
    },
    consultor: {
        label:       'Consultores',
        sublabel:    'Liner',
        key:         'consultor',
        list:        consultores,
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
    },
    supervisor: {
        label:       'Supervisores',
        sublabel:    'Closer / Gerente',
        key:         'supervisor',
        list:        supervisores,
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
    },
};

const groupList = ['promotor', 'consultor', 'supervisor'];

// ─── Status helpers ───────────────────────────────────────────────────────────

const statusConfig = {
    available: { label: 'Disponível',      bg: 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20', text: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    busy:      { label: 'Em Atendimento',  bg: 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20',   text: 'text-amber-600 dark:text-amber-400',   dot: 'bg-amber-500' },
    absent:    { label: 'Ausente',         bg: 'bg-slate-50 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20',   text: 'text-slate-500 dark:text-slate-400',   dot: 'bg-slate-500' },
};

const statusCycle = { available: 'busy', busy: 'absent', absent: 'available' };

function cycleStatus(groupKey, item) {
    const list = groupConfig[groupKey].list;
    const idx  = list.value.findIndex(i => i.id === item.id);
    if (idx === -1) return;
    const nextStatus = statusCycle[item.status];
    
    // Optimistic UI update
    list.value[idx] = { ...list.value[idx], status: nextStatus };
    
    router.patch(route('sales.linha.status', item.id), { status: nextStatus }, { 
        preserveScroll: true,
        onError: () => {
            // Revert on error
            list.value[idx] = { ...list.value[idx], status: item.status };
            showToast('Erro ao atualizar status.');
        }
    });
}

// ─── Drag & Drop ─────────────────────────────────────────────────────────────

function onDragEnd(groupKey) {
    const list = groupConfig[groupKey].list.value;
    router.patch(route('sales.linha.order'), {
        group:       groupKey,
        ordered_ids: list.map(i => i.id),
    }, { preserveScroll: true });
}

// ─── Call Next ───────────────────────────────────────────────────────────────

function callNext(groupKey) {
    callingGroup.value = groupKey;
    const list = groupConfig[groupKey].list;

    // Optimistic: find first available and mark as busy
    const idx = list.value.findIndex(i => i.status === 'available');
    if (idx !== -1) {
        list.value[idx] = { ...list.value[idx], status: 'busy' };
    }

    router.post(route('sales.linha.call-next', groupKey), {}, {
        preserveScroll: true,
        onFinish: () => { callingGroup.value = null; },
    });
}

// ─── Reset queue ─────────────────────────────────────────────────────────────

function resetQueue(groupKey) {
    if (!confirm(`Deseja reiniciar a fila de ${groupConfig[groupKey].label}? Todos voltarão para o status Disponível.`)) return;

    router.post(route('sales.linha.reset', groupKey), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(`Fila de ${groupConfig[groupKey].label} reiniciada!`);
        },
    });
}

// ─── Toast ───────────────────────────────────────────────────────────────────

function showToast(message) {
    toast.value = { show: true, message };
    setTimeout(() => { toast.value.show = false; }, 4000);
}

// ─── Stats ───────────────────────────────────────────────────────────────────

function getStats(groupKey) {
    const list = groupConfig[groupKey].list.value;
    return {
        total:     list.length,
        available: list.filter(i => i.status === 'available').length,
        busy:      list.filter(i => i.status === 'busy').length,
        absent:    list.filter(i => i.status === 'absent').length,
    };
}
</script>

<template>
    <Head title="Linha de Atendimento" />
    <AuthenticatedLayout>
        
        <!-- Action Notification (Match /atendimentos) -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div v-if="toast.show" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[300]">
                <div class="bg-indigo-950/90 dark:bg-[#1a1f2e] border border-indigo-500/30 backdrop-blur-xl px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">{{ toast.message }}</span>
                </div>
            </div>
        </Transition>

        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8 max-w-screen-2xl mx-auto pb-12">
                
                <!-- Premium Header (Match /atendimentos) -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Linha de Atendimento</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Sala de Vendas
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div v-for="groupKey in groupList" :key="'stat-'+groupKey"
                        class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] p-5 shadow-sm dark:shadow-none flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[12px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center text-brand-green">
                                    <span v-html="groupConfig[groupKey].icon"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ groupConfig[groupKey].label }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Total: {{ groupConfig[groupKey].list.value.length }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ getStats(groupKey).available }}
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                {{ getStats(groupKey).busy }}
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                {{ getStats(groupKey).absent }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main 3-column layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    
                    <div v-for="groupKey in groupList" :key="groupKey"
                        class="flex flex-col rounded-[20px] bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 shadow-sm dark:shadow-none overflow-hidden">
                        
                        <!-- Column Header -->
                        <div class="p-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[12px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center text-brand-green">
                                        <span v-html="groupConfig[groupKey].icon"></span>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                            {{ groupConfig[groupKey].label }}
                                        </h2>
                                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">
                                            {{ groupConfig[groupKey].sublabel }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Reset Button -->
                                <button @click="resetQueue(groupKey)"
                                    class="w-8 h-8 rounded-[8px] border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400 flex items-center justify-center hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-white transition-all shadow-sm"
                                    title="Reiniciar fila">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Call Next Button -->
                            <button @click="callNext(groupKey)"
                                :disabled="getStats(groupKey).available === 0"
                                class="w-full flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 disabled:hover:translate-y-0 disabled:hover:bg-brand-green"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17l5-5m0 0l-5-5m5 5H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Chamar Próximo</span>
                            </button>
                        </div>

                        <!-- Drag list area -->
                        <div class="p-4 flex-1 overflow-y-auto space-y-3 min-h-[350px] relative bg-slate-50/40 dark:bg-slate-800/20">
                            
                            <!-- Empty State -->
                            <div v-if="groupConfig[groupKey].list.value.length === 0"
                                class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300">Fila Vazia</h3>
                                <p class="text-xs text-slate-500 mt-1">Nenhum funcionário encontrado.</p>
                            </div>

                            <draggable
                                v-model="groupConfig[groupKey].list.value"
                                :group="groupKey"
                                item-key="id"
                                handle=".drag-handle"
                                animation="250"
                                @end="onDragEnd(groupKey)"
                                class="space-y-3 pb-2"
                            >
                                <template #item="{ element: item, index }">
                                    <div
                                        class="relative rounded-[16px] bg-white dark:bg-slate-900 border transition-all"
                                        :class="[
                                            item.status === 'absent' ? 'opacity-50 border-slate-200 dark:border-slate-800' : 'border-slate-200 dark:border-slate-800 shadow-sm',
                                            index === 0 && item.status === 'available' ? 'border-brand-green/50 dark:border-brand-green/50 ring-1 ring-brand-green/20' : '',
                                        ]"
                                    >
                                        <div class="flex items-center p-3 gap-3">
                                            <!-- Order Indicator / Position -->
                                            <div class="flex-shrink-0 w-8 h-8 rounded-[10px] flex items-center justify-center text-xs font-bold"
                                                :class="index === 0 && item.status === 'available'
                                                    ? 'bg-brand-green/10 text-brand-green border border-brand-green/20'
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                                                {{ index + 1 }}
                                            </div>

                                            <!-- Avatar -->
                                            <div class="relative flex-shrink-0 w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800">
                                                <img v-if="item.user?.profile_photo_url"
                                                    :src="item.user.profile_photo_url"
                                                    :alt="item.user?.name"
                                                    class="w-full h-full rounded-full object-cover"
                                                />
                                                <div v-else class="w-full h-full flex items-end justify-center overflow-hidden">
                                                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                </div>
                                                
                                                <!-- Status dot -->
                                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900" :class="statusConfig[item.status].dot"></span>
                                            </div>

                                            <!-- User Info -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[13px] font-bold text-slate-900 dark:text-white truncate">
                                                    {{ item.user?.name }}
                                                </p>
                                                
                                                <!-- Interactive Status Badge -->
                                                <button @click="cycleStatus(groupKey, item)"
                                                    class="mt-1 px-2 py-0.5 rounded border text-[9px] font-black uppercase tracking-[0.12em] whitespace-nowrap transition-all hover:brightness-95 active:scale-95"
                                                    :class="[statusConfig[item.status].bg, statusConfig[item.status].text]"
                                                    title="Clique para alternar status">
                                                    {{ statusConfig[item.status].label }}
                                                </button>
                                            </div>

                                            <!-- Drag handle -->
                                            <div class="drag-handle flex-shrink-0 w-8 h-8 rounded-[8px] flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 cursor-grab active:cursor-grabbing transition-colors">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                                                    <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                                    <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Solid basic scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}
</style>
