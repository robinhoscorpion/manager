<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    resorts: Array,
    seasons: Array, // se precisar
});

// Computa a lista plana de todas as colunas de dados de um resort
const getDataColumns = (resort) => resort.accommodationGroups.flatMap(g => {
    if (g.columns.length === 0) {
        return [{ key: 'empty_' + g.accommodation_id, label: 'S/ PAX', accommodation_id: g.accommodation_id, empty: true }];
    }
    return g.columns;
});

const editingCell = ref(null);
const editValue = ref(0);

const startEdit = (row, colKey) => {
    editingCell.value = row.season_id + '_' + colKey;
    editValue.value = row[colKey + '_raw'];
};

const cancelEdit = () => {
    editingCell.value = null;
    editValue.value = 0;
};

const saveEdit = (row, colKey) => {
    if (!editingCell.value) return;

    const scoreId = row[colKey + '_score_id'];
    const newVal = editValue.value;

    if (!scoreId) {
        alert("ID de pontuação não encontrado. Configure a acomodação corretamente.");
        cancelEdit();
        return;
    }

    router.post(route('admin.tabela_pontos.score'), {
        score_id: scoreId,
        points: newVal
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            cancelEdit();
        },
        onError: () => {
            alert('Erro ao salvar pontuação.');
            cancelEdit();
        }
    });
};

// =======================
// CRUD MANUAIS
// =======================

// Modais State
const activeModal = ref(null);
const currentResortId = ref(null);
const currentAccId = ref(null);

const closeModal = () => {
    activeModal.value = null;
    currentResortId.value = null;
    currentAccId.value = null;
    formResort.reset();
    formAcc.reset();
    formPax.reset();
    formSeason.reset();
};

// Forms
const formResort = useForm({
    name: '',
    icon: '🏨',
    color_theme: '#3d5a2e',
});

const formAcc = useForm({
    name: '',
    group_name: '',
    max_pax: 2,
});

const formPax = useForm({
    pax: 2,
});

const formSeason = useForm({
    name: '',
    advance_days: 180,
    period_description: '',
});

// Actions Create
const submitResort = () => {
    formResort.post(route('admin.tabela_pontos.resort.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal()
    });
};

const submitAcc = () => {
    formAcc.post(route('admin.tabela_pontos.accommodation.store', { id: currentResortId.value }), {
        preserveScroll: true,
        onSuccess: () => closeModal()
    });
};

const submitPax = () => {
    if (!currentAccId.value) {
        alert("Erro: ID da acomodação não encontrado. Por favor, recarregue a página (F5) para atualizar os dados.");
        return;
    }
    formPax.post(route('admin.tabela_pontos.pax.store', { id: currentAccId.value }), {
        preserveScroll: true,
        onSuccess: () => closeModal()
    });
};

const submitSeason = () => {
    formSeason.post(route('admin.tabela_pontos.season.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal()
    });
};

// Actions Delete
const deleteResort = (id) => {
    if(confirm('Tem certeza que deseja excluir todo este Empreendimento e suas tabelas?')) {
        router.delete(route('admin.tabela_pontos.resort.destroy', id), { preserveScroll: true });
    }
};

const deleteAcc = (id) => {
    if(confirm('Excluir esta Acomodação? Todas as colunas vinculadas serão perdidas.')) {
        router.delete(route('admin.tabela_pontos.accommodation.destroy', id), { preserveScroll: true });
    }
};

const deletePax = (acc_id, pax) => {
    if(confirm(`Excluir a coluna de ${pax} PAX desta acomodação?`)) {
        router.delete(route('admin.tabela_pontos.pax.destroy', { id: acc_id, pax: pax }), { preserveScroll: true });
    }
};

const deleteSeason = (id) => {
    if(confirm('Excluir esta Temporada? Esta ação afetará todos os empreendimentos e perderá todas as pontuações atreladas a ela.')) {
        router.delete(route('admin.tabela_pontos.season.destroy', id), { preserveScroll: true });
    }
};

// Helper para garantir autocracia de contraste e legibilidade nos cabeçalhos dos resorts
const isLightColor = (hex) => {
    if (!hex || typeof hex !== 'string') return true;
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    if (hex.length !== 6) return false;
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 180;
};

const getHeaderStyle = (color) => {
    if (!color || isLightColor(color)) {
        return { backgroundColor: '#2d3b22', color: '#ffffff' };
    }
    return { backgroundColor: color, color: '#ffffff' };
};

