<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    reservations: Object,
    filters: Object,
    destinations: {
        type: Array,
        default: () => []
    },
    holidays: {
        type: Array,
        default: () => []
    },
});

const isCreateModalOpen = ref(false);
const createForm = useForm({
    sales_service_id: '',
    destination: '',
    accommodation: '',
    points_used: 0,
    check_in: '',
    check_out: '',
    adults: 1,
    children: 0,
    guests_list: [{ name: '', type: 'adult' }],
    observations: '',
});

const destinationOptions = computed(() => {
    return (props.destinations || []).map(d => ({
        label: d.name,
        value: d.name
    }));
});


const stayBreakdown = computed(() => {
    if (!createForm.destination || !createForm.accommodation || !createForm.check_in || !createForm.check_out) {
        return null;
    }

    const start = new Date(createForm.check_in + 'T00:00:00');
    const end = new Date(createForm.check_out + 'T00:00:00');
    
    const diffTime = end.getTime() - start.getTime();
    if (diffTime <= 0) return null;
    
    const nights = Math.ceil(diffTime / (1000 * 3600 * 24));
    if (nights <= 0) return null;

    const resort = (props.destinations || []).find(d => d.name === createForm.destination);
    if (!resort || !resort.accommodations) return null;

    const acc = resort.accommodations.find(a => a.name === createForm.accommodation);
    if (!acc || !acc.scores || acc.scores.length === 0) return null;

    const currentPax = Math.max(1, (createForm.adults || 1) + (createForm.children || 0));

    const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];

    let totalPoints = 0;
    const dailyList = [];
    let weeklyBasePoints = 0;
    let mainSeasonName = '';
    let hasHolidayInStay = false;

    for (let i = 0; i < nights; i++) {
        const currentDate = new Date(start);
        currentDate.setDate(currentDate.getDate() + i);

        // Date ISO YYYY-MM-DD
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getDate()).padStart(2, '0');
        const currentISO = `${year}-${month}-${day}`;
        const monthName = monthNames[currentDate.getMonth()];
        const dateStr = currentDate.toLocaleDateString('pt-BR');

        // Check holiday match
        const holidayMatch = (props.holidays || []).find(h => {
            const hDate = h.holiday_date ? String(h.holiday_date).split('T')[0] : null;
            const hStart = h.start_date ? String(h.start_date).split('T')[0] : hDate;
            const hEnd = h.end_date ? String(h.end_date).split('T')[0] : hDate;

            if (hStart && hEnd) {
                return currentISO >= hStart && currentISO <= hEnd;
            }
            return hDate === currentISO;
        });

        let targetSeasonName = null;
        let holidayName = null;

        if (holidayMatch) {
            hasHolidayInStay = true;
            holidayName = holidayMatch.name;
            targetSeasonName = holidayMatch.classification;
        }

        let matchedScore = null;

        // Try holiday classification season
        if (targetSeasonName) {
            matchedScore = acc.scores.find(s => {
                return s.season && String(s.season.name).toLowerCase() === String(targetSeasonName).toLowerCase() && s.pax === currentPax;
            });
            if (!matchedScore) {
                matchedScore = acc.scores.find(s => {
                    return s.season && String(s.season.name).toLowerCase() === String(targetSeasonName).toLowerCase();
                });
            }
        }

        // Regular month season match
        if (!matchedScore) {
            matchedScore = acc.scores.find(s => {
                if (!s.season || !s.season.months_active) return false;
                let months = s.season.months_active;
                if (typeof months === 'string') {
                    try { months = JSON.parse(months); } catch(e) { months = []; }
                }
                if (!Array.isArray(months)) months = [];
                return months.some(m => String(m).toLowerCase() === monthName.toLowerCase()) && s.pax === currentPax;
            });
        }

        if (!matchedScore) {
            matchedScore = acc.scores.find(s => {
                if (!s.season || !s.season.months_active) return false;
                let months = s.season.months_active;
                if (typeof months === 'string') {
                    try { months = JSON.parse(months); } catch(e) { months = []; }
                }
                if (!Array.isArray(months)) months = [];
                return months.some(m => String(m).toLowerCase() === monthName.toLowerCase());
            });
        }

        if (!matchedScore) {
            matchedScore = acc.scores.find(s => s.pax === currentPax) || acc.scores[0];
        }

        const baseWeekly = parseFloat(matchedScore.points || matchedScore.points_raw || 0);
        const dailyPoints = Math.round(baseWeekly / 7);
        totalPoints += dailyPoints;

        const effectiveSeasonName = matchedScore.season ? matchedScore.season.name : (targetSeasonName || 'Padrão');

        if (i === 0) {
            weeklyBasePoints = baseWeekly;
            mainSeasonName = holidayName ? `${holidayName} (${effectiveSeasonName})` : effectiveSeasonName;
        }

        dailyList.push({
            date: dateStr,
            seasonName: effectiveSeasonName,
            holidayName: holidayName,
            isHoliday: !!holidayMatch,
            points: dailyPoints
        });
    }

    return {
        nights,
        totalPoints,
        weeklyBasePoints,
        seasonName: mainSeasonName,
        hasHolidayInStay,
        dailyList
    };
});

