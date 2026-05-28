<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['select']);

const isOpen = ref(false);
const options = [
    { label: 'Manifesto Geral', value: 'general_manifest' },
    { label: 'Manifesto Vendas', value: 'sales_manifest' },
    { label: 'Relatório de VPU', value: 'vpu_report' },
    { label: 'Relatório de Casais', value: 'couples_report' },
    { label: 'Planilha CSV', value: 'csv_export' }
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
        <!-- Elegant Trigger Button -->
        <button 
            @click="isOpen = !isOpen"
            class="group flex items-center gap-4 bg-[#0095da] hover:bg-[#0084c2] px-5 py-2.5 rounded-2xl transition-all duration-300 shadow-xl min-w-[180px]"
        >
            <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-white/20 text-white group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            </div>
            <div class="flex flex-col items-start leading-none text-white">
                <span class="text-[10px] uppercase font-bold opacity-70 tracking-[0.15em] mb-1">Gerar</span>
                <span class="text-sm font-black tracking-tight uppercase">Exportar</span>
            </div>
            <svg class="w-4 h-4 text-white/50 transition-transform duration-300 ml-auto" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Elegant Dropdown (Blue/Pink Style from image) -->
        <div 
            v-if="isOpen"
            class="absolute top-full right-0 mt-4 w-64 bg-white rounded-[2rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] z-[100] border border-gray-100 overflow-hidden animate-slide-up"
        >
            <!-- Blue Header of Dropdown -->
            <div class="bg-[#0095da] px-6 py-4">
                <h4 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                    Exportar
                    <span class="ml-auto opacity-50">▾</span>
                </h4>
            </div>

            <div class="p-3 flex flex-col gap-1">
                <button 
                    v-for="option in options" 
                    :key="option.value"
                    @click="selectOption(option.value)"
                    class="flex items-center gap-3 w-full text-left px-5 py-4 rounded-xl transition-all duration-200 text-[#ff4b81] hover:bg-pink-50 hover:scale-[1.02]"
                >
                    <span class="text-[13px] font-bold leading-tight">{{ option.label }}</span>
                </button>
            </div>
            
            <!-- Elegant Indicator Arrow -->
            <div class="absolute -top-1.5 right-12 w-3 h-3 bg-[#0095da] rotate-45"></div>
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
