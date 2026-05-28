<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    title: { type: String, default: 'Gerar' },
    label: { type: String, required: true },
    options: { type: Array, required: true },
    icon: { type: String, default: 'print' }, // 'print' or 'export'
    primaryColor: { type: String, default: '#0095da' }
});

const emit = defineEmits(['select']);

const isOpen = ref(false);

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
            class="group flex items-center gap-4 px-5 py-2.5 rounded-2xl transition-all duration-300 shadow-xl min-w-[180px]"
            :style="{ backgroundColor: primaryColor }"
        >
            <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-white/20 text-white group-hover:scale-110 transition-transform">
                <!-- Export Icon -->
                <svg v-if="icon === 'export'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <!-- Print Icon -->
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            </div>
            <div class="flex flex-col items-start leading-none text-white">
                <span class="text-[10px] uppercase font-bold opacity-70 tracking-[0.15em] mb-1">{{ title }}</span>
                <span class="text-sm font-black tracking-tight uppercase">{{ label }}</span>
            </div>
            <svg class="w-4 h-4 text-white/50 transition-transform duration-300 ml-auto" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Elegant Dropdown -->
        <div 
            v-if="isOpen"
            class="absolute top-full right-0 mt-4 w-64 bg-white rounded-[2rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] z-[100] border border-gray-100 overflow-hidden animate-slide-up"
        >
            <!-- Colored Header of Dropdown -->
            <div class="px-6 py-4" :style="{ backgroundColor: primaryColor }">
                <h4 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                    {{ label }}
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
            
            <!-- Indicator Arrow -->
            <div class="absolute -top-1.5 right-12 w-3 h-3 rotate-45" :style="{ backgroundColor: primaryColor }"></div>
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
