<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    service: Object,
});

const emit = defineEmits(['close']);

const viewMode = ref('actions');
const deleteInput = ref('');
const isDeleting = ref(false);

const resetModal = () => {
    viewMode.value = 'actions';
    deleteInput.value = '';
    isDeleting.value = false;
};

watch(() => props.show, (val) => {
    if (val) resetModal();
});

const actions = [
    { id: 'details', label: 'Consultar Dados', icon: '🔍', color: 'bg-indigo-600/10 dark:bg-cyan-600/20 text-indigo-700 dark:text-cyan-400 border-indigo-500/20 dark:border-cyan-500/30', hover: 'hover:bg-indigo-600/20 dark:hover:bg-cyan-600/30', route: 'sales.atendimentos.show', type: 'visit' },
    { id: 'service', label: 'Ficha Atendimento', icon: '📄', color: 'bg-blue-600/10 dark:bg-blue-600/20 text-blue-700 dark:text-blue-400 border-blue-500/20 dark:border-blue-500/30', hover: 'hover:bg-blue-600/20 dark:hover:bg-blue-600/30', route: 'sales.atendimentos.ficha.pdf' },
    { id: 'checklist', label: 'Checklist', icon: '✅', color: 'bg-emerald-600/10 dark:bg-green-600/20 text-emerald-700 dark:text-green-400 border-emerald-500/20 dark:border-green-500/30', hover: 'hover:bg-emerald-600/20 dark:hover:bg-emerald-600/30', route: 'sales.atendimentos.checklist.pdf' },
    { id: 'proposal', label: 'Proposta', icon: '💰', color: 'bg-amber-600/10 dark:bg-yellow-600/20 text-amber-700 dark:text-yellow-400 border-amber-500/20 dark:border-yellow-500/30', hover: 'hover:bg-amber-600/20 dark:hover:bg-amber-600/30', route: 'sales.atendimentos.proposta.pdf' },
    { id: 'contract', label: 'Contrato', icon: '✍️', color: 'bg-violet-600/10 dark:bg-purple-600/20 text-violet-700 dark:text-purple-400 border-violet-500/20 dark:border-purple-500/30', hover: 'hover:bg-violet-600/20 dark:hover:bg-violet-600/30', route: 'sales.atendimentos.contrato.pdf' },
    { id: 'rci', label: 'RCI', icon: '🏝️', color: 'bg-orange-600/10 dark:bg-orange-600/20 text-orange-700 dark:text-orange-400 border-orange-500/20 dark:border-orange-500/30', hover: 'hover:bg-orange-600/20 dark:hover:bg-orange-600/30', route: 'sales.atendimentos.rci.pdf' },
    { id: 'cortesia', label: 'Cortesias', icon: '🎁', color: 'bg-rose-600/10 dark:bg-pink-600/20 text-rose-700 dark:text-pink-400 border-rose-500/20 dark:border-pink-500/30', hover: 'hover:bg-rose-600/20 dark:hover:bg-rose-600/30', route: 'sales.atendimentos.cortesia.pdf' },
    { id: 'delete', label: 'Excluir Atendimento', icon: '🗑️', color: 'bg-red-600/10 dark:bg-red-600/20 text-red-600 dark:text-red-500 border-red-500/30 dark:border-red-500/50', hover: 'hover:bg-red-600/20 dark:hover:bg-red-600/30', type: 'delete' },
];

const handleAction = (action) => {
    if (action.id === 'delete') {
        viewMode.value = 'delete_confirm';
        return;
    }

    if (!action.route) {
        return;
    }

    if (action.type === 'visit') {
        emit('close');
        router.visit(route(action.route, props.service.id));
    } else {
        // @ts-ignore
        window.open(route(action.route, props.service.id), '_blank');
    }
};

