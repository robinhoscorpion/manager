<script setup>
defineProps({
    title: String,
    sellers: {
        type: Array,
        required: true, // Expected order: [2nd, 1st, 3rd] to match visual alignment or [Rank 1, Rank 2, Rank 3]
    },
    accentColor: {
        type: String,
        default: 'border-cyan-500'
    },
    headerColor: {
        type: String,
        default: 'bg-cyan-500'
    }
});

// Sellers props assumed as [ {name, avatar, rank} ] sorted 1, 2, 3
</script>

<template>
    <div class="ranking-card bg-white dark:bg-[#11151c] border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden shadow-xl dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex flex-col h-full group relative">
        <!-- Mesh Gradient Header -->
        <div class="px-5 py-3 flex items-center justify-between relative overflow-hidden h-12" :class="headerColor">
            <div class="absolute inset-0 opacity-40 bg-[radial-gradient(at_0%_0%,_rgba(255,255,255,0.3)_0,_transparent_50%),_radial-gradient(at_100%_100%,_rgba(0,0,0,0.3)_0,_transparent_50%)]"></div>
            <div class="absolute inset-0 opacity-20 bg-[url('https://grainy-gradients.vercel.app/noise.svg')]"></div>
            
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-1.5 h-6 bg-white/40 rounded-full"></div>
                <h3 class="text-base font-black text-white uppercase tracking-[0.2em] font-sans">{{ title }}</h3>
            </div>

            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-xl border border-white/20 flex items-center justify-center relative z-10 shadow-inner group-hover:rotate-12 transition-transform duration-500">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>

        <!-- Podium Content -->
        <div class="relative flex-1 md:p-8 p-4 flex items-end justify-center md:min-h-[400px] min-h-[350px] bg-grid-light dark:bg-grid-dark overflow-hidden">
            <!-- Background Aura for 1st Place -->
            <div class="absolute left-1/2 md:bottom-48 bottom-40 -translate-x-1/2 md:w-80 md:h-80 w-60 h-60 bg-cyan-500/5 dark:bg-cyan-500/10 blur-[100px] md:blur-[120px] rounded-full pointer-events-none"></div>

            <!-- Glassmorphism Podium Steps -->
            <div class="relative w-full flex items-end justify-center md:gap-4 gap-2 h-full">
                <!-- 2nd Place -->
                <div class="flex flex-col items-center flex-1 relative group/seller">
                    <div class="relative mb-6">
                        <div class="md:w-20 md:h-20 w-16 h-16 rounded-full border-2 border-slate-200 dark:border-white/20 bg-white dark:bg-white/10 backdrop-blur-md flex items-center justify-center p-1 group-hover/seller:scale-110 group-hover/seller:-translate-y-1 transition-all duration-500 cursor-help shadow-2xl overflow-hidden text-slate-900 dark:text-white font-bold text-lg">
                            <img v-if="sellers[1]?.profile_photo_url || sellers[1]?.avatar" :src="sellers[1]?.profile_photo_url || sellers[1]?.avatar" class="w-full h-full object-cover rounded-full" alt="2nd">
                            <span v-else>{{ (sellers[1]?.name || '??').substring(0, 2).toUpperCase() }}</span>
                        </div>
                    </div>
                    
                    <!-- Tooltip -->
                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 px-4 py-2 bg-white/90 backdrop-blur-xl border border-white rounded-lg text-[#11151c] text-xs font-black whitespace-nowrap opacity-0 md:group-hover/seller:opacity-100 group-hover/seller:-top-16 transition-all duration-300 pointer-events-none shadow-[0_10px_30px_rgba(255,255,255,0.2)] z-30">
                        {{ sellers[1]?.name }}
                    </div>

                    <div class="w-full md:h-[150px] h-[130px] bg-gradient-to-t from-slate-50 to-slate-100 dark:from-white/10 dark:to-white/20 border border-slate-200 dark:border-white/20 rounded-xl flex flex-col items-center justify-center gap-1 shadow-inner relative overflow-hidden px-[1px]">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-slate-300 dark:via-white/40 to-transparent"></div>
                        <span class="text-slate-100 dark:text-white/10 md:text-6xl text-5xl font-black italic absolute md:top-4 top-2 left-1/2 -translate-x-1/2">2</span>
                        <div class="flex flex-col items-center relative z-10 md:pt-10 pt-8 w-full">
                            <span class="text-slate-400 dark:text-white/60 text-[10px] font-black tracking-[0.2em] mb-1">VENDAS</span>
                            <span class="text-slate-900 dark:text-white md:text-xl text-lg font-black tracking-tight leading-none text-center truncate w-full">{{ sellers[1]?.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- 1st Place -->
                <div class="flex flex-col items-center flex-1 relative group/seller z-10">
                    <div class="relative mb-8">
                        <!-- Orbit Effect -->
                        <div class="absolute inset-0 md:-m-3 -m-2 border border-cyan-500/20 rounded-2xl animate-spin-slow pointer-events-none"></div>
                        
                        <div class="md:w-24 md:h-24 w-20 h-20 rounded-full border-4 border-cyan-500/50 bg-white dark:bg-white/10 backdrop-blur-md flex items-center justify-center p-1 shadow-xl dark:shadow-[0_0_50px_rgba(6,182,212,0.4)] group-hover/seller:scale-110 group-hover/seller:-translate-y-3 transition-all duration-500 cursor-help relative animate-float overflow-hidden text-slate-900 dark:text-white font-black text-2xl">
                            <img v-if="sellers[0]?.profile_photo_url || sellers[0]?.avatar" :src="sellers[0]?.profile_photo_url || sellers[0]?.avatar" class="w-full h-full object-cover rounded-full" alt="1st">
                            <span v-else>{{ (sellers[0]?.name || '??').substring(0, 2).toUpperCase() }}</span>
                            
                            <!-- Crown Badge -->
                            <div class="absolute -top-4 -right-4 md:w-10 md:h-10 w-8 h-8 bg-yellow-400 rounded-lg flex items-center justify-center shadow-lg dark:shadow-[0_10px_20px_rgba(250,204,21,0.4)] rotate-12 group-hover/seller:rotate-0 transition-transform duration-500">
                                <svg class="md:w-6 md:h-6 w-5 h-5 text-yellow-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72a1 1 0 00.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tooltip -->
                    <div class="absolute -top-16 left-1/2 -translate-x-1/2 px-4 py-2 bg-cyan-500 dark:bg-cyan-400 border border-cyan-400 dark:border-cyan-300 rounded-lg text-white dark:text-black text-xs font-black whitespace-nowrap opacity-0 md:group-hover/seller:opacity-100 group-hover/seller:-top-20 transition-all duration-300 pointer-events-none shadow-xl dark:shadow-[0_10px_30px_rgba(6,182,212,0.4)] z-30">
                        {{ sellers[0]?.name }}
                    </div>

                    <div class="w-full md:h-[210px] h-[180px] bg-gradient-to-t from-cyan-500/10 to-cyan-500/20 dark:from-cyan-500/20 dark:to-cyan-500/30 border border-cyan-500/30 dark:border-cyan-500/40 rounded-xl flex flex-col items-center justify-center gap-0 shadow-inner dark:shadow-[inset_0_0_30px_rgba(6,182,212,0.3)] relative overflow-hidden px-[1px]">
                        <!-- Neon Line -->
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_15px_rgba(6,182,212,1)]"></div>
                        <span class="text-cyan-500/5 dark:text-cyan-500/10 md:text-8xl text-7xl font-black italic absolute md:top-8 top-4 left-1/2 -translate-x-1/2">1</span>
                        <div class="flex flex-col items-center relative z-10 md:pt-16 pt-12 w-full">
                            <span class="text-cyan-600 dark:text-cyan-400 md:text-xs text-[10px] font-black tracking-[0.3em] mb-2">DADOS DE VENDAS</span>
                            <span class="text-slate-900 dark:text-white md:text-2xl text-xl font-black tracking-tighter leading-none dark:glow-text text-center truncate w-full">{{ sellers[0]?.value }}</span>
                        </div>
                        
                        <!-- Shining Effect -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out"></div>
                    </div>
                </div>

                <!-- 3rd Place -->
                <div class="flex flex-col items-center flex-1 relative group/seller">
                    <div class="relative mb-4">
                        <div class="md:w-16 md:h-16 w-14 h-14 rounded-full border-2 border-slate-200 dark:border-white/20 bg-white dark:bg-white/10 backdrop-blur-md flex items-center justify-center p-1 group-hover/seller:scale-110 group-hover/seller:-translate-y-1 transition-all duration-500 cursor-help shadow-xl overflow-hidden text-slate-900 dark:text-white font-bold text-base">
                            <img v-if="sellers[2]?.profile_photo_url || sellers[2]?.avatar" :src="sellers[2]?.profile_photo_url || sellers[2]?.avatar" class="w-full h-full object-cover rounded-full" alt="3rd">
                            <span v-else>{{ (sellers[2]?.name || '??').substring(0, 2).toUpperCase() }}</span>
                        </div>
                    </div>
                    
                    <!-- Tooltip -->
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-white/10 backdrop-blur-md border border-white/10 rounded-lg text-white text-[10px] font-bold whitespace-nowrap opacity-0 md:group-hover/seller:opacity-100 group-hover/seller:-top-12 transition-all duration-300 pointer-events-none shadow-xl z-30">
                        {{ sellers[2]?.name }}
                    </div>

                    <div class="w-full md:h-[120px] h-[110px] bg-gradient-to-t from-slate-50 to-slate-100 dark:from-white/10 dark:to-white/20 border border-slate-200 dark:border-white/20 rounded-xl flex flex-col items-center justify-center gap-0 shadow-inner relative overflow-hidden px-[1px]">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-slate-300 dark:via-white/40 to-transparent"></div>
                        <span class="text-slate-100 dark:text-white/10 md:text-5xl text-4xl font-black italic absolute md:top-4 top-2 left-1/2 -translate-x-1/2">3</span>
                        <div class="flex flex-col items-center relative z-10 md:pt-10 pt-8 w-full">
                            <span class="text-slate-400 dark:text-white/60 text-[9px] font-black tracking-[0.2em] mb-1">VENDAS</span>
                            <span class="text-slate-900 dark:text-white md:text-lg text-base font-black tracking-tighter leading-none text-center truncate w-full">{{ sellers[2]?.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ranking-card {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.ranking-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.6);
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 12s linear infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
.animate-float {
    animation: float 4s ease-in-out infinite;
}

.bg-grid-light {
    background-image: radial-gradient(circle at 2px 2px, rgba(0, 0, 0, 0.03) 1px, transparent 0);
    background-size: 32px 32px;
}

.bg-grid-dark {
    background-image: radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.03) 1px, transparent 0);
    background-size: 32px 32px;
}
</style>
