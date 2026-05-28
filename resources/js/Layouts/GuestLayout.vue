<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center relative overflow-hidden font-sans antialiased">
        <!-- Premium Background -->
        <div class="absolute inset-0 bg-[#E5ECEC] z-0"></div>
        <img class="absolute inset-0 h-full w-full object-cover opacity-[0.15] mix-blend-multiply blur-[2px]" src="/images/login_feature_bg.png" alt="Background" />
        
        <div class="relative z-10 w-full max-w-[22rem] px-4 py-6">
            <!-- Glassmorphism Card -->
            <div class="bg-white/80 backdrop-blur-2xl px-6 py-6 shadow-[0_8px_30px_rgb(0,0,0,0.08)] ring-1 ring-slate-900/5 rounded-2xl relative overflow-hidden">
                <!-- Subtle top glow -->
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
                
                <div class="mb-5 flex flex-col items-center justify-center text-center">
                    <Link href="/" class="transition-transform duration-300 hover:scale-105 inline-block w-48 h-16">
                        <ApplicationLogo class="w-full h-full drop-shadow-sm" />
                    </Link>
                </div>
                
                <slot />
            </div>
        </div>
    </div>
</template>
