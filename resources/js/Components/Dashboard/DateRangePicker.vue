<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            start: new Date(new Date().getFullYear(), 0, 1),
            end: new Date(new Date().getFullYear(), 11, 31),
            preset: 'Todo Período'
        })
    }
});

const emit = defineEmits(['update:modelValue', 'apply']);

const isOpen = ref(false);
const selectedPreset = ref(props.modelValue.preset);

const today = new Date();
today.setHours(0, 0, 0, 0);

const dateRange = ref({
    start: props.modelValue.start,
    end: props.modelValue.end
});

// Calendar state
const viewDate = ref(new Date(today));
const currentMonth = computed(() => viewDate.value.getMonth());
const currentYear = computed(() => viewDate.value.getFullYear());

const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

const presets = [
    'Hoje',
    'Ontem',
    'Últimos 7 Dias',
    'Últimos 30 Dias',
    'Esse Mês',
    'Último Mês',
    'Todo Período',
    'Outro Período'
];

const formatDate = (date) => {
    if (!date) return '';
    const day = date.getDate();
    const month = date.toLocaleDateString('pt-BR', { month: 'long' });
    const year = date.getFullYear();
    const capMonth = month.charAt(0).toUpperCase() + month.slice(1);
    return `${day} ${capMonth}, ${year}`;
};

const displayRange = computed(() => {
    if (!dateRange.value.end) return formatDate(dateRange.value.start);
    return `${formatDate(dateRange.value.start)} - ${formatDate(dateRange.value.end)}`;
});

const setPreset = (preset) => {
    selectedPreset.value = preset;
    if (preset === 'Outro Período') return;

    let start = new Date(today);
    let end = new Date(today);

    switch (preset) {
        case 'Hoje': break;
        case 'Ontem':
            start.setDate(today.getDate() - 1);
            end.setDate(today.getDate() - 1);
            break;
        case 'Últimos 7 Dias':
            start.setDate(today.getDate() - 7);
            break;
        case 'Últimos 30 Dias':
            start.setDate(today.getDate() - 30);
            break;
        case 'Esse Mês':
            start.setDate(1);
            break;
        case 'Último Mês':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'Todo Período':
            start = new Date(2026, 0, 1);
            end = new Date(2026, 11, 31);
            break;
    }
    dateRange.value = { start, end };
    viewDate.value = new Date(start); // Move calendar to start date
};

// Calendar Logic
const generateCalendarDays = computed(() => {
    const days = [];
    const firstDay = new Date(currentYear.value, currentMonth.value, 1);
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);
    const offset = firstDay.getDay();
    
    for (let i = 0; i < offset; i++) {
        const prevDate = new Date(currentYear.value, currentMonth.value, -offset + i + 1);
        days.push({ date: prevDate, currentMonth: false });
    }
    
    for (let i = 1; i <= lastDay.getDate(); i++) {
        days.push({ date: new Date(currentYear.value, currentMonth.value, i), currentMonth: true });
    }

    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({ date: new Date(currentYear.value, currentMonth.value + 1, i), currentMonth: false });
    }
    
    return days;
});

const selectDay = (date) => {
    if (!dateRange.value.start || (dateRange.value.start && dateRange.value.end)) {
        dateRange.value.start = date;
        dateRange.value.end = null;
    } else {
        if (date < dateRange.value.start) {
            dateRange.value.end = dateRange.value.start;
            dateRange.value.start = date;
        } else {
            dateRange.value.end = date;
        }
        selectedPreset.value = 'Outro Período';
    }
};

const isSelected = (date) => {
    if (!date) return false;
    const time = date.getTime();
    return (dateRange.value.start?.getTime() === time) || (dateRange.value.end?.getTime() === time);
};

const isInRange = (date) => {
    if (!date || !dateRange.value.start || !dateRange.value.end) return false;
    const time = date.getTime();
    return time > dateRange.value.start.getTime() && time < dateRange.value.end.getTime();
};

const changeMonth = (offset) => {
    viewDate.value = new Date(currentYear.value, currentMonth.value + offset, 1);
};

const apply = () => {
    if (!dateRange.value.end) dateRange.value.end = new Date(dateRange.value.start);
    const result = {
        start: dateRange.value.start,
        end: dateRange.value.end,
        preset: selectedPreset.value
    };
    emit('update:modelValue', result);
    emit('apply', result);
    isOpen.value = false;
};

