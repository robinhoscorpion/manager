<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'Todos'
    },
    qualifications: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const options = computed(() => [
    { label: 'Todas as Qualificações', value: 'Todos', color: null },
    ...props.qualifications.map(q => ({ label: `${q.code} · ${q.name}`, value: q.code, color: q.color })),
]);

const selectedLabel = computed(() => {
    const option = options.value.find(o => o.value === props.modelValue);
    return option ? option.label : 'Todas as Qualificações';
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
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
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
            <div class="flex flex-col gap-1.5 max-h-80 overflow-y-auto">
                <button 
                    v-for="option in options" 
                    :key="option.value"
                    @click="selectOption(option.value)"
                    class="flex items-center gap-3 w-full text-left px-4 py-2.5 rounded-xl transition-all duration-200"
                    :class="modelValue === option.value 
                        ? 'bg-[#0095da] text-white shadow-lg shadow-blue-500/20' 
                        : 'text-gray-500 hover:bg-gray-200/50 hover:text-gray-800'"
                >
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="option.color || 'bg-gray-300'"></span>
                    <span class="text-xs font-bold truncate">{{ option.label }}</span>
                    
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