const accommodationOptions = computed(() => {
    if (!createForm.destination) return [];
    const selectedResort = (props.destinations || []).find(d => d.name === createForm.destination);
    if (!selectedResort || !selectedResort.accommodations) return [];
    return selectedResort.accommodations.map(a => ({
        label: a.name,
        value: a.name
    }));
});

watch(() => createForm.destination, (newVal) => {
    const valid = accommodationOptions.value.some(opt => opt.value === createForm.accommodation);
    if (!valid) {
        createForm.accommodation = '';
    }
});


const getNightsCount = (checkIn, checkOut) => {
    if (!checkIn || !checkOut) return 0;
    const start = new Date(checkIn + 'T00:00:00');
    const end = new Date(checkOut + 'T00:00:00');
    const diff = end.getTime() - start.getTime();
    return diff > 0 ? Math.ceil(diff / (1000 * 3600 * 24)) : 0;
};

const formatDatePt = (dateStr) => {
    if (!dateStr) return '-';
    const parts = String(dateStr).split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
};

const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};

const form = ref({
    search: props.filters?.search || '',
});

const applyFilters = debounce(() => {
    router.get(
        route('after-sales.reservations.index'),
        form.value,
        { preserveState: true, preserveScroll: true }
    );
}, 300);

watch(() => form.value, applyFilters, { deep: true });

const clearFilters = () => {
    form.value.search = '';
    applyFilters();
};



// Autocomplete Logic
const searchServiceQuery = ref('');
const searchResults = ref([]);
const isSearchingService = ref(false);
const selectedService = ref(null);

