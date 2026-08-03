<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    seasons: Array
});

// All possible months and standard special dates
const allItems = [
    { id: 'm-1', type: 'month', name: 'Janeiro', color: 'bg-blue-500' },
    { id: 'm-2', type: 'month', name: 'Fevereiro', color: 'bg-blue-500' },
    { id: 'm-3', type: 'month', name: 'Março', color: 'bg-blue-500' },
    { id: 'm-4', type: 'month', name: 'Abril', color: 'bg-blue-500' },
    { id: 'm-5', type: 'month', name: 'Maio', color: 'bg-blue-500' },
    { id: 'm-6', type: 'month', name: 'Junho', color: 'bg-blue-500' },
    { id: 'm-7', type: 'month', name: 'Julho', color: 'bg-blue-500' },
    { id: 'm-8', type: 'month', name: 'Agosto', color: 'bg-blue-500' },
    { id: 'm-9', type: 'month', name: 'Setembro', color: 'bg-blue-500' },
    { id: 'm-10', type: 'month', name: 'Outubro', color: 'bg-blue-500' },
    { id: 'm-11', type: 'month', name: 'Novembro', color: 'bg-blue-500' },
    { id: 'm-12', type: 'month', name: 'Dezembro', color: 'bg-blue-500' },
    { id: 'd-1', type: 'date', name: 'Carnaval', color: 'bg-orange-500' },
    { id: 'd-2', type: 'date', name: 'Páscoa', color: 'bg-pink-500' },
    { id: 'd-3', type: 'date', name: 'Tiradentes', color: 'bg-teal-500' },
    { id: 'd-4', type: 'date', name: 'Dia do Trabalho', color: 'bg-green-500' },
    { id: 'd-5', type: 'date', name: 'Corpus Christi', color: 'bg-amber-800' },
    { id: 'd-6', type: 'date', name: 'Independência do Brasil', color: 'bg-cyan-500' },
    { id: 'd-7', type: 'date', name: 'Dia das Crianças', color: 'bg-purple-500' },
    { id: 'd-8', type: 'date', name: 'Finados', color: 'bg-lime-500' },
    { id: 'd-9', type: 'date', name: 'Proclamação da República', color: 'bg-yellow-500' },
    { id: 'd-10', type: 'date', name: 'Natal', color: 'bg-indigo-500' },
    { id: 'd-11', type: 'date', name: 'Réveillon', color: 'bg-blue-400' },
];

const unassignedItems = ref([]);
const seasonMappings = ref([]);

// Parse JSON arrays safely
const safeParse = (value) => {
    if (!value) return [];
    if (typeof value === 'string') {
        try {
            return JSON.parse(value);
        } catch (e) {
            return [];
        }
    }
    return Array.isArray(value) ? value : [];
};

onMounted(() => {
    const assignedNames = new Set();
    
    seasonMappings.value = props.seasons.map(season => {
        const months = safeParse(season.months_active);
        const dates = safeParse(season.special_dates);
        
        const items = [];
        
        months.forEach(m => {
            const found = allItems.find(i => i.type === 'month' && i.name === m);
            if (found) {
                items.push(found);
                assignedNames.add(found.name);
            }
        });
        
        dates.forEach(d => {
            let found = allItems.find(i => i.type === 'date' && i.name === d);
            if (!found) {
                // Se for uma data especial que não está na nossa lista padrão
                found = { id: `d-custom-${d}`, type: 'date', name: d, color: 'bg-gray-600' };
            }
            items.push(found);
            assignedNames.add(found.name);
        });
        
        return {
            id: season.id,
            name: season.name,
            items: items
        };
    });
    
    // Fill unassigned pool with items not mapped
    unassignedItems.value = allItems.filter(item => !assignedNames.has(item.name));
});

const isSaving = ref(false);

const saveMapping = () => {
    isSaving.value = true;
    router.post(route('admin.seasons.mapping.store'), {
        mappings: seasonMappings.value.map(sm => ({
            id: sm.id,
            items: sm.items.map(item => ({
                name: item.name,
                type: item.type
            }))
        }))
    }, {
        onFinish: () => isSaving.value = false,
        preserveScroll: true
    });
};

const newDateName = ref('');
const addNewDate = () => {
    if (newDateName.value.trim() !== '') {
        const name = newDateName.value.trim();
        unassignedItems.value.push({
            id: `d-custom-${Date.now()}`,
            type: 'date',
            name: name,
            color: 'bg-gray-600'
        });
        newDateName.value = '';
    }
};

const dragOptions = {
    animation: 200,
    group: "seasons",
    disabled: false,
    ghostClass: "opacity-50"
};