const confirmDelete = () => {
    if (deleteInput.value !== 'EXCLUIR') return;
    
    isDeleting.value = true;
    router.delete(route('sales.atendimentos.destroy', props.service.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleting.value = false;
            emit('close');
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

const hasCortesia = (cortesia) => {
    if (!cortesia) return false;
    if (Array.isArray(cortesia)) {
        return cortesia.length > 0 && !cortesia.includes('nenhuma');
    }
    return cortesia !== 'nenhuma';
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <!-- Actions Grid View -->
        <div v-if="viewMode === 'actions'" class="p-6 md:p-8 bg-white dark:bg-[#0f1219] border border-slate-200 dark:border-slate-800 rounded-[20px] relative overflow-hidden animate-in fade-in zoom-in-95 duration-200 shadow-2xl">
            <!-- Background Glow -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-brand-green/5 dark:bg-brand-green/10 rounded-full blur-[60px] pointer-events-none"></div>
            
            <div class="relative">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="w-1.5 h-5 bg-brand-green rounded-full shadow-sm"></span>
                        Central de Documentos
                    </h3>
                    <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-6">
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Cliente Selecionado</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ service?.client?.nome }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button 
                        v-for="action in actions" 
                        :key="action.id"
                        @click="handleAction(action)"
                        :disabled="(!action.route && action.id !== 'delete') || 
                                   (action.id === 'cortesia' && !hasCortesia(service?.cortesia)) ||
                                   (['proposal', 'contract', 'rci', 'checklist'].includes(action.id) && !service?.proposal)"
                        class="group relative p-4 rounded-xl border transition-all flex flex-col items-center gap-3 text-center disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed"
                        :class="[
                            ((action.route || action.id === 'delete') && !(['proposal', 'contract', 'rci', 'checklist'].includes(action.id) && !service?.proposal)) ? (action.color + ' ' + action.hover + ' hover:scale-[1.02]') : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-400 dark:text-slate-600'
                        ]"
                    >
                        <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">
                            {{ action.icon }}
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest leading-tight">
                            {{ action.label }}
                        </span>
                        
                        <!-- Status Badge -->
                        <span v-if="!action.route && action.id !== 'delete'" class="absolute top-1.5 right-1.5 text-[6px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-700 uppercase tracking-widest">
                            Bloqueado
                        </span>

                        <div v-if="action.route || action.id === 'delete'" class="absolute inset-0 bg-white/5 dark:bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl"></div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Premium Delete Confirmation View -->
        <div v-else-if="viewMode === 'delete_confirm'" class="bg-white dark:bg-[#0f1219] border border-red-500/30 p-8 text-center relative overflow-hidden shadow-2xl rounded-[20px] animate-in slide-in-from-right-4 duration-300">
            <!-- Decorative background glow -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-48 bg-red-500/10 blur-[80px] pointer-events-none"></div>

            <div class="relative w-20 h-20 bg-red-500/10 border border-red-500/20 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-red-500/10 rotate-3 transition-transform hover:rotate-0">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-2 relative z-10">Excluir Atendimento?</h3>
            <p class="text-slate-500 dark:text-gray-400 text-xs font-medium leading-relaxed mb-6 px-2 relative z-10">
                Você está prestes a excluir o atendimento do cliente <strong class="text-slate-900 dark:text-white">#{{ service?.client?.nome }}</strong>. <br>Isto apagará permanentemente todas as propostas, contratos e finanças a ele vinculadas. Esta ação é <span class="text-red-500 font-bold">irreversível</span>.
            </p>

            <div class="relative z-10 mb-6 text-left">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 text-center">Digite <span class="text-red-500">EXCLUIR</span> para confirmar</label>
                <input 
                    v-model="deleteInput" 
                    type="text" 
                    placeholder="EXCLUIR" 
                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl py-4 text-center text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-red-500 focus:ring-1 focus:ring-red-500 uppercase tracking-[0.3em] transition-all shadow-sm"
                >
            </div>

            <div class="flex flex-col gap-3 relative z-10">
                <button @click="confirmDelete" :disabled="deleteInput !== 'EXCLUIR' || isDeleting" class="w-full py-4 bg-red-600 hover:bg-red-500 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.3em] shadow-xl shadow-red-500/20 transition-all active:scale-95 flex items-center justify-center gap-3 border border-red-400/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="isDeleting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>{{ isDeleting ? 'Excluindo...' : 'SIM, EXCLUIR AGORA' }}</span>
                </button>
                
                <button @click="viewMode = 'actions'" :disabled="isDeleting" class="w-full py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-[12px] font-bold uppercase text-[10px] tracking-widest transition-all">
                    Voltar para Central
                </button>
            </div>
        </div>
    </Modal>
</template>
