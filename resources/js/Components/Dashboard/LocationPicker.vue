<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'Todos'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const options = [
    { label: 'Todos os Locais', value: 'Todos', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { label: 'Hotel', value: 'hotel', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { label: 'Sindicato', value: 'sindicato', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { label: 'Externo', value: 'externo', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z' },
    { label: 'Aeroporto', value: 'aeroporto', icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2 2 2 0 012 2v.1a2 2 0 002 2 2 2 0 012 2v1.5a2.5 2.5 0 01-2.5 2.5 2.5 2.5 0 01-2.5-2.5V17a2 2 0 00-2-2 2 2 0 01-2-2c0-1.105-.895-2-2-2H10a2 2 0 00-2 2v3.5a2.5 2.5 0 01-2.5 2.5 2.5 2.5 0 01-2.5-2.5V12a2 2 0 00-2-2 2 2 0 01-2-2V7a2 2 0 012-2 2 2 0 002-2 2 2 0 012-2h1.5a2.5 2.5 0 012.5 2.5z' }
];

const selectedLabel = computed(() => {
    const option = options.find(o => o.value === props.modelValue);
    return option ? option.label : 'Todos os Locais';
});

const selectOption = (value) => {
    emit('update:modelValue', value);
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
    <div class="relative inline-block text-left" ref="pickerContainer">
        <!-- Minimalist Trigger Button -->
        <button 
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between gap-2 bg-white dark:bg-[#1a1f2e] px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors shadow-sm"
        >
            <div class="flex items-center gap-2 min-w-0">
                <!-- Simple Location Icon -->
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <!-- Simple Single-line Text -->
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ selectedLabel }}</span>
            </div>
            
            <!-- Simple Dropdown Caret -->
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Elegant Dropdown (Matching DateRangePicker) -->
        <div 
            v-if="isOpen"
            class="absolute top-full right-0 mt-2 w-64 bg-white rounded-xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] z-[100] border border-gray-100 overflow-hidden animate-slide-up p-3"
        >
            <div class="flex flex-col gap-1.5">
                <button 
                    v-for="option in options" 
                    :key="option.value"
                    @click="selectOption(option.value)"
                    class="flex items-center gap-3 w-full text-left px-4 py-2.5 rounded-xl transition-all duration-200"
                    :class="modelValue === option.value 
                        ? 'bg-[#0095da] text-white shadow-lg shadow-blue-500/20' 
                        : 'text-gray-500 hover:bg-gray-200/50 hover:text-gray-800'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="option.icon" />
                    </svg>
                    <span class="text-xs font-bold">{{ option.label }}</span>
                    
                    <div v-if="modelValue === option.value" class="ml-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </button>
            </div>
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
</style>