const editingItem = ref(null);
const editName = ref('');

const startEdit = (item) => {
    editingItem.value = item.id;
    editName.value = item.name;
};

const saveEdit = (item) => {
    if (editName.value.trim() !== '') {
        item.name = editName.value.trim();
    }
    editingItem.value = null;
};
</script>

<template>
    <Head title="Mapeamento de Temporadas" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <Link :href="route('admin.seasons.index')" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            </Link>
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Mapeamento de Temporadas</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Arraste e Solte para Organizar
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button 
                                @click="saveMapping" 
                                class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-2 disabled:opacity-50"
                                :disabled="isSaving"
                            >
                                <svg v-if="!isSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ isSaving ? 'Salvando...' : 'Salvar Alterações' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-6 items-start">
                    
                    <!-- Pool de Itens Livres -->
                    <div class="w-full md:w-1/3 bg-white dark:bg-slate-900 p-5 rounded-[20px] shadow-sm border border-slate-200 dark:border-slate-800 sticky top-4">
                        <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight">Disponíveis</h3>
                        </div>
                        
                        <div class="mb-5 flex gap-2">
                            <input type="text" v-model="newDateName" @keyup.enter="addNewDate" placeholder="Adicionar data especial..." class="w-full text-sm rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 focus:ring-brand-green/40 focus:border-brand-green/40 shadow-sm transition-all px-3 py-2 text-slate-700 dark:text-white">
                            <button @click="addNewDate" class="bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 text-indigo-600 dark:text-indigo-400 px-3 py-2 rounded-xl transition-colors font-bold flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>

                        <draggable 
                            class="min-h-[250px] flex flex-wrap gap-2 content-start p-4 bg-slate-50/80 dark:bg-slate-800/30 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 transition-colors" 
                            :list="unassignedItems" 
                            group="seasons" 
                            itemKey="id"
                            v-bind="dragOptions"
                        >
                            <template #item="{ element }">
                                <div 
                                    @dblclick="startEdit(element)"
                                    class="px-3.5 py-1.5 rounded-full text-white text-[11px] font-bold uppercase tracking-wider cursor-grab shadow-sm flex items-center gap-1.5 transition-transform hover:-translate-y-0.5 active:cursor-grabbing hover:shadow-md"
                                    :class="element.color"
                                    :title="editingItem === element.id ? '' : 'Duplo clique para editar'"
                                >
                                    <svg v-if="editingItem !== element.id" class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                    <span v-if="editingItem !== element.id" class="select-none">{{ element.name }}</span>
                                    
                                    <input 
                                        v-else
                                        type="text"
                                        v-model="editName"
                                        @blur="saveEdit(element)"
                                        @keyup.enter="saveEdit(element)"
                                        @keyup.esc="editingItem = null"
                                        class="bg-transparent border-0 border-b border-white/50 text-white p-0 m-0 outline-none text-[11px] w-24 uppercase font-bold focus:ring-0 focus:border-white placeholder-white/50"
                                        style="box-shadow: none;"
                                        onClick="this.select()"
                                    >
                                </div>
                            </template>
                        </draggable>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-4 text-center">Arraste os itens para os quadros</p>
                    </div>

                    <!-- Quadros das Temporadas -->
                    <div class="w-full md:w-2/3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div 
                            v-for="(season, index) in seasonMappings" 
                            :key="season.id"
                            class="bg-white dark:bg-slate-900 p-5 rounded-[20px] shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col group hover:border-brand-green/30 transition-colors"
                        >
                            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shadow-sm text-white" :class="index % 2 === 0 ? 'bg-brand-green' : 'bg-emerald-500'">
                                    T{{ index + 1 }}
                                </div>
                                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-tight truncate" :title="season.name">{{ season.name }}</h3>
                            </div>
                            
                            <draggable 
                                class="flex-1 min-h-[160px] flex flex-wrap gap-2 content-start p-3 bg-slate-50/50 dark:bg-slate-800/20 rounded-xl border border-slate-100 dark:border-slate-700/50 transition-colors group-hover:bg-brand-green/5" 
                                :list="season.items" 
                                group="seasons" 
                                itemKey="id"
                                v-bind="dragOptions"
                            >
                                <template #item="{ element }">
                                    <div 
                                        class="px-3 py-1.5 rounded-full text-white text-[10px] font-bold uppercase tracking-wider cursor-grab shadow-sm transition-transform hover:scale-105 active:cursor-grabbing hover:shadow-md flex items-center gap-1"
                                        :class="element.color"
                                    >
                                        <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                        {{ element.name }}
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
