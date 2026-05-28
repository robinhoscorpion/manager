<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F5F5F5] font-sans text-slate-900 antialiased relative overflow-hidden">
        <div class="relative z-10 w-full max-w-lg px-4 py-12 sm:px-6">
            <div class="backdrop-blur-xl bg-white/90 px-8 py-10 shadow-2xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:rounded-3xl sm:p-14">
                <div class="mb-8 flex flex-col items-center justify-center">
                    <Link href="/" class="transition-transform duration-300 hover:scale-105">
                        <ApplicationLogo class="h-16" />
                    </Link>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
