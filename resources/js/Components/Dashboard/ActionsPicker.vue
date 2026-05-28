<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['select']);

const isOpen = ref(false);

const exportOptions = [
    { label: 'Planilha CSV (.csv)', value: 'csv_export' },
    { label: 'Excel (.xlsx)', value: 'excel_export' },
    { label: 'Relatório PDF (.pdf)', value: 'pdf_export' }
];

const impressionOptions = [
    { label: 'Manifesto Geral', value: 'general_manifest' },
    { label: 'Manifesto de Vendas', value: 'sales_manifest' },
    { label: 'Relatório de VPU', value: 'vpu_report' },
    { label: 'Relatório de Casais', value: 'couples_report' }
];

const selectOption = (value) => {
    emit('select', value);
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
                <!-- Simple Action Icon -->
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                <!-- Simple Single-line Text -->
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">Ações</span>
            </div>
            
            <!-- Simple Dropdown Caret -->
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Consolidated Dropdown -->
        <div 
            v-if="isOpen"
            class="absolute top-full right-0 mt-4 w-72 bg-white rounded-2xl shadow-[0_30px_70px_-15px_rgba(0,0,0,0.4)] z-[200] border border-gray-100 overflow-hidden animate-slide-up"
        >
            <!-- EXPORTAR SECTION -->
            <div class="bg-[#0095da]/5 px-6 py-4 border-b border-gray-50">
                <h4 class="text-[10px] font-black text-[#0095da] uppercase tracking-[0.2em] flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar
                </h4>
            </div>
            <div class="p-2">
                <button 
                    v-for="option in exportOptions" 
                    :key="option.value"
                    @click="selectOption(option.value)"
                    class="flex items-center gap-3 w-full text-left px-5 py-3.5 rounded-xl transition-all duration-200 text-[#ff4b81] hover:bg-pink-50 hover:translate-x-1"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-pink-400"></div>
                    <span class="text-[13px] font-bold tracking-tight">{{ option.label }}</span>
                </button>
            </div>

            <!-- IMPRESSÕES SECTION -->
            <div class="bg-[#0095da]/5 px-6 py-4 border-y border-gray-50">
                <h4 class="text-[10px] font-black text-[#0095da] uppercase tracking-[0.2em] flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Impressões
                </h4>
            </div>
            <div class="p-2 pb-4">
                <button 
                    v-for="option in impressionOptions" 
                    :key="option.value"
                    @click="selectOption(option.value)"
                    class="flex items-center gap-3 w-full text-left px-5 py-3.5 rounded-xl transition-all duration-200 text-[#ff4b81] hover:bg-pink-50 hover:translate-x-1"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-pink-400"></div>
                    <span class="text-[13px] font-bold tracking-tight">{{ option.label }}</span>
                </button>
            </div>
            
            <!-- Indicator Arrow -->
            <div class="absolute -top-1.5 right-12 w-3 h-3 bg-white border-t border-l border-gray-100 rotate-45"></div>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(15px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
