<script setup>
defineProps({
    percentage: Number,
    label: String
});
</script>

<template>
    <div class="mt-8 flex items-center gap-6 bg-white/5 border border-white/5 p-4 rounded-xl backdrop-blur-md">
        <div class="flex-shrink-0 bg-cyan-500/10 border border-cyan-500/20 px-3 py-1.5 rounded-lg">
            <span class="text-cyan-400 font-bold text-xs">{{ percentage }}% da meta</span>
        </div>
        
        <div class="flex-1 bg-white/5 h-2 rounded-full overflow-hidden">
            <div 
                class="h-full bg-gradient-to-r from-cyan-500 to-purple-600 shadow-[0_0_15px_rgba(6,182,212,0.5)] transition-all duration-1000 ease-out"
                :style="{ width: percentage + '%' }"
            ></div>
        </div>
    </div>
</template>
