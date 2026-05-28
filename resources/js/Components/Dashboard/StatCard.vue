<script setup>
defineProps({
    title: String,
    value: String,
    color: {
        type: String,
        default: 'text-white'
    },
    trend: {
        type: String,
        default: '+0.0%'
    },
    trendUp: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <div class="stat-card p-4 rounded-xl border border-slate-200 dark:border-white/5 bg-white dark:bg-white/5 backdrop-blur-xl group relative overflow-hidden shadow-sm dark:shadow-none">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-500/5 dark:from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        
        <div class="flex items-start justify-between relative z-10">
            <div>
                <div class="text-[11px] uppercase font-bold text-slate-400 dark:text-gray-400 mb-2 tracking-[0.1em]">{{ title }}</div>
                <div class="text-2xl font-extrabold" :class="color.replace('text-white', 'text-slate-900 dark:text-white')">{{ value }}</div>
                
                <div class="mt-3 flex items-center gap-1.5 font-bold text-[10px]" :class="trendUp ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    <svg v-if="trendUp" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                    </svg>
                    {{ trend }}
                    <span class="text-slate-400 dark:text-gray-500 font-medium">vs último mês</span>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/5 group-hover:bg-slate-100 dark:group-hover:bg-white/10 transition-colors">
                <slot name="icon">
                    <svg class="w-5 h-5 text-slate-400 dark:text-gray-400 group-hover:text-slate-600 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </slot>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat-card {
    min-width: 180px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.stat-card:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-5px);
    box-shadow: 0 10px 40px -15px rgba(0,0,0,0.5);
}
</style>
