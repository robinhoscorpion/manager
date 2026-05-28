<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const show = ref(false);
const message = ref('');
const type = ref('success'); // 'success' or 'error'
const timeout = ref(null);

const close = () => {
    show.value = false;
};

const notify = (msg, t = 'success') => {
    if (timeout.value) clearTimeout(timeout.value);
    
    message.value = msg;
    type.value = t;
    show.value = true;
    
    timeout.value = setTimeout(() => {
        show.value = false;
    }, 5000);
};

// Watch for flash messages from Inertia
watch(() => usePage().props.flash, (flash) => {
    if (flash.message) {
        notify(flash.message, 'success');
    }
    if (flash.error) {
        notify(flash.error, 'error');
    }
}, { deep: true, immediate: true });

defineExpose({ notify });
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed top-5 right-5 z-[200] w-full max-w-sm overflow-hidden rounded-2xl border shadow-2xl backdrop-blur-xl transition-all duration-300"
            :class="type === 'success' 
                ? 'bg-white dark:bg-green-500/10 border-green-200 dark:border-green-500/20 shadow-green-500/10' 
                : 'bg-white dark:bg-red-500/10 border-red-200 dark:border-red-500/20 shadow-red-500/10'"
        >
            <div class="p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <svg v-if="type === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Error Icon -->
                        <svg v-else class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1 pt-0.5">
                        <p class="text-xs font-black uppercase tracking-widest" :class="type === 'success' ? 'text-green-400' : 'text-red-400'">
                            {{ type === 'success' ? 'Sucesso' : 'Atenção' }}
                        </p>
                        <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white leading-relaxed">
                            {{ message }}
                        </p>
                    </div>
                    <button @click="close" class="flex-shrink-0 text-slate-400 dark:text-white/40 hover:text-slate-900 dark:hover:text-white transition-colors">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-current opacity-20 transition-all duration-[5000ms] ease-linear w-0"
                :class="type === 'success' ? 'text-green-500' : 'text-red-500'"
                :style="{ width: show ? '100%' : '0%' }"
            ></div>
        </div>
    </Transition>
</template>
