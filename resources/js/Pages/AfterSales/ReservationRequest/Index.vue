<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import { calculateStayBreakdown, getReservationPoints } from '@/Utils/reservationPoints';

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
    reservation_code: '',
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
    const cleanDate = String(dateStr).split('T')[0].split(' ')[0];
    const parts = cleanDate.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
};

const formatDateTimePt = (dateStr) => {
    if (!dateStr) return '-';
    const parts = String(dateStr).split('T');
    const datePart = parts[0].split(' ')[0];
    const timePart = parts[1] || (String(dateStr).split(' ')[1] || '');

    const dateFormatted = formatDatePt(datePart);
    if (!timePart) return dateFormatted;

    const timeFormatted = timePart.substring(0, 5);
    return `${dateFormatted} às ${timeFormatted}`;
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

const createAvailablePoints = computed(() => {
    if (!selectedService.value) return 0;
    if (selectedService.value.available_points !== undefined) {
        return selectedService.value.available_points;
    }
    return getServiceAvailablePoints(selectedService.value);
});

const createRequiredPoints = computed(() => {
    return stayBreakdown.value ? stayBreakdown.value.totalPoints : (createForm.points_used || 0);
});

const isCreatePointsInsufficient = computed(() => {
    if (!selectedService.value) return false;
    return createRequiredPoints.value > createAvailablePoints.value;
});

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitCreate = () => {
    if (!createForm.sales_service_id) {
        alert('Por favor, selecione um atendimento/contrato.');
        return;
    }
    if (stayBreakdown.value) {
        createForm.points_used = stayBreakdown.value.totalPoints;
    }
    if (isCreatePointsInsufficient.value) {
        alert(`Saldo de pontos insuficiente para este contrato! Disponível: ${createAvailablePoints.value.toLocaleString('pt-BR')} pts. Necessário: ${createRequiredPoints.value.toLocaleString('pt-BR')} pts.`);
        return;
    }
    createForm.post(route('after-sales.reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

// --- View Modal Logic ---
const isViewModalOpen = ref(false);
const viewReservation = ref(null);

const openViewModal = (reservation) => {
    viewReservation.value = reservation;
    isViewModalOpen.value = true;
};

const closeViewModal = () => {
    isViewModalOpen.value = false;
    viewReservation.value = null;
};

const viewStayBreakdown = computed(() => {
    if (!viewReservation.value) return null;
    return calculateStayBreakdownFor(viewReservation.value);
});

// --- Helper function for stay breakdown ---
const calculateStayBreakdownFor = (resData) => {
    return calculateStayBreakdown(resData, props.destinations, props.holidays);
};

// --- Edit Modal Logic ---
const isEditModalOpen = ref(false);
const selectedReservation = ref(null);

const editForm = useForm({
    destination: '',
    accommodation: '',
    check_in: '',
    check_out: '',
    adults: 1,
    children: 0,
    points_used: 0,
    guests_list: [{ name: '', type: 'adult' }],
    status: 'pending',
    reservation_code: '',
    observations: '',
});

const editAccommodations = computed(() => {
    if (!editForm.destination) return [];
    const resort = (props.destinations || []).find(d => d.name === editForm.destination);
    return resort ? resort.accommodations || [] : [];
});

const addEditGuest = () => {
    editForm.guests_list.push({ name: '', type: 'adult' });
    updateEditPaxCounts();
};

const removeEditGuest = (index) => {
    if (editForm.guests_list.length > 1) {
        editForm.guests_list.splice(index, 1);
        updateEditPaxCounts();
    }
};

const updateEditPaxCounts = () => {
    editForm.adults = editForm.guests_list.filter(g => g.type === 'adult').length;
    editForm.children = editForm.guests_list.filter(g => g.type === 'child').length;
};

const editStayBreakdown = computed(() => {
    return calculateStayBreakdownFor(editForm);
});

const openEditModal = (reservation) => {
    selectedReservation.value = reservation;
    editForm.destination = reservation.destination || '';
    editForm.accommodation = reservation.accommodation || '';
    editForm.check_in = reservation.check_in || '';
    editForm.check_out = reservation.check_out || '';
    editForm.adults = reservation.adults || 1;
    editForm.children = reservation.children || 0;
    editForm.points_used = reservation.points_used || 0;
    
    let rawGuests = reservation.guests_list;
    if (typeof rawGuests === 'string') {
        try { rawGuests = JSON.parse(rawGuests); } catch(e) { rawGuests = []; }
    }
    editForm.guests_list = Array.isArray(rawGuests) && rawGuests.length > 0 
        ? JSON.parse(JSON.stringify(rawGuests)) 
        : [{ name: '', type: 'adult' }];

    editForm.status = reservation.status || 'pending';
    editForm.reservation_code = reservation.reservation_code || '';
    editForm.observations = reservation.observations || '';
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedReservation.value = null;
};

const getServiceAvailablePoints = (service, currentReservationId = null) => {
    if (!service || !service.proposal) return 0;

    const totalPoints = parseFloat(service.proposal.quantity || 0);
    const totalValue = parseFloat(service.proposal.total_value || 0);

    const bills = service.proposal.bills || [];
    const paidAmount = bills
        .filter(b => ['entrada', 'saldo'].includes(b.category) && b.status === 'paid')
        .reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

    const ratio = totalValue > 0 ? Math.min(1, paidAmount / totalValue) : 0;
    const releasedPoints = Math.floor(totalPoints * ratio);

    const resRequests = service.reservationRequests || service.reservation_requests || [];
    const otherUsedPoints = resRequests
        .filter(r => r.status !== 'canceled' && (!currentReservationId || r.id !== currentReservationId))
        .reduce((sum, r) => sum + parseFloat(r.points_used || 0), 0);

    return Math.max(0, releasedPoints - otherUsedPoints);
};

const editAvailablePoints = computed(() => {
    if (!selectedReservation.value?.service) return 0;
    return getServiceAvailablePoints(selectedReservation.value.service, selectedReservation.value.id);
});

const editRequiredPoints = computed(() => {
    return editStayBreakdown.value ? editStayBreakdown.value.totalPoints : (editForm.points_used || 0);
});

const isEditPointsInsufficient = computed(() => {
    if (editForm.status === 'canceled') return false;
    return editRequiredPoints.value > editAvailablePoints.value;
});

const submitEdit = () => {
    if (editStayBreakdown.value) {
        editForm.points_used = editStayBreakdown.value.totalPoints;
    }

    if (editForm.status !== 'canceled' && isEditPointsInsufficient.value) {
        alert(`Saldo de pontos insuficiente para este contrato! Disponível: ${editAvailablePoints.value.toLocaleString('pt-BR')} pts. Necessário: ${editRequiredPoints.value.toLocaleString('pt-BR')} pts.`);
        return;
    }

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
        case 'confirmed': return 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'pending': return 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'analyzing': return 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'canceled': return 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800';
        default: return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};
</script>

<template>
    <Head title="Solicitações de Reserva" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8 px-4 sm:px-6 lg:px-8">
                
                <!-- Premium Header & Toolbar (Igual /atendimentos) -->
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
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Solicitações de Reserva</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5 mt-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Pós-Venda & Concierge
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto">
                            <button 
                                v-if="can('pos_venda.reservas.gerenciar')"
                                @click="openCreateModal" 
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Nova Reserva</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-6 py-4 bg-slate-50/40 dark:bg-slate-800/20 rounded-b-[20px] flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="relative flex-1 max-w-md w-full">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                v-model="form.search" 
                                placeholder="Buscar por cliente, CPF ou contrato..." 
                                class="pl-10 w-full h-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[10px] text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-green/20 focus:border-brand-green transition-all shadow-2xs"
                            >
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            Total: <span class="text-brand-green font-bold text-sm">{{ reservations.total || (reservations.data ? reservations.data.length : 0) }}</span> solicitações
                        </div>
                    </div>
                </div>

                <!-- Main Table Card Container (Igual /atendimentos) -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none overflow-hidden mb-12">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-700/80 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-4 px-6 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">ID / Solicitado em</th>
                                    <th class="py-4 px-6 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cliente / Contrato</th>
                                    <th class="py-4 px-6 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Destino & Acomodação</th>
                                    <th class="py-4 px-6 text-center text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Pontos Consumidos</th>
                                    <th class="py-4 px-6 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Período / Diárias</th>
                                    <th class="py-4 px-6 text-center text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ocupantes</th>
                                    <th class="py-4 px-6 text-center text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="py-4 px-6 text-right text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in reservations.data" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors duration-200">
                                    <td class="py-4 px-6 align-middle">
                                        <div class="flex flex-col gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-xs font-bold font-mono text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700/80 w-max shadow-2xs">
                                                #{{ String(item.id).padStart(4, '0') }}
                                            </span>
                                            <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>{{ formatDateTimePt(item.created_at) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 align-middle">
                                        <div class="font-bold text-slate-900 dark:text-white text-sm hover:text-brand-green transition-colors">
                                            {{ item.service?.client?.nome || 'Cliente não identificado' }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] font-semibold text-brand-green flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                Contrato: {{ item.service?.proposal?.contract_number || ('IVC-' + String(item.sales_service_id).padStart(4, '0')) }}
                                            </span>
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
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 font-black text-xs border border-amber-200/60 dark:border-amber-500/20 shadow-2xs">
                                            {{ getReservationPoints(item, props.destinations, props.holidays).toLocaleString('pt-BR') }} pts
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 align-middle">
                                        <div class="flex flex-col text-xs text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center gap-1 font-semibold">
                                                <span class="text-slate-800 dark:text-slate-200">{{ formatDatePt(item.check_in) }}</span>
                                                <span class="text-slate-400">→</span>
                                                <span class="text-slate-800 dark:text-slate-200">{{ formatDatePt(item.check_out) }}</span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium flex items-center gap-1">
                                                <span>{{ getNightsCount(item.check_in, item.check_out) }} {{ getNightsCount(item.check_in, item.check_out) === 1 ? 'Diária' : 'Diárias' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center align-middle">
                                        <div class="text-xs text-slate-600 dark:text-slate-400 font-semibold">
                                            {{ item.adults }} ADT <span v-if="item.children > 0">, {{ item.children }} CHD</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center align-middle">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 text-[10px] font-bold rounded-md uppercase tracking-wider border shadow-2xs" :class="getStatusColor(item.status)">
                                                {{ getStatusLabel(item.status) }}
                                            </span>
                                            <div v-if="item.reservation_code" class="inline-flex items-center gap-1 text-[10px] font-bold font-mono text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800/40 shadow-2xs" title="Código da Reserva / Voucher">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                Cód: {{ item.reservation_code }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right align-middle">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="openViewModal(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-amber-600 bg-slate-50 dark:bg-slate-800 hover:bg-amber-50 dark:hover:bg-amber-500/10 border border-slate-200 dark:border-slate-700 transition-all shadow-2xs active:scale-95" title="Visualizar Detalhes">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button v-if="can('pos_venda.reservas.gerenciar')" @click="openEditModal(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-brand-green bg-slate-50 dark:bg-slate-800 hover:bg-brand-green/10 border border-slate-200 dark:border-slate-700 transition-all shadow-2xs active:scale-95" title="Editar Reserva">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button v-if="can('pos_venda.reservas.gerenciar')" @click="confirmDelete(item)" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-600 bg-slate-50 dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-red-500/10 border border-slate-200 dark:border-slate-700 transition-all shadow-2xs active:scale-95" title="Excluir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!reservations.data || reservations.data.length === 0">
                                    <td colspan="8" class="py-16 px-6 text-center">
                                        <div class="text-slate-500 dark:text-slate-400 font-medium text-xs">Nenhuma solicitação de reserva encontrada.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="reservations.links && reservations.links.length > 3" class="p-4 sm:p-6 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-200/80 dark:border-slate-800">
                        <Pagination :links="reservations.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova Solicitação de Reserva
                    </h3>
                    <button @click="closeCreateModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>
                
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <!-- Seleção de Cliente / Contrato -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Cliente / Contrato <span class="text-rose-500">*</span>
                        </label>
                        
                        <!-- Selected Service Card -->
                        <div v-if="selectedService" class="p-3 bg-brand-green/5 dark:bg-brand-green/10 border border-brand-green/20 dark:border-brand-green/30 rounded-xl flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-brand-green/10 text-brand-green flex items-center justify-center font-bold text-sm shrink-0">
                                    ✓
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">{{ selectedService.title }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ selectedService.subtitle }}</div>
                                    <div v-if="selectedService.available_points !== undefined" class="text-[10px] font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                                        Saldo de Pontos Disponíveis: {{ selectedService.available_points.toLocaleString('pt-BR') }} pts
                                    </div>
                                </div>
                            </div>
                            <button type="button" @click="removeSelectedService" class="text-slate-400 hover:text-red-500 text-xs font-bold px-2 py-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors shrink-0">
                                Alterar
                            </button>
                        </div>

                        <!-- Autocomplete Search Input -->
                        <div v-else class="relative">
                            <input 
                                type="text" 
                                v-model="searchServiceQuery" 
                                placeholder="Digite o nome do cliente, CPF ou número do contrato..." 
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium"
                            />
                            <div v-if="isSearchingService" class="absolute right-3 top-2.5 text-xs text-slate-400">
                                Buscando...
                            </div>
                            <!-- Dropdown Results -->
                            <div v-if="searchResults.length > 0" class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                <button 
                                    v-for="service in searchResults" 
                                    :key="service.id"
                                    type="button" 
                                    @click="selectService(service)"
                                    class="w-full px-4 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex items-center justify-between"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">{{ service.title }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ service.subtitle }}</div>
                                    </div>
                                    <span v-if="service.available_points !== undefined" class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 shrink-0">
                                        {{ service.available_points.toLocaleString('pt-BR') }} pts
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div v-if="createForm.errors.sales_service_id" class="text-xs text-rose-500 mt-1 font-medium">{{ createForm.errors.sales_service_id }}</div>
                    </div>

                    <!-- Insufficient Points Warning Banner -->
                    <div v-if="isCreatePointsInsufficient" class="p-3.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-xl flex items-center justify-between gap-3 text-xs text-rose-700 dark:text-rose-400 font-bold shadow-2xs">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Saldo de Pontos Insuficiente! O contrato dispõe de {{ createAvailablePoints.toLocaleString('pt-BR') }} pts, mas a solicitação exige {{ createRequiredPoints.toLocaleString('pt-BR') }} pts.</span>
                        </div>
                    </div>

                    <!-- Grid Destino & Acomodação -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Destino / Resort <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="createForm.destination" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium">
                                <option value="" disabled>Selecione um Destino</option>
                                <option v-for="dest in props.destinations" :key="dest.id" :value="dest.name">{{ dest.name }}</option>
                            </select>
                            <div v-if="createForm.errors.destination" class="text-xs text-rose-500 mt-1 font-medium">{{ createForm.errors.destination }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Acomodação / Suíte</label>
                            <select v-model="createForm.accommodation" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" :disabled="!createForm.destination">
                                <option value="" disabled>Selecione a Acomodação</option>
                                <option v-for="acc in accommodationOptions" :key="acc.value" :value="acc.value">{{ acc.label }}</option>
                            </select>
                            <div v-if="createForm.errors.accommodation" class="text-xs text-rose-500 mt-1 font-medium">{{ createForm.errors.accommodation }}</div>
                        </div>
                    </div>

                    <!-- Grid Período Check-in / Check-out -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Data Check-in <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" v-model="createForm.check_in" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                            <div v-if="createForm.errors.check_in" class="text-xs text-rose-500 mt-1 font-medium">{{ createForm.errors.check_in }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Data Check-out <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" v-model="createForm.check_out" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                            <div v-if="createForm.errors.check_out" class="text-xs text-rose-500 mt-1 font-medium">{{ createForm.errors.check_out }}</div>
                        </div>
                    </div>

                    <!-- Previsão do Fracionamento de Diárias -->
                    <div v-if="stayBreakdown" class="p-3 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-200">Total Estada: {{ stayBreakdown.nights }} {{ stayBreakdown.nights === 1 ? 'Diária' : 'Diárias' }}</span>
                            <span class="font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">{{ stayBreakdown.totalPoints.toLocaleString('pt-BR') }} pts estimados</span>
                        </div>
                        <div v-if="stayBreakdown.dailyList && stayBreakdown.dailyList.length > 0" class="max-h-28 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-[11px]">
                            <div v-for="(day, idx) in stayBreakdown.dailyList" :key="idx" class="py-1 flex justify-between">
                                <span>{{ day.date }} - {{ day.isHoliday ? 'Feriado: ' + day.holidayName : day.seasonName }}</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ day.points }} pts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hóspedes Informados -->
                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Hóspedes Registrados</label>
                            <button type="button" @click="addGuest" class="text-xs font-bold text-brand-green hover:underline flex items-center gap-1">
                                + Adicionar Hóspede
                            </button>
                        </div>
                        <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                            <div v-for="(guest, gIdx) in createForm.guests_list" :key="gIdx" class="flex items-center gap-2">
                                <input type="text" v-model="guest.name" placeholder="Nome Completo do Hóspede" class="flex-1 rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs" />
                                <select v-model="guest.type" @change="updateCounts" class="w-28 rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                                    <option value="adult">Adulto</option>
                                    <option value="child">Criança</option>
                                </select>
                                <button type="button" @click="removeGuest(gIdx)" :disabled="createForm.guests_list.length === 1" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg disabled:opacity-30">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Código da Reserva / Voucher (Se houver)</label>
                        <input type="text" v-model="createForm.reservation_code" placeholder="Ex: RES-123456 ou Voucher N°" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium mb-3" />

                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Observações Internas</label>
                        <textarea v-model="createForm.observations" rows="3" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs" placeholder="Adicione notas ou observações sobre esta reserva..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeCreateModal" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-xs font-bold">Cancelar</button>
                        <button type="submit" :disabled="createForm.processing || !createForm.sales_service_id || isCreatePointsInsufficient" class="px-5 py-2 bg-brand-green text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50 text-xs font-bold">Criar Solicitação</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Solicitação de Reserva #{{ selectedReservation?.id }}
                    </h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>
                
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <!-- Insufficient Points Warning Banner -->
                    <div v-if="isEditPointsInsufficient" class="p-3.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-xl flex items-center justify-between gap-3 text-xs text-rose-700 dark:text-rose-400 font-bold shadow-2xs">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Saldo de Pontos Insuficiente! O contrato dispõe de {{ editAvailablePoints.toLocaleString('pt-BR') }} pts, mas a alteração exige {{ editRequiredPoints.toLocaleString('pt-BR') }} pts.</span>
                        </div>
                    </div>
                    <!-- Grid Destino & Acomodação -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Destino / Resort</label>
                            <select v-model="editForm.destination" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium">
                                <option value="" disabled>Selecione um Destino</option>
                                <option v-for="dest in props.destinations" :key="dest.id" :value="dest.name">{{ dest.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Acomodação / Suíte</label>
                            <select v-model="editForm.accommodation" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" :disabled="!editForm.destination">
                                <option value="" disabled>Selecione a Acomodação</option>
                                <option v-for="acc in editAccommodations" :key="acc.id" :value="acc.name">{{ acc.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Grid Período Check-in / Check-out -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Data Check-in</label>
                            <input type="date" v-model="editForm.check_in" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Data Check-out</label>
                            <input type="date" v-model="editForm.check_out" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                        </div>
                    </div>

                    <!-- Previsão do Fracionamento de Diárias -->
                    <div v-if="editStayBreakdown" class="p-3 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-200">Total Estada: {{ editStayBreakdown.nights }} {{ editStayBreakdown.nights === 1 ? 'Diária' : 'Diárias' }}</span>
                            <span class="font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">{{ editStayBreakdown.totalPoints.toLocaleString('pt-BR') }} pts estimados</span>
                        </div>
                        <div v-if="editStayBreakdown.dailyList && editStayBreakdown.dailyList.length > 0" class="max-h-28 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-[11px]">
                            <div v-for="(day, idx) in editStayBreakdown.dailyList" :key="idx" class="py-1 flex justify-between">
                                <span>{{ day.date }} - {{ day.isHoliday ? 'Feriado: ' + day.holidayName : day.seasonName }}</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ day.points }} pts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pontos, Status & Código da Reserva -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Pontos Consumidos</label>
                            <input type="number" v-model.number="editForm.points_used" min="0" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status da Reserva</label>
                            <select v-model="editForm.status" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium">
                                <option value="pending">Pendente</option>
                                <option value="analyzing">Em Análise</option>
                                <option value="confirmed">Confirmada</option>
                                <option value="canceled">Cancelada</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Código / Voucher</label>
                            <input type="text" v-model="editForm.reservation_code" placeholder="Ex: RES-987654" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs font-medium" />
                        </div>
                    </div>

                    <!-- Aviso de confirmação -->
                    <div v-if="editForm.status === 'confirmed'" class="p-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Reserva Confirmada! Informe o código da reserva ou voucher fornecido pelo hotel no campo acima.</span>
                    </div>

                    <!-- Hóspedes Informados -->
                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Hóspedes Registrados</label>
                            <button type="button" @click="addEditGuest" class="text-xs font-bold text-brand-green hover:underline flex items-center gap-1">
                                + Adicionar Hóspede
                            </button>
                        </div>
                        <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                            <div v-for="(guest, gIdx) in editForm.guests_list" :key="gIdx" class="flex items-center gap-2">
                                <input type="text" v-model="guest.name" placeholder="Nome Completo do Hóspede" class="flex-1 rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs" />
                                <select v-model="guest.type" @change="updateEditPaxCounts" class="w-28 rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                                    <option value="adult">Adulto</option>
                                    <option value="child">Criança</option>
                                </select>
                                <button type="button" @click="removeEditGuest(gIdx)" :disabled="editForm.guests_list.length === 1" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg disabled:opacity-30">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Observações Internas</label>
                        <textarea v-model="editForm.observations" rows="3" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green text-xs" placeholder="Adicione notas ou observações sobre esta reserva..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-xs font-bold">Cancelar</button>
                        <button type="submit" :disabled="editForm.processing || (isEditPointsInsufficient && editForm.status !== 'canceled')" class="px-5 py-2 bg-brand-green text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50 text-xs font-bold">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- View Details Modal -->
        <Modal :show="isViewModalOpen" @close="closeViewModal" maxWidth="2xl">
            <div v-if="viewReservation" class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                                Solicitação de Reserva #{{ viewReservation.id }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                Cliente: {{ viewReservation.service?.client?.nome || '—' }} (Contrato: {{ viewReservation.service?.proposal?.contract_number || ('IVC-' + String(viewReservation.sales_service_id).padStart(4, '0')) }})
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-md border" :class="getStatusColor(viewReservation.status)">
                            {{ getStatusLabel(viewReservation.status) }}
                        </span>
                        <button @click="closeViewModal" class="text-slate-400 hover:text-slate-500 dark:text-slate-500 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                            ✕
                        </button>
                    </div>
                </div>

                <div class="mt-6 space-y-6">
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Destino</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ viewReservation.destination || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Acomodação</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ viewReservation.accommodation || 'Não informada' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Período de Estada</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                {{ formatDatePt(viewReservation.check_in) }} → {{ formatDatePt(viewReservation.check_out) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Pontos Consumidos</span>
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                {{ (viewReservation.points_used || 0).toLocaleString('pt-BR') }} pts
                            </span>
                        </div>
                        <div v-if="viewReservation.reservation_code" class="col-span-2 pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                Código / Voucher da Reserva:
                            </span>
                            <span class="text-xs font-bold font-mono text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800/40 shadow-2xs">
                                {{ viewReservation.reservation_code }}
                            </span>
                        </div>
                    </div>

                    <!-- Resumo das Diárias -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                                Resumo das Diárias ({{ viewStayBreakdown?.nights || getNightsCount(viewReservation.check_in, viewReservation.check_out) }} Diárias)
                            </h4>
                            <span v-if="viewStayBreakdown?.seasonName" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                Temporada / Evento: <strong class="text-slate-800 dark:text-slate-200">{{ viewStayBreakdown.seasonName }}</strong>
                            </span>
                        </div>

                        <div v-if="viewStayBreakdown && viewStayBreakdown.dailyList && viewStayBreakdown.dailyList.length > 0" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800 max-h-48 overflow-y-auto shadow-2xs">
                            <div v-for="(day, idx) in viewStayBreakdown.dailyList" :key="idx" class="px-4 py-2.5 flex items-center justify-between text-xs bg-white dark:bg-slate-900">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ day.date }}</span>
                                    <span v-if="day.isHoliday" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40">
                                        Feriado: {{ day.holidayName }}
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60">
                                        Tipo: {{ day.seasonName }}
                                    </span>
                                </div>
                                <div class="font-bold text-amber-600 dark:text-amber-400">
                                    {{ day.points.toLocaleString('pt-BR') }} pts / dia
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center border border-slate-200/50 dark:border-slate-800">
                            Cálculo de fracionamento das diárias estimado com base na tabela do resort.
                        </div>
                    </div>

                    <!-- Ocupantes & Hóspedes -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                Ocupantes ({{ (viewReservation.adults || 0) + (viewReservation.children || 0) }})
                            </h4>
                            <span class="text-[11px] font-semibold text-slate-500">
                                {{ viewReservation.adults || 0 }} Adulto(s), {{ viewReservation.children || 0 }} Criança(s)
                            </span>
                        </div>

                        <div v-if="viewReservation.guests_list && viewReservation.guests_list.length > 0" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                            <div v-for="(guest, gIdx) in viewReservation.guests_list" :key="gIdx" class="px-4 py-2.5 flex items-center justify-between text-xs bg-white dark:bg-slate-900">
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ guest.name || ('Hóspede ' + (gIdx + 1)) }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    {{ guest.type === 'child' ? 'Criança' : 'Adulto' }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center border border-slate-200/50 dark:border-slate-800">
                            Nenhuma lista individualizada de hóspedes informada.
                        </div>
                    </div>

                    <div v-if="viewReservation.observations" class="space-y-1">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Observações Internas</h4>
                        <div class="p-3 bg-amber-50/50 dark:bg-amber-500/5 border border-amber-200/60 dark:border-amber-500/20 rounded-xl text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                            {{ viewReservation.observations }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <button @click="closeViewModal" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Fechar
                    </button>
                    
                </div>
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