const cancel = () => isOpen.value = false;

const pickerContainer = ref(null);
const handleClickOutside = (event) => {
    if (pickerContainer.value && !pickerContainer.value.contains(event.target)) isOpen.value = false;
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="relative inline-block text-left" ref="pickerContainer">
        <!-- Minimalist Trigger Button (Matches Uploaded Image) -->
        <button 
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between gap-2 bg-white dark:bg-[#1a1f2e] px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors shadow-sm"
        >
            <div class="flex items-center gap-2 min-w-0">
                <!-- Simple Calendar Icon -->
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <!-- Simple Single-line Text -->
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ displayRange }}</span>
            </div>
            
            <!-- Simple Dropdown Caret -->
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Elegant Dual-Panel Dropdown -->
        <div 
            v-if="isOpen"
            class="absolute top-full right-0 mt-4 flex bg-white rounded-2xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] z-[100] border border-gray-100 overflow-hidden animate-slide-up"
        >
            <!-- Left Panel: Presets -->
            <div class="w-48 bg-[#f8fafc] p-6 border-r border-gray-100 flex flex-col gap-1.5">
                <h4 class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-3 ml-2">Atalhos</h4>
                <button 
                    v-for="preset in presets" 
                    :key="preset"
                    @click="setPreset(preset)"
                    class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200"
                    :class="selectedPreset === preset 
                        ? 'bg-[#0095da] text-white shadow-lg shadow-blue-500/20' 
                        : 'text-gray-500 hover:bg-gray-200/50 hover:text-gray-800'"
                >
                    {{ preset }}
                </button>
            </div>

            <!-- Right Panel: Calendar -->
            <div class="w-[320px] p-6 flex flex-col bg-white">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-6 px-2">
                    <button @click="changeMonth(-1)" class="p-2 rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <div class="text-sm font-black text-gray-800 uppercase tracking-widest">
                        {{ monthNames[currentMonth] }} {{ currentYear }}
                    </div>
                    <button @click="changeMonth(1)" class="p-2 rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-y-1 mb-6">
                    <div v-for="day in weekDays" :key="day" class="text-[10px] font-black text-gray-300 uppercase text-center py-2 tracking-tighter">{{ day }}</div>
                    
                    <div 
                        v-for="(day, idx) in generateCalendarDays" 
                        :key="idx"
                        class="relative flex justify-center py-1.5"
                    >
                        <!-- Range Background -->
                        <div 
                            v-if="isInRange(day.date)"
                            class="absolute inset-y-1 left-0 right-0 bg-blue-50"
                            :class="{ 
                                'rounded-l-full ml-1': day.date.getDay() === 0,
                                'rounded-r-full mr-1': day.date.getDay() === 6
                            }"
                        ></div>

                        <button 
                            @click="selectDay(day.date)"
                            class="relative w-8.5 h-8.5 flex items-center justify-center rounded-xl text-xs font-bold transition-all z-10"
                            :class="[
                                day.currentMonth ? 'text-gray-700' : 'text-gray-200',
                                isSelected(day.date) ? 'bg-[#0095da] text-white shadow-md' : 'hover:bg-gray-100',
                                isInRange(day.date) ? 'text-[#0095da]' : ''
                            ]"
                        >
                            {{ day.date.getDate() }}
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2.5 mt-auto">
                    <button 
                        @click="apply"
                        class="flex-1 bg-[#5bbb61] hover:bg-[#4eaa54] text-white text-[11px] font-black py-3 px-4 rounded-xl transition-all shadow-lg shadow-green-500/20 uppercase tracking-[0.1em]"
                    >
                        Confirmar
                    </button>
                    <button 
                        @click="cancel"
                        class="flex-1 bg-[#ff4b81] hover:bg-[#e03d6d] text-white text-[11px] font-black py-3 px-4 rounded-xl transition-all shadow-lg shadow-pink-500/20 uppercase tracking-[0.1em]"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
            
            <!-- Elegant Indicator Arrow -->
            <div class="absolute -top-1.5 right-12 w-3 h-3 bg-white border-t border-l border-gray-100 rotate-45"></div>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.w-8\.5 { width: 2.125rem; }
.h-8\.5 { height: 2.125rem; }
</style>
