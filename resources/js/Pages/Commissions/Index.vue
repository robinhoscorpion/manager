<script setup>
import { ref, watch, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import MonthYearPicker from '@/Components/Dashboard/MonthYearPicker.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import axios from 'axios';

const props = defineProps({
    commissions: Object,
    metrics: Object,
    filters: Object,
    activeRules: Array,
});

const showGenerateModal = ref(false);
const today = new Date();
const generatedRoles = ref([]);
const isCheckingStatus = ref(false);

const generateForm = useForm({
    month: today.getMonth() + 1,
    year: today.getFullYear(),
    roles: []
});

const generateMonthStr = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`);

watch(generateMonthStr, async (newVal) => {
    if (newVal) {
        const [year, month] = newVal.split('-');
        generateForm.year = parseInt(year);
        generateForm.month = parseInt(month);
        generateForm.roles = []; // Reset roles selection on month change
        
        isCheckingStatus.value = true;
        try {
            const response = await axios.get(route('commissions.generated-status', {
                year: generateForm.year,
                month: generateForm.month
            }));
            generatedRoles.value = response.data.generated_roles || [];
        } catch (error) {
            console.error("Erro ao buscar status de comissões:", error);
            generatedRoles.value = [];
        } finally {
            isCheckingStatus.value = false;
        }
    }
}, { immediate: true });

const allRolesGenerated = computed(() => {
    if (!props.activeRules || props.activeRules.length === 0) return false;
    return props.activeRules.every(rule => generatedRoles.value.includes(rule.name));
});

const deletePeriodForm = useForm({
    year: today.getFullYear(),
    month: today.getMonth() + 1,
});

const confirmDeletePeriod = () => {
    if (confirm('Atenção: Isso excluirá permanentemente TODAS as comissões geradas para os contratos deste período. Tem certeza?')) {
        deletePeriodForm.year = generateForm.year;
        deletePeriodForm.month = generateForm.month;
        
        deletePeriodForm.delete(route('commissions.destroy-period'), {
            preserveScroll: true,
            onSuccess: () => {
                // Refresh the generatedRoles
                generatedRoles.value = [];
            }
        });
    }
};

const isProcessingModalOpen = ref(false);
const processProgress = ref(0);
const processStepText = ref('');
const processSteps = [
    'Analisando contratos e propostas consolidadas do período...',
    'Cruzando pontuação de produtos e qualificações (OPC)...',
    'Calculando regras de percentual e valores por cargo...',
    'Gerando parcelas financeiras e vencimentos (M+1)...',
    'Consolidando registros de comissão dos funcionários...'
];
let processInterval = null;

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 4000);
};

const submitGenerate = () => {
    if (generateForm.roles.length === 0) return;
    
    isProcessingModalOpen.value = true;
    processProgress.value = 10;
    processStepText.value = processSteps[0];

    // Progression simulation interval for ultra-smooth professional user experience
    let currentStep = 0;
    processInterval = setInterval(() => {
        if (processProgress.value < 90) {
            processProgress.value += Math.floor(Math.random() * 8) + 6;
            const nextStep = Math.min(
                Math.floor((processProgress.value / 90) * processSteps.length),
                processSteps.length - 1
            );
            if (nextStep !== currentStep) {
                currentStep = nextStep;
                processStepText.value = processSteps[currentStep];
            }
        }
    }, 300);

    generateForm.post(route('commissions.generate'), {
        preserveScroll: true,
        onSuccess: () => {
            clearInterval(processInterval);
            processProgress.value = 100;
            processStepText.value = 'Cálculo concluído! Registros gravados com sucesso.';
            
            setTimeout(() => {
                isProcessingModalOpen.value = false;
                showGenerateModal.value = false;
                generateForm.reset('roles');
                showNotification('Comissões geradas com sucesso para os funcionários!');
            }, 900);
        },
        onError: () => {
            clearInterval(processInterval);
            isProcessingModalOpen.value = false;
            showNotification('Erro ao gerar comissões. Verifique os dados e tente novamente.', 'error');
        }
    });
};

const initialMonth = props.filters?.start_date 
    ? props.filters.start_date.substring(0, 7) 
    : `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;

const selectedMonth = ref(initialMonth);

watch(selectedMonth, (newVal) => {
    if (newVal) {
        const [year, month] = newVal.split('-');
        const start_date = `${year}-${month}-01`;
        const lastDay = new Date(year, month, 0).getDate();
        const end_date = `${year}-${month}-${lastDay}`;
        
        router.get(route('commissions.index'), {
            start_date,
            end_date
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '--';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
};

const formatShortName = (fullName) => {
    if (!fullName) return '';
    const parts = fullName.trim().split(/\s+/);
    if (parts.length > 1) {
        return `${parts[0]} ${parts[1]}`.toUpperCase();
    }
    return parts[0].toUpperCase();
};

const getInitials = (name) => {
    if (!name) return '';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const showAuditModal = ref(false);
const selectedInstallment = ref(null);

const auditForm = useForm({
    status: 'pending',
    paid_at: '',
    notes: '',
});

const openAuditModal = (installment) => {
    selectedInstallment.value = installment;
    auditForm.status = installment.status === 'paid' ? 'paid' : (installment.status || 'pending');
    auditForm.paid_at = installment.paid_at 
        ? String(installment.paid_at).substring(0, 10) 
        : new Date().toISOString().substring(0, 10);
    auditForm.notes = installment.notes || '';
    showAuditModal.value = true;
};

const submitAudit = () => {
    if (!selectedInstallment.value) return;
    
    auditForm.patch(route('commissions.installment.update-status', selectedInstallment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAuditModal.value = false;
            showNotification('Auditoria da comissão atualizada com sucesso!');
        },
        onError: () => {
            showNotification('Erro ao atualizar auditoria da comissão.', 'error');
        }
    });
};

const getStatusMetadata = (status) => {
    const statuses = {
        'paid': { label: 'Pago', color: 'bg-emerald-100 border-emerald-200 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400' },
        'approved': { label: 'Pago', color: 'bg-emerald-100 border-emerald-200 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400' },
        'pending': { label: 'Pendente', color: 'bg-amber-100 border-amber-200 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400' },
        'rejected': { label: 'Recusado', color: 'bg-rose-100 border-rose-200 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400' },
        'cancelled': { label: 'Cancelado', color: 'bg-red-100 border-red-200 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400' },
    };
    return statuses[status?.toLowerCase()] || { label: status || '--', color: 'bg-slate-100 border-slate-200 text-slate-700 dark:border-slate-500/20 dark:bg-slate-500/10 dark:text-slate-400' };
};

</script>

<template>
    <Head title="Grade de Comissões" />

    <AuthenticatedLayout>

        <!-- Action Notification Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div v-if="notification.show" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[300]">
                <div class="bg-slate-900/95 dark:bg-[#1a1f2e] border border-brand-green/40 backdrop-blur-xl px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-brand-green animate-pulse"></div>
                    <span class="text-xs font-bold text-white uppercase tracking-wider">{{ notification.message }}</span>
                </div>
            </div>
        </Transition>

        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Comissões</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Registros Individuais
                                </p>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto flex items-center gap-3">
                            <button 
                                @click="showGenerateModal = true"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 cursor-pointer"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="text-sm font-semibold text-white">Gerar Comissões</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Filters Toolbar -->
                    <div class="px-6 py-4 bg-slate-50/40 dark:bg-slate-800/20 rounded-b-[20px] flex flex-col md:flex-row items-center justify-end gap-3">
                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <div class="w-full sm:w-[280px]">
                                <MonthYearPicker v-model="selectedMonth" class="w-full" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Metrics -->
                <div v-if="metrics" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total VGV -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">VGV Total</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(metrics.total_vgv) }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center border border-blue-100 dark:border-blue-500/20 text-blue-600 dark:text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Commissions -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-brand-green/30 dark:border-brand-green/30 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-brand-green/10 rounded-full blur-2xl group-hover:bg-brand-green/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-brand-green/80 uppercase tracking-wider mb-1">Total Comissões</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-brand-green tracking-tight">{{ formatCurrency(metrics.total_commissions) }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-brand-green/10 flex items-center justify-center border border-brand-green/20 text-brand-green">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Contracts -->
                    <div class="bg-white dark:bg-slate-900 rounded-[20px] p-6 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Contratos Gerados</p>
                                <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ metrics.total_contracts }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center border border-purple-100 dark:border-purple-500/20 text-purple-600 dark:text-purple-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    <div class="w-full overflow-x-auto">
                        <div class="min-w-[1024px]">
                            <!-- Table Header -->
                            <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                                <div class="w-40 text-left ledger-th !text-slate-700 dark:!text-slate-300">Contrato / Produto</div>
                                <div class="w-28 text-center ledger-th !text-slate-700 dark:!text-slate-300">Data / Status</div>
                                <div class="flex-1 min-w-[150px] text-left ledger-th !text-slate-700 dark:!text-slate-300">Cliente</div>
                                <div class="w-56 text-left ledger-th !text-slate-700 dark:!text-slate-300">Beneficiário</div>
                                <div class="w-40 text-left ledger-th !text-slate-700 dark:!text-slate-300">Cargo (Regra)</div>
                                <div class="w-32 text-right ledger-th !text-slate-700 dark:!text-slate-300">Valores (Comissão)</div>
                            </div>

                            <!-- Table Body -->
                            <div class="w-full">
                                <template v-if="commissions.data.length > 0">
                                    <div 
                                        v-for="installment in commissions.data" 
                                        :key="installment.id"
                                        class="ledger-row flex items-center gap-3 px-6 py-4 cursor-default group relative hover:z-50"
                                    >
                                        <!-- Accent bar on hover -->
                                        <div class="ledger-row-accent"></div>

                                        <div class="w-40 text-left flex flex-col justify-center">
                                            <span class="text-xs font-bold text-brand-green">#{{ installment.commission?.proposal?.contract_number || '--' }}</span>
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate w-full" :title="installment.commission?.proposal?.product?.name || '--'">
                                                {{ installment.commission?.proposal?.product?.name || '--' }}
                                            </span>
                                        </div>
                                        
                                        <div class="w-28 text-center flex flex-col items-center justify-center gap-1">
                                            <span class="font-mono text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ formatDate(installment.due_date) }}</span>
                                            <button 
                                                @click.stop="openAuditModal(installment)"
                                                :class="['text-[9px] px-2 py-0.5 rounded-md font-semibold border uppercase tracking-wider transition-all hover:scale-105 cursor-pointer flex items-center gap-1 shadow-sm', getStatusMetadata(installment.status).color]"
                                                title="Clique para auditar / alterar status da comissão"
                                            >
                                                <span>{{ getStatusMetadata(installment.status).label }}</span>
                                                <svg class="w-2.5 h-2.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                        </div>
                                        
                                        <div class="flex-1 min-w-[150px] text-left truncate flex flex-col justify-center">
                                            <span class="ledger-client-name group-hover:text-brand-green transition-colors">{{ formatShortName(installment.commission?.proposal?.client?.nome) }}</span>
                                        </div>
                                        
                                        <!-- Beneficiário -->
                                        <div class="w-56 flex items-center gap-3 group/avatar">
                                            <template v-if="installment.commission?.user">
                                                <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-400 shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm group-hover/avatar:border-brand-green group-hover/avatar:text-brand-green transition-colors">
                                                    {{ getInitials(installment.commission?.user?.name) }}
                                                </div>
                                                <div class="flex flex-col justify-center truncate">
                                                    <span class="text-xs font-semibold text-slate-900 dark:text-white truncate group-hover/avatar:text-brand-green transition-colors">{{ installment.commission?.user?.name }}</span>
                                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ installment.commission?.role }}</span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-slate-500 italic">--</span>
                                            </template>
                                        </div>

                                        <div class="w-40 text-left flex flex-col justify-center">
                                            <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ installment.commission?.role }}</span>
                                            <span class="text-[10px] text-slate-500 uppercase">Parc: {{ installment.month_offset }} ({{ installment.origin_type }})</span>
                                        </div>
                                        
                                        <div class="w-32 text-right flex flex-col justify-center items-end">
                                            <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ formatCurrency(installment.amount) }}</span>
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <span class="text-[9px] text-slate-500" title="Base de Cálculo">B: {{ formatCurrency(installment.commission?.base_sale_value) }}</span>
                                                <span class="font-medium text-[9px] text-slate-400 px-1 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10">{{ installment.commission?.base_commission_percentage }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="ledger-empty">
                                    <div class="ledger-empty-ring">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="ledger-empty-title">Nenhuma comissão encontrada.</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="commissions.data.length > 0" class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-white/[0.02]">
                        <Pagination :links="commissions.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Gerar Comissões -->
        <Modal :show="showGenerateModal" @close="showGenerateModal = false" maxWidth="2xl">
            <div class="relative overflow-hidden">
                <!-- Decorative Background Element -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-brand-green/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="px-6 py-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 backdrop-blur-sm relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-brand-green/10 flex items-center justify-center text-brand-green border border-brand-green/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Gerar Comissões</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Calcule as comissões das vendas aprovadas</p>
                        </div>
                    </div>
                    <button @click="showGenerateModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 bg-white dark:bg-slate-900 relative z-10">
                    <div class="mb-6 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl p-4 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p class="text-sm text-amber-700 dark:text-amber-400 leading-relaxed font-medium">
                            Comissões geradas anteriormente para os cargos e mês selecionados serão <strong class="font-bold">sobrescritas</strong>. Certifique-se de que as vendas deste período já estão consolidadas.
                        </p>
                    </div>

                    <form @submit.prevent="submitGenerate">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <!-- Left Col: Month Selection -->
                            <div class="flex flex-col">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Mês de Referência
                                </label>
                                <MonthYearPicker v-model="generateMonthStr" class="!w-full" />
                                <div v-if="generateForm.errors.month || generateForm.errors.year" class="text-red-500 text-xs mt-2 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Selecione um mês válido.
                                </div>
                            </div>
                            
                            <!-- Right Col: Roles Selection -->
                            <div class="flex flex-col">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        Cargos Disponíveis
                                    </div>
                                    <span v-if="isCheckingStatus" class="text-xs text-slate-400 flex items-center gap-1">
                                        <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Verificando...
                                    </span>
                                </label>
                                <div class="bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-200 dark:border-slate-700 p-2 max-h-[160px] overflow-y-auto custom-scrollbar">
                                    <div class="flex flex-col gap-1.5" :class="{'opacity-50 pointer-events-none': isCheckingStatus}">
                                        <label v-for="rule in activeRules" :key="rule.id" 
                                            class="flex items-center justify-between p-2.5 rounded-lg border border-transparent"
                                            :class="[
                                                generatedRoles.includes(rule.name) ? 'bg-slate-100 dark:bg-slate-800/50 opacity-75 cursor-not-allowed border-slate-200 dark:border-slate-700' : 
                                                generateForm.roles.includes(rule.name) ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 shadow-sm cursor-pointer transition-colors' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80 cursor-pointer transition-colors'
                                            ]"
                                        >
                                            <div class="flex items-center gap-3">
                                                <Checkbox v-if="!generatedRoles.includes(rule.name)" :value="rule.name" v-model:checked="generateForm.roles" class="!rounded !border-slate-300 dark:!border-slate-600 text-brand-green focus:ring-brand-green" />
                                                <div v-else class="w-4 h-4 rounded bg-slate-300 dark:bg-slate-600 flex items-center justify-center shrink-0">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ rule.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span v-if="generatedRoles.includes(rule.name)" class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                                                    Já Gerado
                                                </span>
                                                <span class="text-xs py-1 px-2.5 rounded-full bg-brand-green/10 text-brand-green font-black tracking-wide">
                                                    {{ rule.percentage }}%
                                                </span>
                                            </div>
                                        </label>
                                        <div v-if="!activeRules || activeRules.length === 0" class="text-sm text-slate-500 p-4 text-center">
                                            Nenhuma regra ativa.
                                        </div>
                                    </div>
                                </div>
                                <div v-if="generateForm.errors.roles" class="text-red-500 text-xs mt-2">{{ generateForm.errors.roles }}</div>
                            </div>
                        </div>

                        <!-- Info/Delete Action Area -->
                        <div v-if="allRolesGenerated" class="mb-6 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Todas as comissões já foram geradas</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Para este mês e ano, todos os cargos ativos já receberam cálculo de comissão.</p>
                                </div>
                            </div>
                            <button type="button" @click="confirmDeletePeriod" :disabled="deletePeriodForm.processing" class="shrink-0 px-4 py-2 bg-white dark:bg-slate-900 border border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400 text-xs font-bold rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors flex items-center gap-2">
                                <span v-if="deletePeriodForm.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Excluindo...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    EXCLUIR COMISSÕES DO PERÍODO
                                </span>
                            </button>
                        </div>

                        <div v-else-if="generatedRoles.length > 0" class="mb-6 flex justify-end">
                            <button type="button" @click="confirmDeletePeriod" :disabled="deletePeriodForm.processing" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-semibold underline underline-offset-2 flex items-center gap-1 transition-colors">
                                <span v-if="deletePeriodForm.processing">Processando...</span>
                                <span v-else>Excluir e Recalcular comissões já geradas no período</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-xs text-slate-400 font-medium">Os cálculos são baseados no valor total dos contratos.</span>
                            <div class="flex gap-3">
                                <SecondaryButton type="button" @click="showGenerateModal = false" class="!px-6 !py-2.5">
                                    Cancelar
                                </SecondaryButton>
                                <PrimaryButton :class="{ 'opacity-50 cursor-not-allowed': generateForm.processing || generateForm.roles.length === 0 || allRolesGenerated || isCheckingStatus }" :disabled="generateForm.processing || generateForm.roles.length === 0 || allRolesGenerated || isCheckingStatus" class="!bg-brand-green hover:!bg-brand-green/90 !text-white border-none !px-6 !py-2.5 shadow-lg shadow-brand-green/20">
                                    <span v-if="generateForm.processing" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processando...
                                    </span>
                                    <span v-else class="flex items-center gap-2 font-bold tracking-wide">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        EXECUTAR CÁLCULO
                                    </span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

        <!-- Modal de Carregamento / Processamento de Comissões -->
        <Modal :show="isProcessingModalOpen" maxWidth="lg" :closeable="false">
            <div class="relative overflow-hidden p-8 bg-slate-900 text-white rounded-2xl border border-brand-green/30 shadow-2xl">
                <!-- Background Ambient Pulse Glow -->
                <div class="absolute -top-20 -left-20 w-48 h-48 bg-brand-green/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl animate-pulse"></div>

                <div class="relative z-10 flex flex-col items-center text-center">
                    <!-- High-End Radial Spinner -->
                    <div class="relative w-24 h-24 mb-6 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full border-4 border-slate-800"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-brand-green border-t-transparent animate-spin"></div>
                        <div class="w-14 h-14 rounded-full bg-brand-green/10 border border-brand-green/30 flex items-center justify-center text-brand-green shadow-inner">
                            <svg class="w-7 h-7 animate-pulse text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Main Status Heading -->
                    <h3 class="text-xl font-black text-white tracking-tight mb-1">
                        Gerando Comissões dos Funcionários
                    </h3>
                    <p class="text-xs text-slate-400 font-medium mb-6">
                        Mês de Referência: <span class="text-brand-green font-bold font-mono">{{ String(generateForm.month).padStart(2, '0') }}/{{ generateForm.year }}</span>
                    </p>

                    <!-- Progress Bar & Percentage -->
                    <div class="w-full bg-slate-800 rounded-full h-3 mb-3 p-0.5 border border-slate-700/60 overflow-hidden relative">
                        <div 
                            class="bg-gradient-to-r from-brand-green via-emerald-400 to-green-300 h-full rounded-full transition-all duration-300 ease-out" 
                            :style="{ width: processProgress + '%' }"
                        ></div>
                    </div>

                    <div class="w-full flex items-center justify-between text-xs mb-6">
                        <span class="text-slate-300 font-medium truncate max-w-[82%] flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-brand-green animate-ping"></span>
                            {{ processStepText }}
                        </span>
                        <span class="font-mono font-bold text-brand-green text-sm">{{ processProgress }}%</span>
                    </div>

                    <!-- Roles List Badges being generated -->
                    <div v-if="generateForm.roles && generateForm.roles.length > 0" class="w-full pt-4 border-t border-slate-800 flex flex-col items-center">
                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-2">Cargos Selecionados</span>
                        <div class="flex flex-wrap justify-center gap-1.5">
                            <span v-for="role in generateForm.roles" :key="role" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-slate-200 border border-slate-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-brand-green animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ role }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Modal de Auditoria de Comissão -->
        <Modal :show="showAuditModal" @close="showAuditModal = false" maxWidth="lg">
            <div v-if="selectedInstallment" class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-brand-green/10 border border-brand-green/20 flex items-center justify-center text-brand-green">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Auditar Comissão</h3>
                            <p class="text-xs text-slate-500">Contrato #{{ selectedInstallment.commission?.proposal?.contract_number || '--' }}</p>
                        </div>
                    </div>
                    <button @click="showAuditModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 bg-slate-100 dark:bg-slate-800 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitAudit" class="p-6 space-y-5">
                    <!-- Resumo da Parcela -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700/60 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="block text-[10px] text-slate-400 uppercase font-bold">Beneficiário</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 truncate block">{{ selectedInstallment.commission?.user?.name || '--' }}</span>
                            <span class="text-[10px] text-slate-500">({{ selectedInstallment.commission?.role }})</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 uppercase font-bold">Valor Comissão</span>
                            <span class="font-bold font-mono text-brand-green text-sm">{{ formatCurrency(selectedInstallment.amount) }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 uppercase font-bold">Vencimento</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatDate(selectedInstallment.due_date) }}</span>
                        </div>
                    </div>

                    <!-- Seletor de Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Definir Status da Auditoria</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <!-- Opção Pago -->
                            <label 
                                class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center"
                                :class="auditForm.status === 'paid' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-600 dark:text-slate-400'"
                            >
                                <input type="radio" value="paid" v-model="auditForm.status" class="sr-only">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mb-1"></span>
                                <span class="text-xs font-bold">Pago</span>
                            </label>

                            <!-- Opção Pendente -->
                            <label 
                                class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center"
                                :class="auditForm.status === 'pending' ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 ring-2 ring-amber-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-600 dark:text-slate-400'"
                            >
                                <input type="radio" value="pending" v-model="auditForm.status" class="sr-only">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mb-1"></span>
                                <span class="text-xs font-bold">Pendente</span>
                            </label>

                            <!-- Opção Recusado -->
                            <label 
                                class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center"
                                :class="auditForm.status === 'rejected' ? 'border-rose-500 bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-600 dark:text-slate-400'"
                            >
                                <input type="radio" value="rejected" v-model="auditForm.status" class="sr-only">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mb-1"></span>
                                <span class="text-xs font-bold">Recusado</span>
                            </label>

                            <!-- Opção Cancelado -->
                            <label 
                                class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center"
                                :class="auditForm.status === 'cancelled' ? 'border-red-500 bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-2 ring-red-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-600 dark:text-slate-400'"
                            >
                                <input type="radio" value="cancelled" v-model="auditForm.status" class="sr-only">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 mb-1"></span>
                                <span class="text-xs font-bold">Cancelado</span>
                            </label>
                        </div>
                    </div>

                    <!-- Data de Pagamento -->
                    <div v-if="auditForm.status === 'paid'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Data Efetiva do Pagamento</label>
                        <input 
                            type="date" 
                            v-model="auditForm.paid_at"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                        />
                    </div>

                    <!-- Observações / Auditoria -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Observações / Histórico de Auditoria</label>
                        <textarea 
                            v-model="auditForm.notes" 
                            rows="3"
                            placeholder="Descreva detalhes como comprovante PIX, lote de pagamento ou motivo de recusa/cancelamento..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:border-brand-green focus:ring-brand-green"
                        ></textarea>
                    </div>

                    <!-- Audit Footer Info -->
                    <div v-if="selectedInstallment.audited_by" class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/60 p-2.5 rounded-lg flex items-center justify-between">
                        <span>Auditado por: <strong>{{ selectedInstallment.audited_by?.name || 'Administrador' }}</strong></span>
                        <span v-if="selectedInstallment.paid_at">Pago em: {{ formatDate(selectedInstallment.paid_at) }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <SecondaryButton type="button" @click="showAuditModal = false" class="!px-5 !py-2 text-xs">
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton :disabled="auditForm.processing" class="!bg-brand-green hover:!bg-brand-green/90 !text-white border-none !px-5 !py-2 text-xs font-bold">
                            <span v-if="auditForm.processing">Salvando...</span>
                            <span v-else>Salvar Auditoria</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Ledger Table Styling */
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

@keyframes spin { to { transform: rotate(360deg); } }
</style>
