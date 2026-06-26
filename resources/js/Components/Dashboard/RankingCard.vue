<script setup>
defineProps({
    title: String,
    sellers: {
        type: Array,
        required: true,
    },
    headerColor: {
        type: String,
        default: 'bg-indigo-500'
    }
});
</script>

<template>
    <div class="professional-card flex flex-col h-full group">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-[var(--border-color)]">
            <div class="flex items-center gap-3">
                <div class="w-1.5 h-6 rounded-full" :class="headerColor"></div>
                <h3 class="text-base font-bold text-[var(--text-main)] font-heading uppercase tracking-wide">{{ title }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-[var(--nav-hover-bg)] flex items-center justify-center text-[var(--text-muted)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
        </div>

        <!-- Premium Glassmorphism Podium -->
        <div class="flex-1 flex items-end justify-center gap-2 sm:gap-4 relative px-2 mt-4">
            
            <!-- 2nd Place -->
            <div class="flex flex-col items-center w-1/3 relative group/podium">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full border border-slate-300 dark:border-slate-500 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md shadow-lg overflow-hidden z-10 relative mb-[-1.5rem] group-hover/podium:-translate-y-2 transition-transform duration-300">
                    <img v-if="sellers[1]?.profile_photo_url || sellers[1]?.avatar" :src="sellers[1]?.profile_photo_url || sellers[1]?.avatar" class="w-full h-full object-cover">
                    <div v-else class="w-full h-full flex items-center justify-center font-bold text-[var(--text-muted)] text-sm">
                        {{ (sellers[1]?.name || '??').substring(0, 2).toUpperCase() }}
                    </div>
                </div>
                <div class="w-full h-28 sm:h-32 rounded-t-2xl border border-b-0 backdrop-blur-xl flex flex-col items-center justify-end pb-3 transition-all duration-300 relative overflow-hidden bg-slate-100/60 dark:bg-slate-800/40 border-white/60 dark:border-white/10 shadow-[inset_0_1px_2px_rgba(255,255,255,0.8)] dark:shadow-[inset_0_1px_1px_rgba(255,255,255,0.05)]">
                    <span class="text-slate-400/30 dark:text-slate-500/20 text-3xl sm:text-4xl font-black italic absolute top-4">2</span>
                    <p class="font-semibold text-[var(--text-main)] text-xs sm:text-sm truncate w-full text-center px-1 relative z-10">{{ sellers[1]?.name || '-' }}</p>
                    <p class="font-black text-[var(--text-main)] text-sm sm:text-base mt-1 relative z-10">{{ sellers[1]?.value || '0' }}</p>
                    <!-- Glass shine -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 dark:via-white/5 to-transparent opacity-0 group-hover/podium:opacity-100 transition-opacity duration-500"></div>
                </div>
            </div>

            <!-- 1st Place -->
            <div class="flex flex-col items-center w-1/3 relative group/podium z-20">
                <div class="absolute -top-6 w-8 h-8 bg-amber-400 rounded-full flex items-center justify-center shadow-lg shadow-amber-400/40 z-20 animate-bounce ring-4 ring-amber-400/20">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72a1 1 0 00.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border-2 border-amber-300 dark:border-amber-500 bg-white/90 dark:bg-amber-900/40 backdrop-blur-md shadow-xl shadow-amber-400/20 overflow-hidden z-10 relative mb-[-2rem] group-hover/podium:-translate-y-2 transition-transform duration-300 ring-4 ring-white/50 dark:ring-black/20">
                    <img v-if="sellers[0]?.profile_photo_url || sellers[0]?.avatar" :src="sellers[0]?.profile_photo_url || sellers[0]?.avatar" class="w-full h-full object-cover">
                    <div v-else class="w-full h-full flex items-center justify-center font-bold text-[var(--text-muted)] text-base">
                        {{ (sellers[0]?.name || '??').substring(0, 2).toUpperCase() }}
                    </div>
                </div>
                <div class="w-full h-36 sm:h-44 rounded-t-2xl border border-b-0 backdrop-blur-xl flex flex-col items-center justify-end pb-4 transition-all duration-300 relative overflow-hidden bg-amber-100/50 dark:bg-amber-900/20 border-white/80 dark:border-amber-500/20 shadow-[inset_0_1px_3px_rgba(255,255,255,0.9)] dark:shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)]">
                    <span class="text-amber-500/20 dark:text-amber-500/10 text-5xl sm:text-6xl font-black italic absolute top-4">1</span>
                    <p class="font-bold text-[var(--text-main)] text-sm sm:text-base truncate w-full text-center px-1 relative z-10">{{ sellers[0]?.name || '-' }}</p>
                    <p class="font-black text-amber-600 dark:text-amber-400 text-lg sm:text-xl mt-1 relative z-10">{{ sellers[0]?.value || '0' }}</p>
                    <!-- Glass shine -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/30 dark:via-white/5 to-transparent opacity-0 group-hover/podium:opacity-100 transition-opacity duration-500"></div>
                </div>
            </div>

            <!-- 3rd Place -->
            <div class="flex flex-col items-center w-1/3 relative group/podium">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full border border-orange-300 dark:border-orange-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md shadow-md overflow-hidden z-10 relative mb-[-1rem] group-hover/podium:-translate-y-2 transition-transform duration-300">
                    <img v-if="sellers[2]?.profile_photo_url || sellers[2]?.avatar" :src="sellers[2]?.profile_photo_url || sellers[2]?.avatar" class="w-full h-full object-cover">
                    <div v-else class="w-full h-full flex items-center justify-center font-bold text-[var(--text-muted)] text-xs">
                        {{ (sellers[2]?.name || '??').substring(0, 2).toUpperCase() }}
                    </div>
                </div>
                <div class="w-full h-20 sm:h-24 rounded-t-2xl border border-b-0 backdrop-blur-xl flex flex-col items-center justify-end pb-2 transition-all duration-300 relative overflow-hidden bg-orange-50/60 dark:bg-orange-900/10 border-white/60 dark:border-white/5 shadow-[inset_0_1px_2px_rgba(255,255,255,0.8)] dark:shadow-[inset_0_1px_1px_rgba(255,255,255,0.05)]">
                    <span class="text-orange-400/20 dark:text-orange-500/10 text-2xl sm:text-3xl font-black italic absolute top-3">3</span>
                    <p class="font-medium text-[var(--text-main)] text-[11px] sm:text-xs truncate w-full text-center px-1 relative z-10">{{ sellers[2]?.name || '-' }}</p>
                    <p class="font-bold text-[var(--text-main)] text-xs sm:text-sm mt-1 relative z-10">{{ sellers[2]?.value || '0' }}</p>
                    <!-- Glass shine -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 dark:via-white/5 to-transparent opacity-0 group-hover/podium:opacity-100 transition-opacity duration-500"></div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.font-heading {
    font-family: var(--font-heading);
}

.professional-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow-card);
    transition: box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
}

.professional-card:hover {
    box-shadow: var(--shadow-card-hover);
    transform: translateY(-3px);
    border-color: var(--primary-border);
}
</style>
