<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';
import LocationPicker from '@/Components/Dashboard/LocationPicker.vue';
import ActionsPicker from '@/Components/Dashboard/ActionsPicker.vue';
import NewServiceModal from '@/Components/Sales/NewServiceModal.vue';
import ServiceActionsModal from '@/Components/Sales/ServiceActionsModal.vue';
import { getStatusMetadata, SERVICE_STATUS } from '@/Constants/ServiceStatus';

const props = defineProps({
    services: Array,
    availableAvatars: Array,
    qualifications: Array,
    complimentaryItems: Array,
    columnSettings: Object,
    filters: Object // Added for search persistence
});

const serviceData = ref(props.services);

watch(() => props.services, (newServices) => {
    serviceData.value = newServices;
}, { deep: true });

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
};

const selectedLocation = ref('Todos');
const selectedDateRange = ref({
    start: new Date(2026, 0, 1), 
    end: new Date(2026, 11, 31),
    preset: 'Todo Período'
});

const handleDateChange = (range) => {
    selectedDateRange.value = range;
};

// Local Search Logic
const localSearch = ref(props.filters?.search || '');
watch(localSearch, (value) => {
    router.get(route('sales.atendimentos'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const isNewServiceModalOpen = ref(false);
const isActionsModalOpen = ref(false);
const selectedService = ref(null);

const handleCreateService = (newService) => {
    serviceData.value.unshift(newService);
    isNewServiceModalOpen.value = false;
    showNotification('Novo atendimento gerado com sucesso!');
};

const openService = (item) => {
    selectedService.value = item;
    isNewServiceModalOpen.value = true;
};

const openActions = (item) => {
    selectedService.value = item;
    isActionsModalOpen.value = true;
};

const closeServiceModal = () => {
    isNewServiceModalOpen.value = false;
    selectedService.value = null;
};

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 3000);
};

const exportToCSV = () => {
    if (filteredServiceData.value.length === 0) {
        showNotification('Nenhum dado para exportar', 'error');
        return;
    }

    const headers = ['ID', 'Data', 'Horário', 'Clientes', 'Local', 'Qualificação', 'Status'];
    const rows = filteredServiceData.value.map(item => [
        item.id,
        item.date,
        item.time,
        item.clients,
        item.local,
        item.qualification,
        item.status
    ]);

    const csvContent = [
        headers.join(','),
        ...rows.map(row => row.join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `atendimentos_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Exportação iniciada...');
};

const handleAction = (type) => {
    if (type === 'csv_export' || type === 'excel_export' || type === 'pdf_export') {
        const ext = type.split('_')[0];
        showNotification(`Exportação ${ext.toUpperCase()} iniciada...`);
        // Mocking direct trigger for user
        if (type === 'csv_export') exportToCSV();
    } else {
        showNotification(`Gerando ${type.replace('_', ' ')}...`);
    }
};

// Helper to parse date string to Date object
const parseDate = (dateStr) => {
    if (!dateStr) return null;
    // Handle YYYY-MM-DD (Database default)
    if (dateStr.includes('-')) {
        const [year, month, day] = dateStr.split('-').map(Number);
        return new Date(year, month - 1, day);
    }
    // Handle DD/MM/YYYY
    if (dateStr.includes('/')) {
        const [day, month, year] = dateStr.split('/').map(Number);
        return new Date(year, month - 1, day);
    }
    return new Date(dateStr);
};

const filteredServiceData = computed(() => {
    if (!serviceData.value) return [];
    return serviceData.value.filter(item => {
        try {
            // Date Filter
            const itemDate = parseDate(item.date);
            let matchesDate = false;
            if (itemDate && selectedDateRange.value?.start && selectedDateRange.value?.end) {
                const start = new Date(selectedDateRange.value.start);
                start.setHours(0, 0, 0, 0);
                const end = new Date(selectedDateRange.value.end);
                end.setHours(23, 59, 59, 999);
                matchesDate = itemDate >= start && itemDate <= end;
            } else if (!selectedDateRange.value?.start || !selectedDateRange.value?.end) {
                matchesDate = true; // If no date selected, show all
            }
            
            // Location Filter
            const matchesLocation = selectedLocation.value === 'Todos' || item.local === selectedLocation.value;
            
            return matchesDate && matchesLocation;
        } catch (e) {
            return false;
        }
    });
});

// Avatar Picker State
const isPickerOpen = ref(false);
const activeRecordIndex = ref(null);
const activeField = ref(null);

const filteredAvatars = computed(() => {
    if (!activeField.value) return [];
    
    // Mapeamento exato solicitado: Cargo Real -> Coluna
    const fieldToRoles = {
        'avatar': ['mkt', 'promotor', 'marketing'], // MKT/Seller
        'opcAvatar': ['opc', 'promotor'],          // OPC
        'linerAvatar': ['liner', 'consultor'],     // Liner
        'closerAvatar': ['closer', 'supervisor']   // Closer
    };
    
    const allowedRoles = fieldToRoles[activeField.value] || [];
    
    // Filtra os usuários reais do banco de dados que possuem o cargo (ignorando maiúsculas/minúsculas)
    return props.availableAvatars.filter(a => {
        if (!a.roles || !Array.isArray(a.roles)) return false;
        // Verifica se o usuário tem pelo menos um dos cargos exigidos
        return a.roles.some(role => allowedRoles.includes(role));
    });
});

const openPicker = (index, field) => {
    if (!can('atendimentos.gerenciar')) return;
    activeRecordIndex.value = index;
    activeField.value = field;
    isPickerOpen.value = true;
};


const isAvatarSelected = (avatarPath) => {
    if (activeRecordIndex.value === null || !activeField.value) return false;
    return serviceData.value[activeRecordIndex.value][activeField.value] === avatarPath;
};

const saveQuickUpdate = (index, data) => {
    const service = serviceData.value[index];
    router.patch(route('sales.atendimentos.quick-update', service.id), data, {
        preserveScroll: true,
        onError: (err) => {
            console.error('Update failed:', err);
        }
    });
};

// Qualification Picker State
const isQualPickerOpen = ref(false);
const activeQualIndex = ref(null);

const getQualificationMetadata = (code) => {
    return props.qualifications?.find(q => q.code === code) || null;
};

const openQualPicker = (index) => {
    if (!can('atendimentos.gerenciar')) return;
    const item = serviceData.value[index];
    // Aceita tanto o valor novo 'MESA' quanto o legado 'table'
    if (item.status !== 'MESA' && item.status !== 'table') {
        showNotification('Qualificação bloqueada: Atendimento fora da MESA.', 'error');
        return;
    }
    activeQualIndex.value = index;
    isQualPickerOpen.value = true;
};

const selectAvatar = (avatarPath) => {
    if (activeRecordIndex.value !== null && activeField.value) {
        // Map camelCase from frontend to snake_case for backend
        const fieldMapping = {
            'opcAvatar': 'opc_id',
            'linerAvatar': 'liner_id',
            'closerAvatar': 'closer_id',
            'avatar': 'mkt_id'
        };

        const backendField = fieldMapping[activeField.value] || activeField.value;
        const updateData = { [backendField]: avatarPath };

        if (activeField.value === 'opcAvatar') updateData.opc = true;
        if (activeField.value === 'closerAvatar') updateData.closer = true;

        serviceData.value[activeRecordIndex.value][activeField.value] = avatarPath;
        saveQuickUpdate(activeRecordIndex.value, updateData);
    }
    isPickerOpen.value = false;
};

const selectQual = (value) => {
    if (activeQualIndex.value !== null) {
        serviceData.value[activeQualIndex.value].qualification = value;
        saveQuickUpdate(activeQualIndex.value, { qualification: value });
    }
    isQualPickerOpen.value = false;
};

// Status Picker State
const isStatusPickerOpen = ref(false);
const activeStatusIndex = ref(null);

const openStatusPicker = (index) => {
    if (!can('atendimentos.gerenciar')) return;
    activeStatusIndex.value = index;
    isStatusPickerOpen.value = true;
};

const selectStatus = (value) => {
    if (activeStatusIndex.value !== null) {
        serviceData.value[activeStatusIndex.value].status = value;
        saveQuickUpdate(activeStatusIndex.value, { status: value });
    }
    isStatusPickerOpen.value = false;
};

// Tooltip State
const hoveredStatusId = ref(null);
const hoveredAvatar = ref({ id: null, type: null }); // type: 'mkt', 'opc', 'liner', 'closer'

const getAvatarName = (userId) => {
    if (!userId) return 'Não Definido';
    const avatar = props.availableAvatars.find(a => a.id === userId);
    return avatar ? avatar.name : 'Desconhecido';
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
    <Head title="Sala de Vendas" />

    <AuthenticatedLayout>

        
        <!-- Action Notification -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div v-if="notification" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[300]">
                <div class="bg-indigo-950/90 dark:bg-[#1a1f2e] border border-indigo-500/30 backdrop-blur-xl px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">{{ notification }}</span>
                </div>
            </div>
        </Transition>

        <div class="min-h-screen font-sans">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                <!-- Premium Header & Toolbar - Highly Organized Density -->
                <div class="bg-white dark:bg-[#1a1f2e]/80 backdrop-blur-3xl border border-slate-200 dark:border-white/10 rounded-2xl mb-5 shadow-xl flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50 dark:bg-transparent">
                        <div class="flex items-center gap-3 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/10 to-blue-500/10 border border-indigo-500/20 dark:border-white/10 flex items-center justify-center shadow-inner">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter leading-none">Atendimentos</h2>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-1 flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-cyan-500 animate-pulse"></span>
                                    Sala de Vendas
                                </p>
                            </div>
                        </div>

                        <!-- Action Button - Primary Color -->
                        <div class="w-full sm:w-auto">
                            <button 
                                @click="selectedService = null; isNewServiceModalOpen = true"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-cyan-600 dark:hover:bg-cyan-700 px-4 py-2 rounded-md transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-medium text-white">Novo Atendimento</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-5 py-3 bg-white dark:bg-black/20 flex flex-col md:flex-row items-center justify-end gap-3">
                        
                        <!-- Component Filters Grid -->
                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <div class="w-full sm:w-[280px]">
                                <DateRangePicker v-model="selectedDateRange" class="w-full" />
                            </div>
                            <div class="w-full sm:w-48">
                                <LocationPicker v-model="selectedLocation" class="w-full" />
                            </div>
                            <div class="w-full sm:w-40">
                                <ActionsPicker @select="handleAction" class="w-full" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1a1f2e]/60 backdrop-blur-3xl border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-2xl flex flex-col overflow-x-auto">
                    <!-- Premium Table Header -->
                    <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/[0.03] w-full text-[9px] font-black font-sans text-center text-slate-500 dark:text-gray-400 uppercase tracking-[0.2em]">
                        <div v-if="columnSettings.id" class="w-10 sm:w-12">#</div>
                        <div v-if="columnSettings.date" class="w-24 sm:w-28">Data</div>
                        <div v-if="columnSettings.time" class="flex-1 min-w-[60px]">Horários</div>
                        <div v-if="columnSettings.clients" class="flex-[2] min-w-[120px] text-left">Clientes</div>
                        <div v-if="columnSettings.mkt" class="w-16 sm:w-20 hidden md:block">Mkt</div>
                        <div v-if="columnSettings.opc" class="w-16 sm:w-20 hidden lg:block">OPC</div>
                        <div v-if="columnSettings.liner" class="w-16 sm:w-20 hidden lg:block">Liner</div>
                        <div v-if="columnSettings.closer" class="w-16 sm:w-20 hidden lg:block">Closer</div>
                        <div v-if="columnSettings.qualification" class="w-16 sm:w-20">Qualif.</div>
                        <div v-if="columnSettings.status" class="w-16 sm:w-20">Status</div>
                        <div v-if="columnSettings.actions" class="w-12 sm:w-14">AÇÕES</div>
                    </div>

                    <!-- Table Body -->
                    <div class="w-full">
                        <template v-if="filteredServiceData.length > 0">
                            <div 
                                v-for="(item, index) in filteredServiceData" 
                                :key="item.id"
                                @click="openService(item)"
                                class="flex items-center gap-1.5 px-4 py-1.5 transition-all duration-300 border-b border-slate-50 dark:border-white/[0.03] hover:bg-slate-50 dark:hover:bg-white/[0.04] group cursor-pointer"
                                :class="{ 'bg-yellow-400/5': item.highlight }"
                            >
                                 <div v-if="columnSettings.id" class="w-10 sm:w-12 font-medium text-slate-400 dark:text-gray-500 text-center text-[10px]">{{ index + 1 }}</div>
                                 <div v-if="columnSettings.date" class="w-24 sm:w-28 font-medium text-slate-400 dark:text-gray-400 text-center text-[10px]">{{ item.date }}</div>
                                 <div v-if="columnSettings.time" class="flex-1 min-w-[60px] font-bold text-indigo-600 dark:text-cyan-400/80 text-center text-[10px]">{{ item.time }}</div>
                                 <div v-if="columnSettings.clients" class="flex-[2] min-w-[120px] text-[11px] font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-cyan-400 transition-colors text-left line-clamp-1 uppercase tracking-tight">{{ item.clients }}</div>
                                 <div v-if="columnSettings.mkt" class="w-16 sm:w-20 hidden md:flex justify-center relative">
                                    <div 
                                        @mouseenter="hoveredAvatar = { id: item.id, type: 'mkt' }"
                                        @mouseleave="hoveredAvatar = { id: null, type: null }"
                                        @click.stop="openPicker(index, 'avatar')"
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-slate-200 dark:border-white/20 bg-slate-100 dark:bg-white/10 flex items-center justify-center p-0.5 group-hover:scale-110 transition-all duration-500 shadow-lg overflow-hidden active:scale-95 relative"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <img v-if="item.mkt_user" :src="item.mkt_user.profile_photo_url" class="w-full h-full object-cover rounded-full" alt="Seller">
                                        <svg v-else class="w-5 h-5 text-indigo-500/20 dark:text-white/20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        
                                        <!-- Tooltip Equipe -->
                                        <Transition name="tooltip-fade">
                                            <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'mkt' && item.mkt_id" class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-indigo-950/90 dark:bg-[#1a1f2e]/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl min-w-max">
                                                <p class="text-[8px] font-black text-cyan-400 uppercase tracking-widest mb-0.5">Marketing / Seller</p>
                                                <p class="text-[10px] font-black text-white uppercase tracking-wider">{{ item.mkt_user?.name || getAvatarName(item.mkt_id) }}</p>
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-indigo-950/90 dark:border-t-[#1a1f2e]/95"></div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                                                                 <div v-if="columnSettings.opc" class="w-16 sm:w-20 hidden lg:flex justify-center relative">
                                    <div 
                                        @mouseenter="hoveredAvatar = { id: item.id, type: 'opc' }"
                                        @mouseleave="hoveredAvatar = { id: null, type: null }"
                                        v-if="item.opc" 
                                        @click.stop="openPicker(index, 'opcAvatar')"
                                        class="w-9 h-9 rounded-full border border-indigo-500/30 dark:border-cyan-500/30 bg-indigo-500/10 dark:bg-cyan-500/10 flex items-center justify-center p-0.5 group-hover:scale-110 transition-all duration-500 shadow-lg overflow-hidden active:scale-95 relative"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <img v-if="item.opc_user" :src="item.opc_user.profile_photo_url" class="w-full h-full object-cover rounded-full" alt="OPC">
                                        <svg v-else class="w-5 h-5 text-indigo-500/40 dark:text-cyan-500/40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        
                                        <!-- Tooltip OPC -->
                                        <Transition name="tooltip-fade">
                                            <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'opc' && item.opc_id" class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-indigo-950/90 dark:bg-[#1a1f2e]/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl min-w-max">
                                                <p class="text-[8px] font-black text-indigo-400 dark:text-cyan-400 uppercase tracking-widest mb-0.5">OPC / Atendente</p>
                                                <p class="text-[10px] font-black text-white uppercase tracking-wider">{{ item.opc_user?.name || getAvatarName(item.opc_id) }}</p>
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-indigo-950/90 dark:border-t-[#1a1f2e]/95"></div>
                                            </div>
                                        </Transition>
                                    </div>
                                    <div 
                                        v-else 
                                        @click.stop="openPicker(index, 'opcAvatar')"
                                        class="w-9 h-9 flex items-center justify-center group/add"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <div class="w-4 h-[1px] bg-slate-300 dark:bg-white/10 group-hover/add:w-6 group-hover/add:bg-indigo-500 dark:group-hover/add:bg-cyan-500 transition-all"></div>
                                    </div>
                                </div>
                                <!-- Liner -->
                                <div v-if="columnSettings.liner" class="w-16 sm:w-20 hidden lg:flex justify-center relative">
                                    <div 
                                        @mouseenter="hoveredAvatar = { id: item.id, type: 'liner' }"
                                        @mouseleave="hoveredAvatar = { id: null, type: null }"
                                        @click.stop="openPicker(index, 'linerAvatar')"
                                        class="w-9 h-9 rounded-full border border-purple-500/30 bg-purple-500/10 flex items-center justify-center p-0.5 group-hover:scale-110 transition-all duration-500 shadow-lg overflow-hidden active:scale-95 relative"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <img v-if="item.liner_user" :src="item.liner_user.profile_photo_url" class="w-full h-full object-cover rounded-full" alt="Liner">
                                        <svg v-else class="w-5 h-5 text-emerald-500/40 dark:text-emerald-500/40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        
                                        <!-- Tooltip Liner -->
                                        <Transition name="tooltip-fade">
                                            <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'liner' && item.liner_id" class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-indigo-950/90 dark:bg-[#1a1f2e]/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl min-w-max">
                                                <p class="text-[8px] font-black text-emerald-400 uppercase tracking-widest mb-0.5">Liner / Consultor</p>
                                                <p class="text-[10px] font-black text-white uppercase tracking-wider">{{ item.liner_user?.name || getAvatarName(item.liner_id) }}</p>
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-indigo-950/90 dark:border-t-[#1a1f2e]/95"></div>
                                            </div>
                                         </Transition>
                                    </div>
                                </div>
                                <!-- Closer -->
                                <div v-if="columnSettings.closer" class="w-16 sm:w-20 hidden lg:flex justify-center relative">
                                    <div v-if="item.closer === 'closer'" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-[6px] font-black text-gray-500 uppercase tracking-tighter text-center leading-none px-1">CLOSER</div>
                                    <div 
                                        v-else-if="item.closer" 
                                        @mouseenter="hoveredAvatar = { id: item.id, type: 'closer' }"
                                        @mouseleave="hoveredAvatar = { id: null, type: null }"
                                        @click.stop="openPicker(index, 'closerAvatar')"
                                        class="w-9 h-9 rounded-full border border-pink-500/30 bg-pink-500/10 flex items-center justify-center p-0.5 group-hover:scale-110 transition-all duration-500 shadow-lg overflow-hidden active:scale-95 relative"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <img v-if="item.closer_user" :src="item.closer_user.profile_photo_url" class="w-full h-full object-cover rounded-full" alt="Closer">
                                        <svg v-else class="w-5 h-5 text-pink-500/40 dark:text-pink-500/40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        
                                        <!-- Tooltip Closer -->
                                        <Transition name="tooltip-fade">
                                            <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'closer' && item.closer_id" class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-indigo-950/90 dark:bg-[#1a1f2e]/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl min-w-max">
                                                <p class="text-[8px] font-black text-pink-400 uppercase tracking-widest mb-0.5">Closer / Fechador</p>
                                                <p class="text-[10px] font-black text-white uppercase tracking-wider">{{ item.closer_user?.name || getAvatarName(item.closer_id) }}</p>
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-indigo-950/90 dark:border-t-[#1a1f2e]/95"></div>
                                            </div>
                                         </Transition>
                                    </div>
                                    <div 
                                        v-else 
                                        @click.stop="openPicker(index, 'closerAvatar')"
                                        class="w-9 h-9 flex items-center justify-center group/add"
                                        :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                    >
                                        <div class="w-4 h-[1px] bg-white/10 group-hover/add:w-6 group-hover/add:bg-pink-500 transition-all"></div>
                                    </div>
                                </div>

                                 <!-- Identification Columns -->
                                 <!-- Qualification -->
                                 <div v-if="columnSettings.qualification" class="w-16 sm:w-20 flex justify-center">
                                     <div 
                                         @click.stop="openQualPicker(index)"
                                         class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border flex flex-col items-center justify-center text-[9px] font-black shadow-lg active:scale-95 transition-transform overflow-hidden group/qual relative"
                                         :class="[
                                             getQualificationMetadata(item.qualification) 
                                                ? getQualificationMetadata(item.qualification).color + ' text-white border-white/20' 
                                                : 'border-white/10 bg-white/5 text-gray-600 hover:bg-white/10',
                                             can('atendimentos.gerenciar') && (item.status === 'MESA' || item.status === 'table') ? 'cursor-pointer' : 'cursor-not-allowed opacity-80'
                                         ]"
                                     >
                                         <span class="relative z-10">{{ item.qualification }}</span>
                                         <div v-if="getQualificationMetadata(item.qualification)" class="absolute inset-0 bg-white/20 opacity-0 group-hover/qual:opacity-100 transition-opacity"></div>
                                     </div>
                                 </div>
                                 <!-- Status -->
                                 <div v-if="columnSettings.status" class="w-16 sm:w-20 flex justify-center relative">
                                    <div 
                                        @mouseenter="hoveredStatusId = item.id"
                                        @mouseleave="hoveredStatusId = null"
                                        @click.stop="openStatusPicker(index)" 
                                        class="w-9 h-9 rounded-full shadow-lg flex items-center justify-center transition-all duration-500 group-hover:scale-110 border border-white/10 relative"
                                        :class="[
                                            getStatusMetadata(item.status).color,
                                            getStatusMetadata(item.status).color.replace('bg-', 'shadow-') + '/20',
                                            can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'
                                        ]"
                                    >
                                        <!-- Ícones Dinâmicos based on metadata -->
                                        <svg v-if="getStatusMetadata(item.status).icon === 'table'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'door'" class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'money'" class="w-5 h-5 text-white font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'x-circle'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'clock'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'document'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <svg v-else-if="getStatusMetadata(item.status).icon === 'check-double'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>

                                        <!-- Custom Tooltip -->
                                        <Transition
                                            enter-active-class="transition duration-200 ease-out"
                                            enter-from-class="opacity-0 translate-y-1 scale-95"
                                            enter-to-class="opacity-100 translate-y-0 scale-100"
                                            leave-active-class="transition duration-150 ease-in"
                                            leave-from-class="opacity-100 translate-y-0 scale-100"
                                            leave-to-class="opacity-0 translate-y-1 scale-95"
                                        >
                                            <div 
                                                v-if="hoveredStatusId === item.id" 
                                                class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-indigo-950/90 dark:bg-[#1a1f2e]/95 backdrop-blur-xl border border-white/10 rounded-lg shadow-2xl overflow-hidden min-w-max"
                                            >
                                                <!-- Status Color Trace -->
                                                <div class="absolute inset-0 opacity-10" :class="getStatusMetadata(item.status).color"></div>
                                                
                                                <div class="relative flex items-center gap-2">
                                                    <div class="w-1.5 h-1.5 rounded-full" :class="getStatusMetadata(item.status).color"></div>
                                                    <span class="text-[10px] font-black text-white uppercase tracking-[0.15em] whitespace-nowrap">
                                                        {{ getStatusMetadata(item.status).label }}
                                                    </span>
                                                </div>
                                                
                                                <!-- Triangle Pointer -->
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-indigo-950/90 dark:border-t-[#1a1f2e]/95"></div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                                 <!-- Ações -->
                                 <div v-if="columnSettings.actions" class="w-12 sm:w-14 flex justify-center">
                                    <button 
                                        @click.stop="openActions(item)"
                                        class="w-8 h-8 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white hover:border-indigo-600 dark:hover:bg-cyan-500 dark:hover:text-white dark:hover:border-cyan-500 transition-all shadow-lg active:scale-95"
                                        title="Menu de Ações"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                 </div>
                            </div>
                        </template>

                        <div v-else class="h-full flex flex-col items-center justify-center p-20 text-center">
                            <div class="w-20 h-20 bg-white/5 rounded-[2rem] flex items-center justify-center mb-6 border border-white/10">
                                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Nenhum atendimento</h3>
                            <p class="text-gray-500 text-sm max-w-xs">Não há registros de atendimentos para os filtros selecionados no momento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avatar Picker Modal (Transparent Overlay) -->
        <div 
            v-if="isPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-all duration-500"
            @click.self="isPickerOpen = false"
        >
            <div class="bg-[#1a1f2e]/80 border border-white/10 rounded-xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-110">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-widest">Selecionar Avatar</h3>
                    <button @click="isPickerOpen = false" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-3 gap-6">
                    <div 
                        v-for="avatar in filteredAvatars" 
                        :key="avatar.id"
                        class="flex flex-col items-center gap-2"
                    >
                        <button 
                            @click="selectAvatar(avatar.id)"
                            :disabled="isAvatarSelected(avatar.id)"
                            class="relative group aspect-square rounded-full overflow-hidden border-2 transition-all duration-300 p-1 w-full"
                            :class="isAvatarSelected(avatar.id) ? 'border-indigo-500 dark:border-cyan-500 bg-indigo-500/20 dark:bg-cyan-500/20 cursor-not-allowed' : 'border-transparent hover:border-indigo-500/50 dark:hover:border-cyan-500/50 bg-white/5 hover:bg-white/10'"
                        >
                            <img 
                                :src="avatar.path" 
                                class="w-full h-full object-cover rounded-full transition-transform duration-500" 
                                :class="isAvatarSelected(avatar.id) ? 'scale-110 opacity-60 grayscale-[30%]' : 'group-hover:scale-110'" 
                                :alt="avatar.name"
                            >
                            <div v-if="isAvatarSelected(avatar.id)" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full backdrop-blur-[1px]">
                                <svg class="w-8 h-8 text-indigo-400 dark:text-cyan-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.8)] dark:drop-shadow-[0_0_8px_rgba(6,182,212,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div v-else class="absolute inset-0 bg-indigo-500/20 dark:bg-cyan-500/20 opacity-0 group-hover:opacity-100 transition-opacity rounded-full"></div>
                        </button>
                        <span 
                            class="text-[10px] font-bold uppercase tracking-wider text-center"
                            :class="isAvatarSelected(avatar.id) ? 'text-cyan-400' : 'text-gray-400 group-hover:text-white'"
                        >
                            {{ avatar.name }}
                        </span>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-center">
                    <button @click="isPickerOpen = false" class="text-xs font-bold text-gray-500 uppercase tracking-[0.3em] hover:text-white transition-colors">Cancelar</button>
                </div>
            </div>
        </div>
        <!-- Qualification Picker Modal (Transparent Overlay) -->
        <div 
            v-if="isQualPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-all duration-500"
            @click.self="isQualPickerOpen = false"
        >
            <div class="bg-[#1a1f2e]/80 border border-white/10 rounded-xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-110">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-widest">Qualificação</h3>
                    <button @click="isQualPickerOpen = false" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-4 gap-4">
                    <button 
                        v-for="qual in qualifications" 
                        :key="qual.id"
                        @click="selectQual(qual.code)"
                        class="h-14 rounded-xl flex flex-col items-center justify-center transition-all duration-300 border border-white/20 group/btn shadow-lg hover:scale-105 active:scale-95"
                        :class="[qual.color, 'text-white']"
                    >
                        <span class="text-[12px] font-black uppercase">{{ qual.code }}</span>
                        <span class="text-[7px] font-bold uppercase opacity-70 tracking-tighter leading-none mt-1 group-hover/btn:opacity-100">{{ qual.name }}</span>
                    </button>
                    <!-- Opção padrão 'Q' caso não exista no banco -->
                    <button 
                        v-if="!qualifications.find(q => q.code === 'Q')"
                        @click="selectQual('Q')"
                        class="h-14 rounded-xl flex flex-col items-center justify-center bg-white/5 text-gray-500 border border-white/10 hover:bg-white/10 transition-all"
                    >
                        <span class="text-[12px] font-black uppercase">Q</span>
                        <span class="text-[7px] font-bold uppercase opacity-50 tracking-tighter leading-none mt-1">Qualificado</span>
                    </button>
                </div>
                
                <div class="mt-8 flex justify-center">
                    <button @click="isQualPickerOpen = false" class="text-xs font-bold text-gray-500 uppercase tracking-[0.3em] hover:text-white transition-colors">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Status Picker Modal -->
        <div 
            v-if="isStatusPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-all duration-500"
            @click.self="isStatusPickerOpen = false"
        >
            <div class="bg-[#1a1f2e]/80 border border-white/10 rounded-xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-110">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-widest">Alterar Status</h3>
                    <button @click="isStatusPickerOpen = false" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        v-for="status in Object.values(SERVICE_STATUS)" 
                        :key="status.value"
                        @click="selectStatus(status.value)"
                        class="h-14 rounded-xl flex items-center gap-3 px-4 transition-all duration-300 border border-white/10 group/btn shadow-lg hover:scale-105 active:scale-95"
                        :class="[status.color, 'text-white']"
                    >
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                            <svg v-if="status.icon === 'table'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            <svg v-else-if="status.icon === 'door'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            <svg v-else-if="status.icon === 'money'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'x-circle'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'clock'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'document'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <svg v-else-if="status.icon === 'check-double'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <span class="text-[11px] font-black uppercase tracking-widest">{{ status.label }}</span>
                    </button>
                </div>
                
                <div class="mt-8 flex justify-center">
                    <button @click="isStatusPickerOpen = false" class="text-xs font-bold text-gray-500 uppercase tracking-[0.3em] hover:text-white transition-colors">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- New Service Modal -->
        <NewServiceModal 
            :show="isNewServiceModalOpen"
            :initial-data="selectedService"
            :available-avatars="availableAvatars"
            :qualifications="qualifications"
            :complimentary-items="complimentaryItems"
            @close="closeServiceModal"
            @created="handleCreateService"
        />

        <ServiceActionsModal
            :show="isActionsModalOpen"
            :service="selectedService"
            @close="isActionsModalOpen = false"
        />

        <!-- Local Notification Toast -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification.show" class="fixed top-24 right-5 z-[300] w-full max-w-sm overflow-hidden rounded-2xl border shadow-2xl backdrop-blur-xl"
                :class="notification.type === 'success' ? 'bg-green-500/10 border-green-500/20' : 'bg-amber-500/10 border-amber-500/20'"
            >
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg v-if="notification.type === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="h-6 w-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="flex-1 pt-0.5">
                            <p class="text-xs font-black uppercase tracking-widest" :class="notification.type === 'success' ? 'text-green-400' : 'text-amber-400'">
                                {{ notification.type === 'success' ? 'Sucesso' : 'Atenção' }}
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white leading-relaxed">
                                {{ notification.message }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="absolute bottom-0 left-0 h-1 bg-current opacity-20 transition-all duration-[3000ms] ease-linear w-0"
                    :class="notification.type === 'success' ? 'text-green-500' : 'text-amber-500'"
                    :style="{ width: notification.show ? '100%' : '0%' }"
                ></div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-input {
    transition: all 0.3s ease;
}
.glass-input:focus-within {
    border-color: rgba(6, 182, 212, 0.4);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.1);
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(6, 182, 212, 0.2);
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.glow-text {
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
}
</style>
