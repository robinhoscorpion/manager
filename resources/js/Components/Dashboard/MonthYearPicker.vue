<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String, // format 'YYYY-MM'
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);

const monthNames = [
    'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
    'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'
];

const fullMonthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
];

// Initialize from modelValue or current date
const initDate = props.modelValue ? new Date(`${props.modelValue}-01T00:00:00`) : new Date();

const currentYear = ref(initDate.getFullYear());
const currentMonth = ref(initDate.getMonth()); // 0-11

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        const parts = newVal.split('-');
        if (parts.length === 2) {
            currentYear.value = parseInt(parts[0]);
            currentMonth.value = parseInt(parts[1]) - 1;
        }
    }
});

const displayValue = computed(() => {
    if (!props.modelValue) return 'Selecionar Mês';
    return `${fullMonthNames[currentMonth.value]} de ${currentYear.value}`;
});

const selectMonth = (monthIndex) => {
    currentMonth.value = monthIndex;
    applySelection();
};

const changeYear = (offset) => {
    currentYear.value += offset;
};

const applySelection = () => {
    const val = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}`;
    emit('update:modelValue', val);
    emit('change', val);
    isOpen.value = false;
};

const pickerContainer = ref(null);
const handleClickOutside = (event) => {
    if (pickerContainer.value && !pickerContainer.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="relative inline-block text-left w-full sm:w-[240px]" ref="pickerContainer">
        <!-- Trigger Button -->
        <button 
            type="button"
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between gap-3 bg-white dark:bg-slate-900 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-brand-green dark:hover:border-brand-green transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-green/20"
        >
            <div class="flex items-center gap-3 min-w-0 text-brand-green">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">{{ displayValue }}</span>
            </div>
            
            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div 
            v-if="isOpen"
            class="absolute top-[calc(100%+8px)] right-0 w-[280px] bg-white dark:bg-slate-800 rounded-2xl shadow-xl z-[100] border border-slate-100 dark:border-slate-700 overflow-hidden animate-slide-up"
        >
            <div class="p-4">
                <!-- Year Selector -->
                <div class="flex items-center justify-between mb-4 px-2">
                    <button type="button" @click="changeYear(-1)" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <div class="text-base font-black text-slate-800 dark:text-white tracking-wider">
                        {{ currentYear }}
                    </div>
                    <button type="button" @click="changeYear(1)" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>

                <!-- Months Grid -->
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        type="button"
                        v-for="(month, idx) in monthNames" 
                        :key="idx"
                        @click="selectMonth(idx)"
                        class="py-2.5 px-2 rounded-xl text-xs font-bold transition-all duration-200"
                        :class="currentMonth === idx 
                            ? 'bg-brand-green text-white shadow-md shadow-brand-green/20' 
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    >
                        {{ month }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