const searchService = debounce(async () => {
    if (!searchServiceQuery.value || searchServiceQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    
    isSearchingService.value = true;
    try {
        const response = await axios.get(route('api.search.global'), { params: { q: searchServiceQuery.value, require_contract: true } });
        searchResults.value = response.data;
    } catch (error) {
        console.error("Error searching services:", error);
    } finally {
        isSearchingService.value = false;
    }
}, 400);
watch(searchServiceQuery, searchService);

const selectService = (service) => {
    selectedService.value = service;
    searchServiceQuery.value = '';
    searchResults.value = [];
    createForm.sales_service_id = service.id;
};

const removeSelectedService = () => {
    selectedService.value = null;
    createForm.sales_service_id = '';
};

const addGuest = () => {
    createForm.guests_list.push({ name: '', type: 'adult' });
    updateCounts();
};

const removeGuest = (index) => {
    createForm.guests_list.splice(index, 1);
    updateCounts();
};

const updateCounts = () => {
    createForm.adults = createForm.guests_list.filter(g => g.type === 'adult').length;
    createForm.children = createForm.guests_list.filter(g => g.type === 'child').length;
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.guests_list = [{ name: '', type: 'adult' }];
    updateCounts();
    selectedService.value = null;
    searchServiceQuery.value = '';
    searchResults.value = [];
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitCreate = () => {
    if (stayBreakdown.value) {
        createForm.points_used = stayBreakdown.value.totalPoints;
    }
    createForm.post(route('after-sales.reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

const isEditModalOpen = ref(false);
const selectedReservation = ref(null);
const editForm = useForm({
    status: '',
    observations: '',
});

const openEditModal = (reservation) => {
    selectedReservation.value = reservation;
    editForm.status = reservation.status;
    editForm.observations = reservation.observations;
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedReservation.value = null;
};

const submitEdit = () => {
    editForm.put(route('after-sales.reservations.update', selectedReservation.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        }
    });
};

const isDeleteModalOpen = ref(false);
const reservationToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (reservation) => {
    reservationToDelete.value = reservation;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (reservationToDelete.value) {
        deleteForm.delete(route('after-sales.reservations.destroy', reservationToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                reservationToDelete.value = null;
            }
        });
    }
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Pendente',
        analyzing: 'Em Análise',
        confirmed: 'Confirmada',
        canceled: 'Cancelada'
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400';
        case 'analyzing': return 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400';
        case 'confirmed': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-400';
        case 'canceled': return 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400';
        default: return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
    }
};
</script>

<template>
    <Head title="Solicitações de Reserva" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-green to-emerald-600 flex items-center justify-center text-white shadow-lg shadow-brand-green/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-500 dark:from-white dark:to-slate-400">
                            Solicitações de Reserva
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Gestão de pedidos de reserva dos clientes</p>
                    </div>
                </div>
                <div>
                    <button v-if="can('pos_venda.reservas.gerenciar')" @click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-brand-green hover:bg-emerald-600 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg shadow-brand-green/30 hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Nova Reserva
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 animate-fade-in-up" style="animation-delay: 0.1s">
            
            <!-- Filters -->
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-lg shadow-slate-200/40 dark:shadow-none border border-slate-200/50 dark:border-slate-700/50 p-5 mb-6">
                <div class="flex flex-col gap-4">
                    <div class="w-full relative group">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Pesquisar por Cliente ou CPF</label>
                        <div class="relative flex items-center">
                            <div class="absolute left-3 text-slate-400 group-focus-within:text-brand-green transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="form.search" placeholder="Buscar..." class="pl-10 w-full md:w-1/3 h-11 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all duration-300">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl shadow-xl shadow-slate-200/30 dark:shadow-none border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/80 backdrop-blur-md">
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">ID</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Cliente / Contrato</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Destino & Acomodação</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Pontos Utilizados</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Período</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Hóspedes</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Status</th>
                                <th class="py-4 px-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in reservations.data" :key="item.id" class="group relative hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors duration-300">
                                <td class="py-4 px-6 align-middle">
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">#{{ item.id }}</div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                            {{ item.service?.client?.name || item.service?.client?.nome || 'Cliente não informado' }}
                                        </div>
                                        <div class="text-xs font-semibold text-brand-green mt-0.5 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Contrato: {{ item.service?.proposal?.contract_number || ('IVC-' + String(item.sales_service_id).padStart(4, '0')) }}
                                        </div>
                                        <span v-if="item.service?.client?.cpf" class="text-[11px] text-slate-400 font-medium">CPF: {{ item.service.client.cpf }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                        {{ item.destination }}
                                    </div>
                                    <div v-if="item.accommodation" class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        {{ item.accommodation }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 font-black text-xs border border-amber-200/60 dark:border-amber-500/20">
                                            {{ (item.points_used || 0).toLocaleString('pt-BR') }} pts
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <div class="flex flex-col text-xs text-slate-600 dark:text-slate-400">
                                        <div class="flex items-center gap-1">
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatDatePt(item.check_in) }}</span>
                                            <span class="text-slate-400">→</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatDatePt(item.check_out) }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                            {{ getNightsCount(item.check_in, item.check_out) }} {{ getNightsCount(item.check_in, item.check_out) === 1 ? 'Diária' : 'Diárias' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <div class="text-xs text-slate-600 dark:text-slate-400 font-semibold">
                                        {{ item.adults }} ADT <span v-if="item.children > 0">, {{ item.children }} CHD</span>
                                        <div v-if="item.guests_list && item.guests_list.length > 0" class="mt-1 flex -space-x-1 justify-center" title="Hóspedes informados">
                                            <div class="w-5 h-5 rounded-full bg-brand-green/20 text-brand-green flex items-center justify-center text-[10px] font-bold border border-white dark:border-slate-800" :title="item.guests_list.map(g => g.name).join(', ')">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <span :class="['inline-flex items-center justify-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider', getStatusColor(item.status)]">
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right align-middle">
                                    <div class="flex items-center justify-end gap-2 opacity-70 group-hover:opacity-100 transition-opacity">
                                        <button v-if="can('pos_venda.reservas.gerenciar')" @click="openEditModal(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-brand-green bg-white hover:bg-brand-green/10 border border-slate-200 transition-all shadow-sm" title="Editar Status">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button v-if="can('pos_venda.reservas.gerenciar')" @click="confirmDelete(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-600 bg-white hover:bg-red-50 border border-slate-200 transition-all shadow-sm" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="reservations.data.length === 0">
                                <td colspan="8" class="py-16 px-6 text-center">
                                    <div class="text-slate-500">Nenhuma solicitação de reserva encontrada.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="reservations.links && reservations.links.length > 3" class="p-4 sm:p-6 bg-slate-50/50 border-t border-slate-200">
                    <Pagination :links="reservations.links" />
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="2xl">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Nova Solicitação de Reserva</h3>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    
                    <!-- Search Service Selection -->
                    <div class="mb-6 relative z-30">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Vincular ao Cliente / Atendimento <span class="text-red-500">*</span></label>
                        
                        <div v-if="!selectedService" class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400 group-focus-within:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="searchServiceQuery" 
                                placeholder="Digite nome, CPF ou número do contrato..." 
                                class="w-full h-12 pl-12 pr-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all shadow-sm placeholder-slate-400"
                            >
                            <div v-if="isSearchingService" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <svg class="animate-spin h-5 w-5 text-brand-green" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                            
                            <!-- Results Dropdown -->
                            <div v-if="searchResults.length > 0 && !selectedService" class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-800 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-700 rounded-xl max-h-72 overflow-y-auto custom-scrollbar">
                                <button 
                                    type="button" 
                                    v-for="result in searchResults" 
                                    :key="result.id" 
                                    @click="selectService(result)"
                                    class="w-full text-left px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex items-center justify-between group"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-green group-hover:text-white transition-all shadow-sm">
                                            <span class="text-slate-500 dark:text-slate-300 font-bold text-sm group-hover:text-white transition-colors">{{ result.title.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-brand-green transition-colors">{{ result.title }}</div>
                                            <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">{{ result.subtitle }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right shrink-0">
                                            <div class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                                {{ (result.available_points ?? (result.total_points ?? 0)).toLocaleString('pt-BR') }} pts
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium">Pontos Disp.</div>
                                        </div>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:text-brand-green group-hover:bg-brand-green/10 transition-all opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            
                            <div v-else-if="searchServiceQuery.length >= 2 && !isSearchingService" class="mt-4 bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">Nenhum contrato encontrado</div>
                                <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">Verifique se o termo digitado está correto.</div>
                            </div>
                        </div>
                        
                        <div v-else class="p-5 bg-gradient-to-br from-emerald-50/80 via-white to-amber-50/50 dark:from-emerald-950/20 dark:via-slate-900 dark:to-amber-950/10 border border-emerald-500/30 dark:border-emerald-500/20 rounded-2xl shadow-md">
                            <div class="flex items-center justify-between gap-4 pb-3 border-b border-slate-200/60 dark:border-slate-800">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-brand-green to-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-brand-green/20">
                                        {{ selectedService.title.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ selectedService.title }}</div>
                                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            {{ selectedService.subtitle }}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="removeSelectedService" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-all" title="Trocar Cliente">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Pontuação Disponível em Destaque -->
                            <div class="mt-3 pt-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-amber-50 to-emerald-50 dark:from-amber-950/30 dark:to-emerald-950/30 p-3.5 rounded-xl border border-amber-300/40 dark:border-amber-500/30 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Pontuação Disponível</div>
                                        <div class="text-xl font-black text-amber-600 dark:text-amber-400 tracking-tight leading-tight">
                                            {{ (selectedService.available_points ?? 0).toLocaleString('pt-BR') }} <span class="text-xs font-bold text-slate-500 dark:text-slate-400">pts</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white/80 dark:bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-200/50 dark:border-slate-700/50 self-start sm:self-auto">
                                    <span>Liberados: <strong class="text-amber-600 dark:text-amber-400">{{ (selectedService.released_points ?? 0).toLocaleString('pt-BR') }}</strong></span>
                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                    <span>Total: <strong>{{ (selectedService.total_points ?? 0).toLocaleString('pt-BR') }}</strong></span>
                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                    <span>Utilizados: <strong>{{ (selectedService.used_points ?? 0).toLocaleString('pt-BR') }}</strong></span>
                                </div>
                            </div>
                            <div v-if="selectedService.available_points === 0" class="mt-2 text-xs text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Contrato sem pontuação liberada (pagamento de entrada/saldo pendente).
                            </div>
                        </div>
                        <div v-if="createForm.errors.sales_service_id" class="text-sm text-red-600 mt-1">{{ createForm.errors.sales_service_id }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <SearchableSelect
                                v-model="createForm.destination"
                                :options="destinationOptions"
                                label="Destino / Empreendimento"
                                placeholder="Selecione o destino..."
                                :error="createForm.errors.destination"
                                required
                            />
                        </div>
                        <div>
                            <SearchableSelect
                                v-model="createForm.accommodation"
                                :options="accommodationOptions"
                                label="Tipo de Acomodação"
                                :placeholder="createForm.destination ? 'Selecione a acomodação...' : 'Selecione o destino primeiro'"
                                :disabled="!createForm.destination"
                                :error="createForm.errors.accommodation"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Check-in</label>
                            <input type="date" v-model="createForm.check_in" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm">
                            <div v-if="createForm.errors.check_in" class="text-sm text-red-600 mt-1">{{ createForm.errors.check_in }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Check-out</label>
                            <input type="date" v-model="createForm.check_out" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm">
                            <div v-if="createForm.errors.check_out" class="text-sm text-red-600 mt-1">{{ createForm.errors.check_out }}</div>
                        </div>
                    </div>

                    <!-- Stay Breakdown Card -->
                    <div v-if="stayBreakdown" class="p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/60 rounded-2xl animate-fade-in">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-sm shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Resumo das Diárias ({{ stayBreakdown.nights }} {{ stayBreakdown.nights === 1 ? 'Diária' : 'Diárias' }})</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ stayBreakdown.seasonName }} • Fracionado por diária (Base 7 dias)</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Total Necessário</span>
                                <span class="text-base font-black text-brand-green dark:text-emerald-400">{{ stayBreakdown.totalPoints.toLocaleString('pt-BR') }} pts</span>
                            </div>
                        </div>

                        <!-- Lista Detalhada das Diárias -->
                        <div class="mt-3 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar pr-1">
                            <div v-for="(day, idx) in stayBreakdown.dailyList" :key="idx" class="flex items-center justify-between py-1.5 px-3 bg-white dark:bg-slate-800/80 rounded-xl text-xs border border-slate-100 dark:border-slate-700/50 shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">1x Diária - {{ day.date }}</span>
                                    <span v-if="day.isHoliday" class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                        🎉 {{ day.holidayName }} ({{ day.seasonName }})
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        {{ day.seasonName }}
                                    </span>
                                </div>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ day.points.toLocaleString('pt-BR') }} pts</span>
                            </div>
                        </div>

                        <!-- Resumo e Validação do Saldo do Cliente -->
                        <div class="mt-3 pt-2.5 border-t border-slate-200/80 dark:border-slate-700/80 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Base Semanal (7 dias): <strong>{{ stayBreakdown.weeklyBasePoints.toLocaleString('pt-BR') }} pts</strong></span>
                            <div v-if="selectedService">
                                <span v-if="selectedService.available_points >= stayBreakdown.totalPoints" class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Saldo Suficiente
                                </span>
                                <span v-else class="px-2.5 py-1 rounded-lg bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 font-bold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Saldo Insuficiente (Falta {{ (stayBreakdown.totalPoints - selectedService.available_points).toLocaleString('pt-BR') }} pts)
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dynamic Guests List -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Nomes dos Hóspedes</label>
                            <span class="text-xs text-slate-500 font-medium">Total: {{ createForm.adults }} Adultos, {{ createForm.children }} Crianças</span>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="(guest, index) in createForm.guests_list" :key="index" class="flex items-center gap-3">
                                <input type="text" v-model="guest.name" placeholder="Nome Completo" required class="flex-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm">
                                
                                <select v-model="guest.type" @change="updateCounts" class="w-32 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm">
                                    <option value="adult">Adulto</option>
                                    <option value="child">Criança</option>
                                </select>

                                <button type="button" @click="removeGuest(index)" :disabled="createForm.guests_list.length === 1" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" @click="addGuest" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-green hover:text-emerald-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Adicionar Hóspede
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observações</label>
                        <textarea v-model="createForm.observations" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm"></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeCreateModal" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="createForm.processing || !selectedService" class="px-4 py-2 bg-brand-green text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50">Salvar Solicitação</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Atualizar Solicitação</h3>
                
                <div v-if="selectedReservation?.guests_list" class="mb-4 bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Hóspedes Informados</h4>
                    <ul class="text-sm space-y-1">
                        <li v-for="(guest, idx) in selectedReservation.guests_list" :key="idx" class="flex justify-between">
                            <span class="text-slate-700 font-medium">{{ guest.name }}</span>
                            <span class="text-xs text-slate-500 bg-slate-200 px-2 py-0.5 rounded">{{ guest.type === 'adult' ? 'Adulto' : 'Criança' }}</span>
                        </li>
                    </ul>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                        <select v-model="editForm.status" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm">
                            <option value="pending">Pendente</option>
                            <option value="analyzing">Em Análise</option>
                            <option value="confirmed">Confirmada</option>
                            <option value="canceled">Cancelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observações Internas (Atualização)</label>
                        <textarea v-model="editForm.observations" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green sm:text-sm"></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 bg-brand-green text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" maxWidth="md">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Excluir Solicitação de Reserva?</h3>
                <p class="text-sm text-slate-500 mb-6">Tem certeza que deseja excluir esta solicitação? Esta ação não poderá ser desfeita.</p>
                <div class="flex gap-3 justify-center">
                    <button @click="isDeleteModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancelar</button>
                    <button @click="executeDelete" :disabled="deleteForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors disabled:opacity-50">Excluir Solicitação</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
