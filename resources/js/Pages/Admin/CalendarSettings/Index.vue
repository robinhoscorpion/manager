<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import YearPicker from '@/Components/Dashboard/YearPicker.vue';
import PointsTab from './Partials/PointsTab.vue';

const props = defineProps({
    seasons:      Array,
    holidays:     Array,
    resorts:      Array,
    selectedYear: { type: Number, default: new Date().getFullYear() }
});

const activeTab = ref('seasons');

// --- Filtros Feriados ---
const currentYear = new Date().getFullYear();
const selectedYearRef = ref(props.selectedYear);

// Navega mantendo aba e ano na URL
const goToTab = (tab, year) => {
    year = year || selectedYearRef.value;
    if (tab) activeTab.value = tab;
    router.get(route('admin.calendar_settings.index'), { year }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setSelectedYear = (year) => {
    selectedYearRef.value = Number(year);
    goToTab(activeTab.value, year);
};

// --- Template Feriados ---
const baseHolidayTemplate = [
    { name: 'Ano Novo', classification: 'Data Especial' },
    { name: 'Carnaval', classification: 'Super Alta' },
    { name: 'Semana Santa / Páscoa', classification: 'Alta Temporada' },
    { name: 'Tiradentes', classification: 'Alta Temporada' },
    { name: 'Dia do Trabalhador', classification: 'Alta Temporada' },
    { name: 'Corpus Christi', classification: 'Alta Temporada' },
    { name: 'São João', classification: 'Alta Temporada' },
    { name: 'Independência da Bahia', classification: 'Alta Temporada' },
    { name: 'Independência do Brasil', classification: 'Alta Temporada' },
    { name: 'N. Sra. Aparecida / Crianças', classification: 'Alta Temporada' },
    { name: 'Finados', classification: 'Alta Temporada' },
    { name: 'Proclamação da República', classification: 'Alta Temporada' },
    { name: 'Consciência Negra', classification: 'Alta Temporada' },
    { name: 'Natal', classification: 'Alta Temporada' }
];

const normalizeStr = (str) => str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

const filteredHolidays = computed(() => {
    const yearHolidays = (props.holidays || []).filter(holiday => {
        const raw = holiday.holiday_date || holiday.start_date || holiday.end_date;
        if (!raw) return false;
        const cleaned = String(raw).split('T')[0];
        const date = new Date(cleaned + 'T00:00:00');
        return date.getFullYear() === Number(selectedYearRef.value);
    });

    const result = [];
    const usedIds = new Set();

    baseHolidayTemplate.forEach(templateItem => {
        const normalizedTemplate = normalizeStr(templateItem.name);
        const existing = yearHolidays.find(h => {
            const normalizedH = normalizeStr(h.name);
            return normalizedH.includes(normalizedTemplate) || normalizedTemplate.includes(normalizedH);
        });

        if (existing) {
            usedIds.add(existing.id);
            result.push(existing);
        } else {
            result.push({
                id: null,
                name: templateItem.name,
                classification: templateItem.classification,
                holiday_date: '',
                start_date: '',
                end_date: '',
                is_template: true
            });
        }
    });

    yearHolidays.forEach(h => {
        if (!usedIds.has(h.id)) result.push(h);
    });

    return result;
});

// --- Normaliza / formata datas ---
const parseDate = (val) => {
    if (!val) return null;
    const str = typeof val === 'object' ? val.toString() : String(val);
    return str.split('T')[0];
};

const formatDate = (dateString) => {
    const cleaned = parseDate(dateString);
    if (!cleaned) return 'Não definido';
    const date = new Date(cleaned + 'T00:00:00');
    return date.toLocaleDateString('pt-BR');
};

// ===== LÓGICA TEMPORADAS =====
const showSeasonModal  = ref(false);
const isEditingSeason  = ref(false);

const formSeason = useForm({
    id:                 null,
    name:               '',
    advance_days:       0,
    period_description: '',
});

const openCreateSeasonModal = () => {
    isEditingSeason.value = false;
    formSeason.reset();
    formSeason.clearErrors();
    showSeasonModal.value = true;
};

const openEditSeasonModal = (season) => {
    isEditingSeason.value = true;
    formSeason.reset();
    formSeason.clearErrors();
    formSeason.id                 = season.id;
    formSeason.name               = season.name;
    formSeason.advance_days       = season.advance_days;
    formSeason.period_description = season.period_description || '';
    showSeasonModal.value = true;
};

const closeSeasonModal = () => {
    showSeasonModal.value = false;
    formSeason.reset();
};

const submitSeason = () => {
    const onDone = () => {
        closeSeasonModal();
        activeTab.value = 'seasons';
    };
    if (isEditingSeason.value) {
        formSeason.put(route('admin.seasons.update', formSeason.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onDone
        });
    } else {
        formSeason.post(route('admin.seasons.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onDone
        });
    }
};

const deleteSeason = (id) => {
    if (confirm('Tem certeza que deseja excluir esta temporada?')) {
        router.delete(route('admin.seasons.destroy', id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { activeTab.value = 'seasons'; }
        });
    }
};

// ===== LÓGICA FERIADOS =====
const showHolidayModal    = ref(false);
const isEditingHoliday    = ref(false);
const isTemplateHoliday   = ref(false);

const formHoliday = useForm({
    id:             null,
    name:           '',
    holiday_date:   '',
    start_date:     '',
    end_date:       '',
    classification: '',
});

const openCreateHolidayModal = () => {
    isEditingHoliday.value  = false;
    isTemplateHoliday.value = false;
    formHoliday.reset();
    formHoliday.clearErrors();
    showHolidayModal.value = true;
};

const openEditHolidayModal = (holiday) => {
    isEditingHoliday.value  = !!holiday.id;
    isTemplateHoliday.value = !!holiday.is_template;
    formHoliday.reset();
    formHoliday.clearErrors();
    formHoliday.id             = holiday.id;
    formHoliday.name           = holiday.name;
    formHoliday.classification = holiday.classification || '';

    if (holiday.is_template) {
        const y = selectedYearRef.value;
        formHoliday.holiday_date = `${y}-01-01`;
        formHoliday.start_date   = `${y}-01-01`;
        formHoliday.end_date     = `${y}-01-01`;
    } else {
        formHoliday.holiday_date = parseDate(holiday.holiday_date) || '';
        formHoliday.start_date   = parseDate(holiday.start_date)   || '';
        formHoliday.end_date     = parseDate(holiday.end_date)     || '';
    }
    showHolidayModal.value = true;
};

const closeHolidayModal = () => {
    showHolidayModal.value = false;
    formHoliday.reset();
};

const submitHoliday = () => {
    const yearToKeep = selectedYearRef.value;
    const onDone = () => {
        closeHolidayModal();
        router.visit(
            route('admin.calendar_settings.index') + '?year=' + yearToKeep,
            { preserveScroll: true, preserveState: true }
        );
        activeTab.value = 'holidays';
    };

    if (isEditingHoliday.value) {
        formHoliday.put(route('admin.holidays.update', formHoliday.id), { onSuccess: onDone });
    } else {
        formHoliday.post(route('admin.holidays.store'), { onSuccess: onDone });
    }
};

const deleteHoliday = (id) => {
    if (confirm('Tem certeza que deseja excluir esta data especial?')) {
        router.delete(route('admin.holidays.destroy', id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => { activeTab.value = 'holidays'; }
        });
    }
};

// Badge de classificação de temporada
const seasonColors = {
    'SUPER ALTA':    'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
    'ALTA TEMPORADA':'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
    'MEDIA TEMPORADA':'bg-yellow-50 text-yellow-600 border-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:border-yellow-500/20',
    'BAIXA TEMPORADA':'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
    'DATAS ESPECIAIS':'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
};
const getSeasonBadge = (name) => {
    const normalized = (name || '').toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    return seasonColors[normalized] || 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
};

// Cores dos badges de classificação de feriados
const classificationBadge = (cls) => {
    if (cls === 'Data Especial')  return 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20';
    if (cls === 'Super Alta')     return 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20';
    if (cls === 'Alta Temporada') return 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20';
    return 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
};
</script>

<template>
    <Head title="Configurações de Calendário" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8 pb-12">

                <!-- Premium Header & Toolbar (Match /atendimentos) -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Configurações de Calendário</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Temporadas, Feriados e Matriz de Pontuações
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <template v-if="activeTab === 'seasons'">
                                <Link
                                    :href="route('admin.seasons.mapping')"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                    Mapeamento
                                </Link>
                                <button
                                    @click="openCreateSeasonModal"
                                    class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Nova Temporada
                                </button>
                            </template>
                            <template v-else-if="activeTab === 'holidays'">
                                <button
                                    @click="openCreateHolidayModal"
                                    class="bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    Adicionar Feriado
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Bottom Toolbar: Navigation Tabs Pill Buttons (Match /atendimentos) -->
                    <div class="px-6 py-4 bg-slate-50/40 dark:bg-slate-800/20 rounded-b-[20px] flex flex-col md:flex-row items-center justify-between gap-3 border-t border-slate-200/50 dark:border-slate-800/50">
                        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto">
                            <button
                                @click="activeTab = 'seasons'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer"
                                :class="activeTab === 'seasons' ? 'bg-brand-green text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Temporadas
                            </button>
                            <button
                                @click="activeTab = 'holidays'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer"
                                :class="activeTab === 'holidays' ? 'bg-brand-green text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                Feriados
                            </button>
                            <button
                                @click="activeTab = 'points'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 cursor-pointer"
                                :class="activeTab === 'points' ? 'bg-brand-green text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Pontuações
                            </button>
                        </div>

                        <!-- Year Filter if Holidays tab active -->
                        <div v-show="activeTab === 'holidays'" class="w-full md:w-auto">
                            <YearPicker :model-value="selectedYearRef" @update:modelValue="setSelectedYear" />
                        </div>
                    </div>
                </div>

                <!-- ===== ABA TEMPORADAS ===== -->
                <div v-show="activeTab === 'seasons'" class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-widest bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Nome</th>
                                    <th scope="col" class="px-6 py-4 text-center">Antecedência</th>
                                    <th scope="col" class="px-6 py-4">Meses / Período</th>
                                    <th scope="col" class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="season in seasons"
                                    :key="season.id"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all duration-300 group"
                                >
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-3 py-1.5 rounded-lg text-[10.5px] font-black uppercase tracking-widest border shadow-sm"
                                            :class="getSeasonBadge(season.name)"
                                        >{{ season.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/50 dark:border-slate-700/50 rounded-xl text-slate-700 dark:text-slate-300 font-black text-sm shadow-sm group-hover:scale-105 transition-transform">
                                            {{ season.advance_days }}
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">dias</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="season.period_description" class="flex flex-wrap gap-1.5">
                                            <span
                                                v-for="part in season.period_description.split(',')"
                                                :key="part"
                                                class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 rounded-md text-[10px] font-bold uppercase tracking-wider border border-slate-200/50 dark:border-slate-700/50"
                                            >{{ part.trim() }}</span>
                                        </div>
                                        <span v-else class="text-slate-400 dark:text-slate-500 italic text-[11px] font-medium">Nenhum período mapeado</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                            <button
                                                @click="openEditSeasonModal(season)"
                                                class="text-slate-400 hover:text-brand-green p-2 transition-all bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-xl hover:shadow-sm hover:-translate-y-0.5"
                                                title="Editar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <button
                                                @click="deleteSeason(season.id)"
                                                class="text-slate-400 hover:text-red-500 p-2 transition-all bg-slate-50 dark:bg-slate-800/50 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl hover:shadow-sm hover:-translate-y-0.5"
                                                title="Excluir"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!seasons?.length">
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <svg class="w-12 h-12 text-slate-200 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <p class="text-slate-400 font-medium">Nenhuma temporada cadastrada.</p>
                                            <button @click="openCreateSeasonModal" class="text-brand-green text-sm font-bold hover:underline">+ Criar temporada</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== ABA FERIADOS ===== -->
                <div v-show="activeTab === 'holidays'" class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-widest bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Feriado / Data Especial</th>
                                    <th scope="col" class="px-6 py-4 text-center">Data Feriado</th>
                                    <th scope="col" class="px-6 py-4 text-center">Período (Início / Fim)</th>
                                    <th scope="col" class="px-6 py-4 text-center">Classificação</th>
                                    <th scope="col" class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="holiday in filteredHolidays"
                                    :key="holiday.id || holiday.name"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all duration-300 group"
                                >
                                    <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                        {{ holiday.name }}
                                        <span v-if="holiday.is_template" class="ml-2 px-2 py-0.5 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-bold rounded-md border border-amber-200 dark:border-amber-500/20">Modelo</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-xs text-slate-600 dark:text-slate-300 font-semibold">
                                        {{ formatDate(holiday.holiday_date) }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-xs text-slate-600 dark:text-slate-300">
                                        <template v-if="holiday.start_date && holiday.end_date">
                                            {{ formatDate(holiday.start_date) }} a {{ formatDate(holiday.end_date) }}
                                        </template>
                                        <template v-else-if="holiday.start_date">
                                            A partir de {{ formatDate(holiday.start_date) }}
                                        </template>
                                        <template v-else>
                                            <span class="text-slate-400 italic text-[11px]">Não configurado</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            v-if="holiday.classification"
                                            class="px-3 py-1 rounded-lg text-[10.5px] font-bold uppercase tracking-wider border shadow-sm"
                                            :class="classificationBadge(holiday.classification)"
                                        >{{ holiday.classification }}</span>
                                        <span v-else class="text-slate-400 italic text-[11px]">Nenhuma</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                            <button
                                                @click="openEditHolidayModal(holiday)"
                                                class="text-slate-400 hover:text-brand-green p-2 transition-all bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-green/10 rounded-xl hover:shadow-sm hover:-translate-y-0.5"
                                                title="Configurar Data"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <button
                                                v-if="!holiday.is_template"
                                                @click="deleteHoliday(holiday.id)"
                                                class="text-slate-400 hover:text-red-500 p-2 transition-all bg-slate-50 dark:bg-slate-800/50 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl hover:shadow-sm hover:-translate-y-0.5"
                                                title="Excluir"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== ABA PONTUAÇÕES ===== -->
                <div v-show="activeTab === 'points'">
                    <PointsTab :resorts="resorts" :seasons="seasons" />
                </div>

            </div>
        </div>

        <!-- ===== MODAL TEMPORADA ===== -->
        <div v-if="showSeasonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[20px] shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg">
                        {{ isEditingSeason ? 'Editar Temporada' : 'Nova Temporada' }}
                    </h3>
                    <button @click="closeSeasonModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitSeason" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Nome da Temporada</label>
                        <input
                            type="text"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formSeason.name"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Antecedência (Dias)</label>
                        <input
                            type="number"
                            min="0"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formSeason.advance_days"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Meses / Período (Ex: JANEIRO, FEVEREIRO)</label>
                        <textarea
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formSeason.period_description"
                            rows="3"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeSeasonModal"
                            class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all"
                        >Cancelar</button>
                        <button
                            type="submit"
                            :disabled="formSeason.processing"
                            class="px-6 py-2.5 bg-brand-green hover:bg-[#485638] text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2"
                        >
                            <svg v-if="!formSeason.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ isEditingSeason ? 'Salvar Alterações' : 'Criar Temporada' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== MODAL FERIADO ===== -->
        <div v-if="showHolidayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-[20px] shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg">
                        <template v-if="isEditingHoliday">Editar Feriado</template>
                        <template v-else-if="isTemplateHoliday">Configurar Feriado</template>
                        <template v-else>Novo Feriado</template>
                    </h3>
                    <button @click="closeHolidayModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitHoliday" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Nome do Feriado</label>
                        <input
                            type="text"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formHoliday.name"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Data do Feriado</label>
                        <input
                            type="date"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formHoliday.holiday_date"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Início (Check-in)</label>
                            <input
                                type="date"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                                v-model="formHoliday.start_date"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Término (Check-out)</label>
                            <input
                                type="date"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                                v-model="formHoliday.end_date"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest px-1 mb-2">Classificação</label>
                        <select
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-brand-green/40 focus:ring-1 focus:ring-brand-green/40 shadow-sm transition-all"
                            v-model="formHoliday.classification"
                        >
                            <option value="">Nenhuma</option>
                            <option value="Data Especial">Data Especial</option>
                            <option value="Super Alta">Super Alta</option>
                            <option value="Alta Temporada">Alta Temporada</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button type="button" @click="closeHolidayModal" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formHoliday.processing" class="px-6 py-2.5 bg-brand-green hover:bg-[#485638] text-white text-xs font-bold uppercase tracking-wider rounded-[12px] transition-all shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="!formHoliday.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