const getSubHeaderStyle = (color) => {
    if (!color || isLightColor(color)) {
        return { backgroundColor: '#1e2918', color: '#ffffff' };
    }
    return { backgroundColor: color, filter: 'brightness(0.82)', color: '#ffffff' };
};

</script>

<template>
    <div class="space-y-8 pb-10">
        
            <div class="flex items-center gap-3 relative z-10">
                <button @click="activeModal = 'season'" class="bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 px-5 py-3 rounded-[14px] text-[11px] font-black uppercase tracking-widest transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Nova Temporada
                </button>
                <button @click="activeModal = 'resort'" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-3 rounded-[14px] text-[11px] font-black uppercase tracking-widest transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Novo Empreendimento
                </button>
            </div>
        </div>

        <div
            v-for="resort in resorts"
            :key="resort.id"
            class="group relative mb-6 rounded-[20px] shadow-sm bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 overflow-hidden transition-all duration-300 hover:shadow-md"
        >
            <div class="overflow-x-auto rounded-[24px]">
                <table class="w-full border-collapse text-[11px] bg-white dark:bg-slate-900">
                    <thead>
                        <tr>
                            <!-- Nome do resort -->
                            <th
                                class="text-white font-black text-[15px] uppercase tracking-widest px-5 py-5 text-left border-r border-b border-white/20 align-middle shadow-md relative overflow-hidden group/resort"
                                rowspan="2"
                                style="min-width: 220px;"
                                :style="getHeaderStyle(resort.headerColor)"
                            >
                                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>
                                <div class="flex items-center justify-between relative z-10">
                                    <div class="flex items-center gap-3">
                                        <div v-if="resort.icon" class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl shadow-inner backdrop-blur-md border border-white/40">
                                            {{ resort.icon }}
                                        </div>
                                        <span class="leading-tight drop-shadow-md">{{ resort.name }}</span>
                                    </div>
                                    
                                    <!-- Ações do Resort (Hover) -->
                                    <div class="flex items-center gap-1.5 opacity-0 group-hover/resort:opacity-100 transition-opacity">
                                        <button @click="currentResortId = resort.id; activeModal = 'accommodation'" class="p-2 rounded-lg bg-black/20 hover:bg-white/30 text-white transition-colors backdrop-blur-md" title="Adicionar Acomodação">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                        <button @click="deleteResort(resort.id)" class="p-2 rounded-lg bg-black/20 hover:bg-red-500 text-white transition-colors backdrop-blur-md" title="Excluir Empreendimento">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </th>

                            <!-- Antecedência -->
                            <th
                                class="bg-slate-200/90 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-black text-xs uppercase tracking-widest px-3 py-3 text-center border-b border-r border-slate-300 dark:border-slate-700 align-middle shadow-sm"
                                rowspan="2"
                                style="min-width: 100px;"
                            >
                                Antecedência
                            </th>

                            <!-- Grupos -->
                            <th
                                v-for="group in resort.accommodationGroups"
                                :key="group.label"
                                :colspan="Math.max(1, group.columns.length)"
                                class="text-white font-black text-center px-4 py-4 border-b border-r border-white/20 uppercase tracking-widest relative overflow-hidden group/acc shadow-sm"
                                :style="getHeaderStyle(resort.headerColor)"
                            >
                                <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>
                                <div class="flex items-center justify-center gap-2.5 relative z-10">
                                    <span class="drop-shadow-md">{{ group.label }}</span>
                                    
                                    <!-- Ações da Acomodação -->
                                    <div class="flex items-center gap-1.5 ml-2 bg-black/20 rounded-lg backdrop-blur-md p-1 transition-all group-hover/acc:bg-black/40 opacity-80 hover:opacity-100">
                                        <button @click="currentAccId = group.accommodation_id; activeModal = 'pax'" class="p-1 rounded-md hover:bg-white/30 transition-colors" title="Adicionar PAX">
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                        <button @click="deleteAcc(group.accommodation_id)" class="p-1 rounded-md hover:bg-red-500 transition-colors" title="Excluir Acomodação">
                                            <svg class="w-3.5 h-3.5 text-white/90 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </th>

                            <!-- Período -->
                            <th
                                class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest px-3 py-3 text-center border-b border-slate-200/80 dark:border-slate-700/80 align-middle"
                                rowspan="2"
                                style="min-width: 180px;"
                            >
                                Período
                            </th>
                        </tr>
                        <tr>
                            <!-- Colunas PAX -->
                            <th
                                v-for="col in getDataColumns(resort)"
                                :key="col.key"
                                class="text-white font-extrabold text-center px-2 py-2.5 border-r border-white/20 text-[11px] uppercase tracking-widest relative group/pax shadow-inner"
                                :style="getSubHeaderStyle(resort.headerColor)"
                                style="min-width: 85px;"
                            >
                                <div class="absolute inset-0 bg-black/10 pointer-events-none"></div>
                                <div class="flex items-center justify-center gap-1.5 relative z-10">
                                    <span class="drop-shadow-sm">{{ col.label }}</span>
                                    <button v-if="!col.empty" @click="deletePax(col.accommodation_id, col.pax)" class="opacity-0 group-hover/pax:opacity-100 p-0.5 rounded-md bg-black/20 hover:bg-red-500 transition-all text-white" title="Excluir PAX">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, rowIndex) in resort.rows"
                            :key="rowIndex"
                            class="group/row transition-all duration-200"
                            :class="rowIndex % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/50 dark:bg-slate-800/30'"
                        >
                            <td class="font-black text-slate-900 dark:text-slate-100 px-5 py-4 border-r border-b border-slate-300 dark:border-slate-800 uppercase text-[12px] tracking-wide group-hover/row:bg-brand-green/5 dark:group-hover/row:bg-slate-800/50 transition-colors relative">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-1.5 h-3.5 rounded-full bg-slate-300 dark:bg-slate-600 transition-colors group-hover/row:bg-brand-green"></div>
                                        {{ row.season }}
                                    </div>
                                    <button @click="deleteSeason(row.season_id)" class="opacity-0 group-hover/row:opacity-100 p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 text-slate-400 transition-all" title="Excluir Temporada">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center font-bold text-slate-500 dark:text-slate-400 px-3 py-4 border-r border-b border-slate-200/60 dark:border-slate-800 text-[11px] group-hover/row:bg-brand-green/5 dark:group-hover/row:bg-slate-800/50 transition-colors">
                                <span class="inline-flex items-center justify-center min-w-[45px] px-3 py-1.5 bg-slate-200/90 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-black text-[11px] rounded-lg shadow-sm border border-slate-300 dark:border-slate-700 group-hover/row:scale-105 transition-all">
                                    {{ row.days }}
                                </span>
                            </td>

                            <!-- Células editáveis -->
                            <td
                                v-for="col in getDataColumns(resort)"
                                :key="col.key"
                                class="px-2 py-4 border-r border-b border-slate-200/60 dark:border-slate-800 text-center font-black text-slate-700 dark:text-slate-300 relative group/cell transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800 hover:shadow-[inset_0_0_10px_rgba(0,0,0,0.02)]"
                                @dblclick="!col.empty && startEdit(row, col.key)"
                                :title="col.empty ? 'Acomodação sem PAX' : 'Duplo clique para editar'"
                            >
                                <div v-if="col.empty" class="text-slate-300 dark:text-slate-600 text-[10px] uppercase font-bold italic opacity-50 cursor-not-allowed">
                                    -
                                </div>
                                <template v-else>
                                    <div v-if="editingCell === row.season_id + '_' + col.key" class="absolute inset-0 flex items-center justify-center p-1.5 bg-white dark:bg-slate-800 shadow-[0_0_20px_rgba(0,0,0,0.1)] ring-2 ring-brand-green z-20 rounded-lg transform scale-[1.02] transition-all">
                                        <input 
                                            type="number" 
                                            v-model="editValue"
                                            @blur="saveEdit(row, col.key)"
                                            @keyup.enter="saveEdit(row, col.key)"
                                            @keyup.esc="cancelEdit"
                                            class="w-full text-center text-[15px] font-black text-brand-green bg-slate-50 dark:bg-slate-900 border border-brand-green/20 rounded-md focus:ring-0 focus:border-brand-green/50 p-1.5 selection:bg-brand-green/20 shadow-inner"
                                            autofocus
                                        />
                                        <!-- Animated indicator -->
                                        <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-brand-green rounded-full animate-ping opacity-75"></div>
                                        <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-brand-green rounded-full shadow-sm border border-white dark:border-slate-800"></div>
                                    </div>
                                    <div v-else class="flex flex-col items-center gap-1.5 transition-transform group-hover/cell:-translate-y-0.5">
                                        <span class="text-[14px] font-black text-slate-900 dark:text-slate-100 tabular-nums tracking-tight">{{ row[col.key] || '-' }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 opacity-0 group-hover/cell:opacity-100 transition-all transform scale-75 group-hover/cell:scale-100 group-hover/cell:text-brand-green drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </div>
                                </template>
                            </td>

                            <td class="text-center text-[11px] font-black text-slate-800 dark:text-slate-100 px-4 py-4 border-b border-slate-200 dark:border-slate-800 uppercase leading-relaxed tracking-wider whitespace-pre-line group-hover/row:bg-brand-green/5 dark:group-hover/row:bg-slate-800/50 transition-colors">
                                {{ row.period }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="resort.rows.length === 0" class="text-center py-16 text-slate-500 font-medium">
                    <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    Nenhuma temporada cadastrada para este resort.
                </div>
            </div>
        </div>
        
        <!-- Empty State General -->
        <div v-if="!resorts || resorts.length === 0" class="flex flex-col items-center justify-center py-20 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[24px] border border-slate-200 border-dashed dark:border-slate-700 shadow-sm">
            <div class="w-20 h-20 bg-brand-green/10 rounded-full flex items-center justify-center mb-5 animate-pulse">
                <svg class="w-10 h-10 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Nenhum Empreendimento Encontrado</h4>
            <p class="text-sm text-slate-500 mb-6 max-w-sm text-center">Comece cadastrando um novo resort ou hotel para definir sua matriz de pontos por temporada.</p>
            <button @click="activeModal = 'resort'" class="bg-brand-green hover:bg-[#485638] text-white px-6 py-3 rounded-[16px] text-xs font-bold uppercase tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Cadastrar Empreendimento
            </button>
        </div>

        <!-- ======================= -->
        <!-- MODAIS DE CRUD          -->
        <!-- ======================= -->

        <!-- Modal Resort -->
        <div v-if="activeModal === 'resort'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-slate-200 dark:border-slate-800">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Novo Empreendimento</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Nome do Empreendimento</label>
                        <input v-model="formResort.name" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: Itacaré Vacation Club">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Ícone</label>
                            <input v-model="formResort.icon" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: 🏨">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Cor do Cabeçalho</label>
                            <input v-model="formResort.color_theme" type="color" class="w-full h-10 p-1 rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green cursor-pointer">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                    <button @click="submitResort" :disabled="formResort.processing" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm disabled:opacity-50">Salvar</button>
                </div>
            </div>
        </div>

        <!-- Modal Acomodação -->
        <div v-if="activeModal === 'accommodation'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-slate-200 dark:border-slate-800">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Nova Acomodação</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Nome da Unidade</label>
                        <input v-model="formAcc.name" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: Casa T3 - Beira Mar">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Grupo (Opcional)</label>
                            <input v-model="formAcc.group_name" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: T3 (Casa 3 Quartos)">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Capacidade Máxima</label>
                            <input v-model="formAcc.max_pax" type="number" min="1" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: 8">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                    <button @click="submitAcc" :disabled="formAcc.processing" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm disabled:opacity-50">Criar Acomodação</button>
                </div>
            </div>
        </div>

        <!-- Modal Temporada -->
        <div v-if="activeModal === 'season'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-slate-200 dark:border-slate-800">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Nova Temporada</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Nome da Temporada</label>
                        <input v-model="formSeason.name" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: SUPER ALTA">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Período (Opcional)</label>
                        <input v-model="formSeason.period_description" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: Réveillon, Carnaval">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Antecedência (Dias)</label>
                        <input v-model="formSeason.advance_days" type="number" min="0" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green" placeholder="Ex: 180">
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg border border-amber-200 dark:border-amber-800/50 mt-2">
                        <p class="text-xs text-amber-700 dark:text-amber-400 font-medium leading-relaxed">
                            <span class="font-bold">Aviso:</span> Criar uma temporada aqui a adicionará em todas as matrizes. Os valores para todas as colunas PAX serão iniciados como "0" para preenchimento.
                        </p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                    <button @click="submitSeason" :disabled="formSeason.processing" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm disabled:opacity-50">Criar Temporada</button>
                </div>
            </div>
        </div>

        <!-- Modal PAX -->
        <div v-if="activeModal === 'pax'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-2xl w-full max-w-sm overflow-hidden transform transition-all border border-slate-200 dark:border-slate-800">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">Nova Coluna PAX</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Capacidade (Número de Pessoas)</label>
                        <input v-model="formPax.pax" type="number" min="1" max="20" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 focus:ring-brand-green focus:border-brand-green text-center text-lg font-bold" placeholder="2">
                        <p class="text-xs text-slate-500 mt-2">Isso criará uma nova coluna e registrará valores zerados para todas as temporadas desta acomodação.</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                    <button @click="submitPax" :disabled="formPax.processing" class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm disabled:opacity-50">Criar Coluna</button>
                </div>
            </div>
        </div>
    </template>