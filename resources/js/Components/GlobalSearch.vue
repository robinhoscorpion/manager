<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const isOpen = ref(false);
const query = ref('');
const results = ref([]);
const isLoading = ref(false);
const selectedIndex = ref(0);

const toggleSearch = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        setTimeout(() => {
            document.getElementById('global-search-input')?.focus();
        }, 100);
    }
};

const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        toggleSearch();
    }
    
    if (!isOpen.value) return;

    if (e.key === 'Escape') {
        isOpen.value = false;
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % results.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + results.value.length) % results.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (results.value[selectedIndex.value]) {
            navigateTo(results.value[selectedIndex.value]);
        }
    }
};

let searchTimeout;
watch(query, (newQuery) => {
    clearTimeout(searchTimeout);
    if (newQuery.length < 2) {
        results.value = [];
        return;
    }

    isLoading.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('api.search.global'), { params: { q: newQuery } });
            results.value = response.data;
            selectedIndex.value = 0;
        } catch (error) {
            console.error('Search failed:', error);
        } finally {
            isLoading.value = false;
        }
    }, 300);
});

const navigateTo = (result) => {
    isOpen.value = false;
    query.value = '';
    router.visit(result.url);
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

</script>

<template>
    <!-- Trigger Button -->
    <button 
        @click="toggleSearch"
        class="flex items-center gap-2 md:gap-3 px-3 py-1.5 bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl transition-all group shadow-sm dark:shadow-none"
    >
        <svg class="w-4 h-4 text-indigo-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="hidden sm:inline text-[10px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest">Busca Global</span>
        <kbd class="hidden lg:inline-flex items-center gap-1 px-1.5 py-0.5 rounded border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-[9px] font-black text-slate-400 dark:text-gray-400">
            <span class="text-[8px]">CTRL</span> K
        </kbd>
    </button>

    <!-- Modal Overlay -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="isOpen" class="fixed inset-0 z-[1000] p-4 md:p-20 overflow-y-auto bg-slate-900/60 dark:bg-[#0d1117]/80 backdrop-blur-sm" @click.self="isOpen = false">
            <div class="max-w-2xl mx-auto bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-2xl shadow-2xl overflow-hidden">
                <!-- Search Input -->
                <div class="relative p-4 border-b border-white/5">
                    <svg class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input 
                        id="global-search-input"
                        v-model="query"
                        type="text"
                        placeholder="PESQUISAR CLIENTE OU CONTRATO..."
                        class="w-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-gray-600 font-black uppercase tracking-widest pl-10 text-sm"
                        autocomplete="off"
                    >
                    <div v-if="isLoading" class="absolute right-6 top-1/2 -translate-y-1/2">
                        <div class="w-4 h-4 border-2 border-indigo-500/20 border-t-indigo-500 dark:border-cyan-500/20 dark:border-t-cyan-500 rounded-full animate-spin"></div>
                    </div>
                </div>

                <!-- Results List -->
                <div class="max-h-[60vh] overflow-y-auto py-2">
                    <div v-if="results.length > 0">
                        <div 
                            v-for="(result, index) in results" 
                            :key="index"
                            @click="navigateTo(result)"
                            @mouseenter="selectedIndex = index"
                            class="px-4 py-3 cursor-pointer flex items-center justify-between group transition-colors"
                            :class="selectedIndex === index ? 'bg-indigo-500/10 dark:bg-cyan-500/10' : ''"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-white/5 flex items-center justify-center text-lg border border-slate-100 dark:border-white/5 shadow-inner">
                                    {{ result.type === 'Contrato' ? '✍️' : '👤' }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ result.title }}</p>
                                    <p class="text-[9px] font-bold text-slate-500 dark:text-gray-500 uppercase tracking-widest mt-0.5">{{ result.subtitle }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-[8px] font-black text-indigo-500 dark:text-cyan-400 uppercase tracking-[0.2em]">Consultar</span>
                                <svg class="w-4 h-4 text-indigo-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="query.length >= 2 && !isLoading" class="p-8 text-center">
                        <p class="text-xs font-black text-gray-600 uppercase tracking-widest">Nenhum resultado encontrado para "{{ query }}"</p>
                    </div>
                    <div v-else-if="query.length < 2" class="p-8 text-center text-gray-600">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em]">Digite pelo menos 2 caracteres...</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-slate-50 dark:bg-black/20 p-3 flex items-center justify-between px-6 border-t border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <kbd class="px-1 text-[8px] font-black bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded text-slate-500 dark:text-gray-500">ESC</kbd>
                            <span class="text-[8px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Sair</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <kbd class="px-1 text-[8px] font-black bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded text-slate-500 dark:text-gray-500">↵</kbd>
                            <span class="text-[8px] font-black text-slate-400 dark:text-gray-600 uppercase tracking-widest">Selecionar</span>
                        </div>
                    </div>
                    <p class="text-[8px] font-black text-slate-300 dark:text-gray-700 uppercase tracking-widest">BUSSOLA SEARCH v1.0</p>
                </div>
            </div>
        </div>
    </Transition>
</template>
