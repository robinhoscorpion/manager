<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    schedules: Array,
    metrics: Object,
    filters: Object,
});

const schedules = computed(() => props.schedules || []);
const metrics = computed(() => props.metrics || { today: 0, scheduled: 0, confirmed: 0 });

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const today = new Date().toISOString().split('T')[0];
const startDate = ref(props.filters?.start_date || today);
const endDate = ref(props.filters?.end_date || today);

const fetchFilteredData = () => {
    router.get(route('sales.agendamentos.index'), {
        start_date: startDate.value,
        end_date: endDate.value,
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearDates = () => {
    startDate.value = '';
    endDate.value = '';
    fetchFilteredData();
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const [year, month, day] = dateStr.split('-');
        return `${day}/${month}/${year}`;
    } catch (e) {
        return dateStr;
    }
};

const formatTime = (timeStr) => {
    if (!timeStr) return '-';
    return timeStr.substring(0, 5);
};

const getStatusBadge = (status) => {
    const badges = {
        'scheduled': { label: 'Agendado', color: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20' },
        'confirmed': { label: 'Confirmado', color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' },
        'show': { label: 'Compareceu', color: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20' },
        'no_show': { label: 'No-Show', color: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20' },
        'cancelled': { label: 'Cancelado', color: 'bg-slate-800/10 text-slate-800 dark:text-slate-300 border-slate-800/20' }
    };
    return badges[status] || badges['scheduled'];
};

// Modal State
const showNewModal = ref(false);
const form = ref({
    name: '',
    phone: '',
    date: today,
    time: '',
    observations: ''
});
const isSubmitting = ref(false);

const openNewModal = () => {
    form.value = { name: '', phone: '', date: today, time: '', observations: '' };
    showNewModal.value = true;
};

const submitNewSchedule = () => {
    isSubmitting.value = true;
    router.post(route('sales.agendamentos.store'), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            showNewModal.value = false;
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const updateStatus = (schedule, newStatus) => {
    router.patch(route('sales.agendamentos.status.update', schedule.id), { status: newStatus }, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Agendamentos - Sala de Vendas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-black text-xl text-slate-800 dark:text-white uppercase tracking-widest">
                            Agendamentos
                        </h2>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Gestão de visitas à Sala de Vendas</p>
                    </div>
                </div>
                <button @click="openNewModal" class="h-10 bg-indigo-600 text-white px-5 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Agendamento
                </button>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto space-y-6">
            
            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Hoje</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ metrics.today }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-indigo-500/10 text-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Agendados Futuros</p>
                        <h3 class="text-2xl font-black text-slate-500">{{ metrics.scheduled }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-slate-500/10 text-slate-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="bg-white dark:bg-[#0f1219] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Confirmados Futuros</p>
                        <h3 class="text-2xl font-black text-blue-500">{{ metrics.confirmed }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                
                <!-- Filter Bar -->
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-3 items-center justify-between bg-slate-50/50 dark:bg-white/[0.02]">
                    <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto">
                        <!-- Search -->
                        <div class="relative w-full sm:w-56">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input v-model="search" @keyup.enter="fetchFilteredData" type="text" placeholder="Buscar por nome/telefone..." class="w-full pl-7 pr-2 h-8 bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-white focus:ring-indigo-500/20 placeholder-slate-400">
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="flex items-center h-8 bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg px-2 gap-1 shrink-0">
                            <input v-model="startDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <span class="text-slate-400 text-[9px] uppercase font-bold">até</span>
                            <input v-model="endDate" type="date" class="bg-transparent border-none text-[10px] text-slate-900 dark:text-white p-0 focus:ring-0 w-[95px] h-full">
                            <button v-if="startDate || endDate" @click="clearDates" class="ml-1 text-slate-400 hover:text-red-500 flex items-center" title="Limpar Datas">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Search Button -->
                        <button @click="fetchFilteredData" class="h-8 shrink-0 bg-indigo-600 text-white px-4 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-indigo-700 transition-colors flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Buscar
                        </button>
                    </div>
                    
                    <!-- Status Filters -->
                    <div class="flex flex-wrap items-center gap-1.5 w-full xl:w-auto">
                        <button @click="statusFilter = ''; fetchFilteredData()" :class="statusFilter === '' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Todos
                        </button>
                        <button @click="statusFilter = 'scheduled'; fetchFilteredData()" :class="statusFilter === 'scheduled' ? 'bg-slate-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Agendados
                        </button>
                        <button @click="statusFilter = 'confirmed'; fetchFilteredData()" :class="statusFilter === 'confirmed' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Confirmados
                        </button>
                        <button @click="statusFilter = 'show'; fetchFilteredData()" :class="statusFilter === 'show' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            Compareceu (Show)
                        </button>
                        <button @click="statusFilter = 'no_show'; fetchFilteredData()" :class="statusFilter === 'no_show' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" class="h-7 px-3 rounded-md text-[9px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap">
                            No-Show
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cliente</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Data / Hora</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Observações</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-3 py-2 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/50">
                            <tr v-for="schedule in schedules" :key="schedule.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold text-xs">
                                            {{ schedule.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ schedule.name }}</span>
                                            <span class="text-[9px] font-bold text-slate-400">{{ schedule.phone || 'Sem telefone' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">{{ formatDate(schedule.date) }}</span>
                                        <span class="text-[9px] font-bold text-indigo-500">{{ formatTime(schedule.time) || '--:--' }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 max-w-[200px] truncate">
                                    <span class="text-[10px] text-slate-500" :title="schedule.observations">{{ schedule.observations || '-' }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span :class="getStatusBadge(schedule.status).color" class="inline-flex px-2 py-0.5 rounded-md border text-[9px] font-bold uppercase tracking-widest">
                                        {{ getStatusBadge(schedule.status).label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1" v-if="schedule.status === 'scheduled'">
                                        <button @click="updateStatus(schedule, 'confirmed')" class="px-2 py-1 rounded border border-blue-500/30 text-blue-500 hover:bg-blue-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-colors" title="Confirmar">
                                            Confirmar
                                        </button>
                                        <button @click="updateStatus(schedule, 'cancelled')" class="px-2 py-1 rounded border border-red-500/30 text-red-500 hover:bg-red-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-colors" title="Cancelar">
                                            Cancelar
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-end gap-1" v-else-if="schedule.status === 'confirmed'">
                                        <button @click="updateStatus(schedule, 'show')" class="px-2 py-1 rounded border border-green-500/30 text-green-500 hover:bg-green-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-colors" title="Marcar Show">
                                            Show
                                        </button>
                                        <button @click="updateStatus(schedule, 'no_show')" class="px-2 py-1 rounded border border-red-500/30 text-red-500 hover:bg-red-500 hover:text-white text-[9px] font-bold uppercase tracking-widest transition-colors" title="Marcar No-Show">
                                            No-Show
                                        </button>
                                    </div>
                                    <div v-else>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Finalizado</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="schedules.length === 0">
                                <td colspan="5" class="px-3 py-8 text-center text-slate-500 text-xs">
                                    Nenhum agendamento encontrado com os filtros atuais.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Novo Agendamento -->
        <Modal :show="showNewModal" @close="showNewModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Novo Agendamento</h3>
                        <p class="text-xs text-slate-500">Agende uma visita à Sala de Vendas</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nome do Cliente *</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-indigo-500/20" placeholder="Ex: João da Silva" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Telefone (WhatsApp)</label>
                        <input v-model="form.phone" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-indigo-500/20" placeholder="(00) 00000-0000">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data *</label>
                            <input v-model="form.date" type="date" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-indigo-500/20" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Horário</label>
                            <input v-model="form.time" type="time" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm px-3 py-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Observações (Opcional)</label>
                        <textarea v-model="form.observations" rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm p-3 focus:ring-indigo-500/20" placeholder="Ex: Casal com 2 filhos..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showNewModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button @click="submitNewSchedule" :disabled="isSubmitting || !form.name || !form.date" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors flex items-center gap-2 disabled:opacity-50">
                        <span v-if="isSubmitting">Salvando...</span>
                        <span v-else>Salvar Agendamento</span>
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
