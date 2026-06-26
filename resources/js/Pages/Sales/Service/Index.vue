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
    
    // Atualiza o registro selecionado (se houver) para refletir os dados mais recentes do servidor,
    // como por exemplo a criação de uma nova proposta.
    if (selectedService.value) {
        const updatedService = newServices.find(s => s.id === selectedService.value.id);
        if (updatedService) {
            selectedService.value = updatedService;
        }
    }
}, { deep: true });

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
};

const selectedLocation = ref('Todos');
const today = new Date();
const selectedDateRange = ref({
    start: today,
    end: today,
    preset: 'Hoje'
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

const formatShortName = (fullName) => {
    if (!fullName) return '';
    const parts = fullName.trim().split(/\s+/);
    if (parts.length > 1) {
        return `${parts[0]} ${parts[1]}`.toUpperCase();
    }
    return parts[0].toUpperCase();
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

const getQualStyles = (colorClass) => {
    if (!colorClass) return 'border-slate-200 text-slate-500 bg-slate-50';
    const colorMap = {
        'bg-emerald-500': 'text-emerald-700 bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
        'bg-red-500': 'text-red-700 bg-red-50 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
        'bg-blue-500': 'text-blue-700 bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
        'bg-amber-500': 'text-amber-700 bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
        'bg-pink-500': 'text-pink-700 bg-pink-50 border-pink-200 dark:bg-pink-500/10 dark:text-pink-400 dark:border-pink-500/20',
        'bg-purple-500': 'text-purple-700 bg-purple-50 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
        'bg-indigo-500': 'text-indigo-700 bg-indigo-50 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20',
        'bg-cyan-500': 'text-cyan-700 bg-cyan-50 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20',
        'bg-orange-500': 'text-orange-700 bg-orange-50 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
        'bg-teal-500': 'text-teal-700 bg-teal-50 border-teal-200 dark:bg-teal-500/10 dark:text-teal-400 dark:border-teal-500/20',
        'bg-slate-500': 'text-slate-700 bg-slate-50 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20',
        'bg-gray-500': 'text-gray-700 bg-gray-50 border-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/20',
    };
    return colorMap[colorClass] || 'border-slate-200 text-slate-500 bg-slate-50 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
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

const getAllowedStatuses = (currentStatus) => {
    if (!currentStatus) return Object.values(SERVICE_STATUS).map(s => s.value);
    
    const val = currentStatus.toLowerCase();
    const allowed = [];

    // FILA -> MESA, FINALIZADO
    if (val === 'queue' || val === 'fila') {
        allowed.push(SERVICE_STATUS.MESA, SERVICE_STATUS.FINALIZADO);
    }
    // MESA -> FINALIZADO (PROPOSTA entra automaticamente via outro fluxo)
    else if (val === 'table' || val === 'mesa') {
        allowed.push(SERVICE_STATUS.FINALIZADO);
    }
    // PROPOSTA -> PENDENTE, REPROVADO (APROVADO entra automaticamente)
    else if (val === 'proposal' || val === 'proposta') {
        allowed.push(SERVICE_STATUS.PENDENTE, SERVICE_STATUS.REPROVADO);
    }
    // PENDENTE -> Nenhum destino manual (APROVADO entra automaticamente)
    else if (val === 'pending' || val === 'pendente') {
        // Array fica vazio, bloqueando alterações manuais pelo modal.
        // A mudança só ocorre ao aprovar/reprovar a proposta em sua respectiva tela.
    }
    // APROVADO -> PENDENTE, CANCELADO
    else if (val === 'approved' || val === 'aprovado' || val === 'money' || val === 'venda') {
        allowed.push(SERVICE_STATUS.PENDENTE, SERVICE_STATUS.CANCELADO);
    }
    // Estados Finais (REPROVADO, CANCELADO, FINALIZADO) - não permite sair (array fica vazio)

    return allowed.map(s => s.value);
};

const openStatusPicker = (index) => {
    if (!can('atendimentos.gerenciar')) return;
    
    const currentStatus = serviceData.value[index].status;
    const allowed = getAllowedStatuses(currentStatus);
    
    // Se a lista de permitidos estiver vazia, significa que é um status final, bloqueia a abertura
    if (allowed.length === 0) {
        showNotification('Atenção: Este é um status final e não pode ser alterado.');
        return;
    }

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
const hoveredQualId = ref(null);
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

        <div class="min-h-screen font-sans bg-slate-50 dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white/50 dark:bg-slate-900/40 backdrop-blur-2xl border border-white/60 dark:border-slate-800/50 rounded-[20px] mb-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Atendimentos</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Sala de Vendas
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="w-full sm:w-auto">
                            <button 
                                @click="selectedService = null; isNewServiceModalOpen = true"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Atendimento</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-6 py-4 bg-slate-50/40 dark:bg-slate-800/20 rounded-b-[20px] flex flex-col md:flex-row items-center justify-end gap-3">
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

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12">
                    
                    <!-- Table Header -->
                    <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                        <div v-if="columnSettings.id" class="w-10 text-center ledger-th">#</div>
                        <div v-if="columnSettings.date" class="w-24 text-center ledger-th">Data</div>
                        <div v-if="columnSettings.time" class="w-20 text-center ledger-th">Horário</div>
                        <div v-if="columnSettings.clients" class="flex-1 min-w-[150px] text-left ledger-th">Clientes</div>
                        <div class="flex gap-4">
                            <div v-if="columnSettings.mkt" class="w-14 text-center ledger-th hidden md:block">Mkt</div>
                            <div v-if="columnSettings.opc" class="w-14 text-center ledger-th hidden lg:block">Promotor</div>
                            <div v-if="columnSettings.liner" class="w-14 text-center ledger-th hidden lg:block">Liner</div>
                            <div v-if="columnSettings.closer" class="w-14 text-center ledger-th hidden lg:block">Closer</div>
                        </div>
                        <div v-if="columnSettings.qualification" class="w-24 text-center ledger-th">Qualif.</div>
                        <div v-if="columnSettings.status" class="w-36 text-center ledger-th">Status</div>
                        <div v-if="columnSettings.actions" class="w-14 text-center ledger-th">Ações</div>
                    </div>

                    <!-- Table Body -->
                    <div class="w-full">
                        <template v-if="filteredServiceData.length > 0">
                            <div 
                                v-for="(item, index) in filteredServiceData" 
                                :key="item.id"
                                @click="openService(item)"
                                class="ledger-row flex items-center gap-3 px-6 py-3 cursor-pointer group relative"
                            >
                                <!-- Accent bar on hover -->
                                <div class="ledger-row-accent"></div>

                                <!-- Basic Info -->
                                <div v-if="columnSettings.id" class="w-10 text-center ledger-cell-muted font-mono text-xs">{{ index + 1 }}</div>
                                <div v-if="columnSettings.date" class="w-24 text-center font-mono text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ item.date }}</div>
                                <div v-if="columnSettings.time" class="w-20 text-center">
                                    <span class="ledger-time-tag">{{ item.time }}</span>
                                </div>
                                <div v-if="columnSettings.clients" class="flex-1 min-w-[150px] text-left truncate" :title="item.client?.nome + (item.nome_conjuge ? ' & ' + item.nome_conjuge : '')">
                                    <span class="ledger-client-name group-hover:text-indigo-400 dark:group-hover:text-indigo-300 transition-colors">{{ formatShortName(item.client?.nome || item.clients) }}</span><template v-if="item.nome_conjuge"><span class="ledger-ampersand">&amp;</span><span class="ledger-client-name group-hover:text-indigo-400 dark:group-hover:text-indigo-300 transition-colors">{{ formatShortName(item.nome_conjuge) }}</span></template>
                                </div>
                                
                                <!-- Staff Avatars -->
                                <div class="flex gap-4">
                                    <div v-if="columnSettings.mkt" class="w-14 hidden md:flex justify-center relative">
                                        <div 
                                            @mouseenter="hoveredAvatar = { id: item.id, type: 'mkt' }"
                                            @mouseleave="hoveredAvatar = { id: null, type: null }"
                                            @click.stop="openPicker(index, 'avatar')"
                                            class="w-9 h-9 rounded-full ring-2 ring-indigo-100 dark:ring-indigo-900/50 bg-slate-100 dark:bg-slate-800 flex items-center justify-center p-0.5 group-hover:scale-110 transition-transform duration-300 relative"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer hover:ring-indigo-300' : 'cursor-default'"
                                        >
                                            <img v-if="item.mkt_user && item.mkt_user.profile_photo_path && !item.mkt_user.has_error" :src="item.mkt_user.profile_photo_url" @error="item.mkt_user.has_error = true" class="w-full h-full object-cover rounded-full" alt="Seller">
                                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 rounded-full flex items-end justify-center overflow-hidden">
                                                <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                            
                                            <!-- Tooltip -->
                                            <Transition name="tooltip-fade">
                                                <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'mkt' && item.mkt_id" class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white rounded-lg shadow-xl min-w-max">
                                                    <p class="text-[9px] font-bold text-indigo-300 uppercase tracking-wider mb-0.5">Marketing / Seller</p>
                                                    <p class="text-xs font-semibold">{{ item.mkt_user?.name || getAvatarName(item.mkt_id) }}</p>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>

                                    <div v-if="columnSettings.opc" class="w-14 hidden lg:flex justify-center relative">
                                        <div 
                                            @mouseenter="hoveredAvatar = { id: item.id, type: 'opc' }"
                                            @mouseleave="hoveredAvatar = { id: null, type: null }"
                                            v-if="item.opc_id" 
                                            @click.stop="openPicker(index, 'opcAvatar')"
                                            class="w-9 h-9 rounded-full ring-2 ring-cyan-100 dark:ring-cyan-900/50 bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center p-0.5 group-hover:scale-110 transition-transform duration-300 relative"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer hover:ring-cyan-300' : 'cursor-default'"
                                        >
                                            <img v-if="item.opc_user && item.opc_user.profile_photo_path && !item.opc_user.has_error" :src="item.opc_user.profile_photo_url" @error="item.opc_user.has_error = true" class="w-full h-full object-cover rounded-full" alt="OPC">
                                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 rounded-full flex items-end justify-center overflow-hidden">
                                                <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                            
                                            <!-- Tooltip -->
                                            <Transition name="tooltip-fade">
                                                <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'opc' && item.opc_id" class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white rounded-lg shadow-xl min-w-max">
                                                    <p class="text-[9px] font-bold text-cyan-300 uppercase tracking-wider mb-0.5">OPC / Atendente</p>
                                                    <p class="text-xs font-semibold">{{ item.opc_user?.name || getAvatarName(item.opc_id) }}</p>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                                </div>
                                            </Transition>
                                        </div>
                                        <div 
                                            v-else 
                                            @click.stop="openPicker(index, 'opcAvatar')"
                                            class="w-9 h-9 flex items-center justify-center group/add"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                        >
                                            <div class="w-4 h-[2px] bg-slate-200 dark:bg-slate-700 group-hover/add:w-6 group-hover/add:bg-cyan-400 transition-all rounded-full"></div>
                                        </div>
                                    </div>

                                    <div v-if="columnSettings.liner" class="w-14 hidden lg:flex justify-center relative">
                                        <div 
                                            @mouseenter="hoveredAvatar = { id: item.id, type: 'liner' }"
                                            @mouseleave="hoveredAvatar = { id: null, type: null }"
                                            @click.stop="openPicker(index, 'linerAvatar')"
                                            class="w-9 h-9 rounded-full ring-2 ring-emerald-100 dark:ring-emerald-900/50 bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center p-0.5 group-hover:scale-110 transition-transform duration-300 relative"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer hover:ring-emerald-300' : 'cursor-default'"
                                        >
                                            <img v-if="item.liner_user && item.liner_user.profile_photo_path && !item.liner_user.has_error" :src="item.liner_user.profile_photo_url" @error="item.liner_user.has_error = true" class="w-full h-full object-cover rounded-full" alt="Liner">
                                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 rounded-full flex items-end justify-center overflow-hidden">
                                                <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                            
                                            <!-- Tooltip -->
                                            <Transition name="tooltip-fade">
                                                <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'liner' && item.liner_id" class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white rounded-lg shadow-xl min-w-max">
                                                    <p class="text-[9px] font-bold text-emerald-300 uppercase tracking-wider mb-0.5">Liner / Consultor</p>
                                                    <p class="text-xs font-semibold">{{ item.liner_user?.name || getAvatarName(item.liner_id) }}</p>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>

                                    <div v-if="columnSettings.closer" class="w-14 hidden lg:flex justify-center relative">
                                        <div 
                                            v-if="item.closer" 
                                            @mouseenter="hoveredAvatar = { id: item.id, type: 'closer' }"
                                            @mouseleave="hoveredAvatar = { id: null, type: null }"
                                            @click.stop="openPicker(index, 'closerAvatar')"
                                            class="w-9 h-9 rounded-full ring-2 ring-pink-100 dark:ring-pink-900/50 bg-pink-50 dark:bg-pink-900/20 flex items-center justify-center p-0.5 group-hover:scale-110 transition-transform duration-300 relative"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer hover:ring-pink-300' : 'cursor-default'"
                                        >
                                            <img v-if="item.closer_user && item.closer_user.profile_photo_path && !item.closer_user.has_error" :src="item.closer_user.profile_photo_url" @error="item.closer_user.has_error = true" class="w-full h-full object-cover rounded-full" alt="Closer">
                                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 rounded-full flex items-end justify-center overflow-hidden">
                                                <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 translate-y-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            </div>
                                            
                                            <!-- Tooltip -->
                                            <Transition name="tooltip-fade">
                                                <div v-if="hoveredAvatar.id === item.id && hoveredAvatar.type === 'closer' && item.closer_id" class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white rounded-lg shadow-xl min-w-max">
                                                    <p class="text-[9px] font-bold text-pink-300 uppercase tracking-wider mb-0.5">Closer / Fechador</p>
                                                    <p class="text-xs font-semibold">{{ item.closer_user?.name || getAvatarName(item.closer_id) }}</p>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                                </div>
                                            </Transition>
                                        </div>
                                        <div 
                                            v-else 
                                            @click.stop="openPicker(index, 'closerAvatar')"
                                            class="w-9 h-9 flex items-center justify-center group/add"
                                            :class="can('atendimentos.gerenciar') ? 'cursor-pointer' : 'cursor-default'"
                                        >
                                            <div class="w-4 h-[2px] bg-slate-200 dark:bg-slate-700 group-hover/add:w-6 group-hover/add:bg-pink-400 transition-all rounded-full"></div>
                                        </div>
                                    </div>
                                </div>

                                 <!-- Qualification -->
                                 <div v-if="columnSettings.qualification" class="w-24 flex justify-center relative">
                                     <div 
                                         @mouseenter="hoveredQualId = item.id"
                                         @mouseleave="hoveredQualId = null"
                                         @click.stop="openQualPicker(index)"
                                         class="min-w-[56px] h-9 px-3 rounded-[10px] border flex items-center justify-center text-xs font-black uppercase tracking-wider transition-all shadow-sm"
                                         :class="[
                                             getQualificationMetadata(item.qualification) 
                                                ? getQualStyles(getQualificationMetadata(item.qualification).color)
                                                : 'border-slate-200 text-slate-500 bg-slate-50 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
                                             can('atendimentos.gerenciar') && (item.status === 'MESA' || item.status === 'table') ? 'cursor-pointer hover:scale-105 hover:shadow-md' : 'cursor-not-allowed opacity-70'
                                         ]"
                                     >
                                         {{ item.qualification }}
                                     </div>

                                     <!-- Tooltip Qualificação -->
                                     <Transition name="tooltip-fade">
                                         <div v-if="hoveredQualId === item.id && getQualificationMetadata(item.qualification)" class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-[200] pointer-events-none px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white rounded-lg shadow-xl min-w-max flex flex-col items-center">
                                             <div class="flex items-center gap-1.5 mb-0.5">
                                                 <div class="w-2 h-2 rounded-full shadow-[0_0_5px_rgba(255,255,255,0.3)]" :class="getQualificationMetadata(item.qualification).color"></div>
                                                 <p class="text-[9px] font-bold text-slate-300 uppercase tracking-wider leading-none">Qualificação</p>
                                             </div>
                                             <p class="text-xs font-semibold">{{ getQualificationMetadata(item.qualification).name }}</p>
                                             <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                         </div>
                                     </Transition>
                                 </div>


                                 <!-- Status -->
                                 <div v-if="columnSettings.status" class="w-36 flex justify-center relative">
                                    <div
                                        class="inline-flex items-center gap-1.5 pl-2 pr-3 h-7 rounded-full cursor-default select-none border text-[10px] font-black uppercase tracking-[0.12em] whitespace-nowrap transition-none"
                                        :class="{
                                            'bg-orange-50 dark:bg-orange-500/10 border-orange-200 dark:border-orange-500/20 text-orange-600 dark:text-orange-400': item.status === 'queue' || item.status === 'fila',
                                            'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20 text-cyan-600 dark:text-cyan-400': item.status === 'table' || item.status === 'mesa',
                                            'bg-purple-50 dark:bg-purple-500/10 border-purple-200 dark:border-purple-500/20 text-purple-600 dark:text-purple-400': item.status === 'proposal' || item.status === 'proposta',
                                            'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-600 dark:text-amber-400': item.status === 'pending' || item.status === 'pendente',
                                            'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400': item.status === 'approved' || item.status === 'aprovado' || item.status === 'money' || item.status === 'venda',
                                            'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400': item.status === 'rejected' || item.status === 'reprovado',
                                            'bg-blue-50 dark:bg-blue-600/10 border-blue-200 dark:border-blue-600/20 text-blue-600 dark:text-blue-400': item.status === 'completed' || item.status === 'finalizado',
                                            'bg-slate-50 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20 text-slate-500 dark:text-slate-400': item.status === 'cancelled' || item.status === 'cancelado',
                                        }"
                                    >
                                        <!-- Ícone por status -->
                                        <span class="w-4 h-4 flex items-center justify-center shrink-0">
                                            <!-- FILA -->
                                            <svg v-if="item.status === 'queue' || item.status === 'fila'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" /></svg>
                                            <!-- MESA -->
                                            <svg v-else-if="item.status === 'table' || item.status === 'mesa'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <!-- PROPOSTA -->
                                            <svg v-else-if="item.status === 'proposal' || item.status === 'proposta'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            <!-- PENDENTE -->
                                            <svg v-else-if="item.status === 'pending' || item.status === 'pendente'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <!-- APROVADO -->
                                            <svg v-else-if="item.status === 'approved' || item.status === 'aprovado' || item.status === 'money' || item.status === 'venda'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <!-- REPROVADO -->
                                            <svg v-else-if="item.status === 'rejected' || item.status === 'reprovado'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <!-- FINALIZADO -->
                                            <svg v-else-if="item.status === 'completed' || item.status === 'finalizado'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                            <!-- CANCELADO -->
                                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </span>
                                        {{ getStatusMetadata(item.status).label }}
                                    </div>
                                 </div>


                                 <!-- Ações -->
                                 <div v-if="columnSettings.actions" class="w-14 flex justify-center">
                                    <button 
                                        @click.stop="openActions(item)"
                                        class="w-8 h-8 rounded-[8px] border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400 flex items-center justify-center hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-white transition-all shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                 </div>
                            </div>
                        </template>

                        <div v-else class="ledger-empty">
                            <div class="ledger-empty-ring">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h3 class="ledger-empty-title">Nenhum atendimento</h3>
                            <p class="ledger-empty-sub">Não há registros para os filtros selecionados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avatar Picker Modal (Transparent Overlay) -->
        <div 
            v-if="isPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm transition-all duration-500"
            @click.self="isPickerOpen = false"
        >
            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 rounded-2xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-100">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Selecionar Avatar</h3>
                    <button @click="isPickerOpen = false" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-slate-500 dark:text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
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
                            :class="isAvatarSelected(avatar.id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20 cursor-not-allowed' : 'border-transparent hover:border-indigo-300 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700'"
                        >
                            <img v-if="avatar.path && !avatar.path.includes('ui-avatars.com') && !avatar.has_error"
                                :src="avatar.path" 
                                @error="avatar.has_error = true"
                                class="w-full h-full object-cover rounded-full transition-transform duration-500" 
                                :class="isAvatarSelected(avatar.id) ? 'scale-110 opacity-70 grayscale-[20%]' : 'group-hover:scale-110'" 
                                :alt="avatar.name"
                            >
                            <div v-else class="w-full h-full bg-slate-200 dark:bg-slate-700 rounded-full flex items-end justify-center overflow-hidden transition-transform duration-500" :class="isAvatarSelected(avatar.id) ? 'scale-110 opacity-70' : 'group-hover:scale-110'">
                                <svg class="w-[85%] h-[85%] text-slate-400 dark:text-slate-500 translate-y-[10%]" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <div v-if="isAvatarSelected(avatar.id)" class="absolute inset-0 flex items-center justify-center bg-white/40 dark:bg-black/40 rounded-full backdrop-blur-[1px]">
                                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div v-else class="absolute inset-0 bg-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-full"></div>
                        </button>
                        <span 
                            class="text-xs font-semibold tracking-wide text-center"
                            :class="isAvatarSelected(avatar.id) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200'"
                        >
                            {{ avatar.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Qualification Picker Modal (Transparent Overlay) -->
        <div 
            v-if="isQualPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm transition-all duration-500"
            @click.self="isQualPickerOpen = false"
        >
            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 rounded-2xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-100">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Qualificação</h3>
                    <button @click="isQualPickerOpen = false" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-slate-500 dark:text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <button 
                        v-for="qual in qualifications" 
                        :key="qual.id"
                        @click="selectQual(qual.code)"
                        class="h-24 rounded-[16px] border flex flex-col items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg active:scale-95 group/btn overflow-hidden relative"
                        :class="getQualStyles(qual.color)"
                    >
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity" :class="qual.color"></div>
                        <span class="text-2xl font-black uppercase mb-1">{{ qual.code }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-90 text-center px-2 leading-tight">{{ qual.name }}</span>
                    </button>
                    <!-- Opção padrão 'Q' caso não exista no banco -->
                    <button 
                        v-if="!qualifications.find(q => q.code === 'Q')"
                        @click="selectQual('Q')"
                        class="h-24 rounded-[16px] border flex flex-col items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg active:scale-95 border-slate-200 text-slate-500 bg-slate-50 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 group/btn relative overflow-hidden"
                    >
                        <div class="absolute inset-0 bg-slate-400 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                        <span class="text-2xl font-black uppercase mb-1">Q</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-90 text-center px-2 leading-tight">Qualificado</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Status Picker Modal -->
        <div 
            v-if="isStatusPickerOpen" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm transition-all duration-500"
            @click.self="isStatusPickerOpen = false"
        >
            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-700/50 rounded-2xl p-8 shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-100">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Alterar Status</h3>
                    <button @click="isStatusPickerOpen = false" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-slate-500 dark:text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        v-for="status in Object.values(SERVICE_STATUS)" 
                        :key="status.value"
                        v-show="activeStatusIndex !== null && getAllowedStatuses(serviceData[activeStatusIndex].status).includes(status.value)"
                        @click="selectStatus(status.value)"
                        class="h-16 rounded-[14px] flex items-center gap-3 px-4 transition-all duration-300 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-500 group/btn hover:shadow-md active:scale-95 bg-white dark:bg-slate-800"
                    >
                        <div class="w-8 h-8 rounded-full flex items-center justify-center transition-colors" :class="[status.bgColor, status.textColor]">
                            <svg v-if="status.icon === 'users'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <svg v-else-if="status.icon === 'table'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            <svg v-else-if="status.icon === 'door'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            <svg v-else-if="status.icon === 'money'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'x-circle'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'clock'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="status.icon === 'document'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <svg v-else-if="status.icon === 'check-double'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ status.label }}</span>
                    </button>
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

/* ─── LEDGER TABLE (GeneralCommission style) ─── */
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&display=swap');

.ledger-card {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}

/* Dark mode keeps the same dark look */
.dark .ledger-card {
    background: #0d1117;
    border-color: rgba(255, 255, 255, 0.05);
}

/* Light mode — elegant off-white variant */
html:not(.dark) .ledger-card {
    background: #ffffff;
    border-color: rgba(0, 0, 0, 0.07);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.ledger-header-row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(0, 0, 0, 0.25);
}

html:not(.dark) .ledger-header-row {
    border-bottom: 1px solid rgba(0, 0, 0, 0.07);
    background: rgba(0, 0, 0, 0.025);
}

.ledger-th {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #475569;
}

html:not(.dark) .ledger-th {
    color: #94a3b8;
}

/* Row */
.ledger-row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    transition: background 0.15s;
}

html:not(.dark) .ledger-row {
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.ledger-row:last-child {
    border-bottom: none;
}

.ledger-row:hover {
    background: rgba(99, 102, 241, 0.04);
}

html:not(.dark) .ledger-row:hover {
    background: rgba(99, 102, 241, 0.03);
}

/* Left accent bar */
.ledger-row-accent {
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 2px;
    border-radius: 2px;
    background: #6366f1;
    opacity: 0;
    transition: opacity 0.15s;
}

.ledger-row:hover .ledger-row-accent {
    opacity: 1;
}

/* Cells */
.ledger-cell-muted {
    font-family: 'JetBrains Mono', monospace;
    color: #334155;
}

html:not(.dark) .ledger-cell-muted {
    color: #94a3b8;
}

/* Time tag */
.ledger-time-tag {
    display: inline-block;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 20px;
    color: #818cf8;
    letter-spacing: 0.5px;
}

html:not(.dark) .ledger-time-tag {
    background: rgba(99, 102, 241, 0.06);
    border-color: rgba(99, 102, 241, 0.2);
    color: #6366f1;
}

/* Client name */
.ledger-client-name {
    font-size: 0.82rem;
    font-weight: 500;
    color: #e2e8f0;
    letter-spacing: 0.1px;
}

html:not(.dark) .ledger-client-name {
    color: #1e293b;
}

.ledger-ampersand {
    color: #f59e0b;
    font-weight: 700;
    margin: 0 6px;
}

/* Empty state */
.ledger-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 80px 20px;
    text-align: center;
}

.ledger-empty-ring {
    width: 72px;
    height: 72px;
    border: 2px dashed rgba(255, 255, 255, 0.07);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1f2937;
    animation: spin 12s linear infinite;
}

html:not(.dark) .ledger-empty-ring {
    border-color: rgba(0, 0, 0, 0.08);
    color: #cbd5e1;
}

.ledger-empty-title {
    font-size: 1rem;
    font-weight: 600;
    color: #f1f5f9;
    margin: 0;
}

html:not(.dark) .ledger-empty-title {
    color: #1e293b;
}

.ledger-empty-sub {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem;
    color: #475569;
    line-height: 1.8;
    max-width: 320px;
    margin: 0;
    letter-spacing: 0.5px;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
