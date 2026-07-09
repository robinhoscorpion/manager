<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1">
        <template v-for="(link, p) in links" :key="p">
            <div 
                v-if="link.url === null" 
                class="mr-1 mb-1 px-3 py-2 text-[10px] sm:text-xs leading-4 text-slate-400 border border-slate-200 dark:border-slate-800 rounded-lg cursor-not-allowed bg-slate-50 dark:bg-[#0f1219]"
                v-html="link.label"
            />
            <Link 
                v-else 
                class="mr-1 mb-1 px-3 py-2 text-[10px] sm:text-xs leading-4 border rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-brand-green/20"
                :class="{ 
                    'bg-brand-green text-white border-brand-green font-bold': link.active,
                    'bg-white dark:bg-[#0f1219] text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-white/[0.02]': !link.active 
                }"
                :href="link.url"
                v-html="link.label"
            />
        </template>
    </div>
</template>
