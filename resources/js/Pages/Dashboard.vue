<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import SalesChart from '@/Components/Dashboard/SalesChart.vue';
import GoalProgress from '@/Components/Dashboard/GoalProgress.vue';
import ConsultantList from '@/Components/Dashboard/ConsultantList.vue';
import BarChart from '@/Components/Dashboard/BarChart.vue';
import DoughnutChart from '@/Components/Dashboard/DoughnutChart.vue';
import RankingCard from '@/Components/Dashboard/RankingCard.vue';

const activeTab = ref('vendas');

const rankingSellers = [
    { name: 'Ricardo Santos', avatar: 'images/dashboard/avatar_illu1.png', value: 'R$ 840.000' },
    { name: 'Juliana Lima', avatar: 'images/dashboard/avatar_illu2.png', value: 'R$ 620.000' },
    { name: 'Marcos Vinicius', avatar: 'images/dashboard/avatar_illu3.png', value: 'R$ 410.000' },
];

const consultantsData = [
    { name: 'Ana Silva', sales: 42, total: 125000 },
    { name: 'Bruno Costa', sales: 38, total: 110000 },
    { name: 'Carla Dias', sales: 35, total: 98000 },
    { name: 'Diego Ramos', sales: 29, total: 85000 },
    { name: 'Elaine Oliveira', sales: 24, total: 72000 },
];

const teamsData = {
    labels: ['Equipe Alpha', 'Equipe Beta', 'Equipe Gamma', 'Equipe Delta'],
    datasets: [{
        label: 'Vendas',
        data: [150, 120, 180, 90],
        backgroundColor: ['#00d2ff', '#9200ff', '#ff007a', '#00ff88'],
        borderRadius: 8,
    }]
};

const sourcesVendasData = {
    labels: ['WhatsApp', 'Instagram', 'Google', 'Facebook'],
    datasets: [{
        data: [45, 25, 20, 10],
        backgroundColor: ['#25D366', '#E1306C', '#4285F4', '#1877F2'],
        borderWidth: 0,
        hoverOffset: 10
    }]
};

const sourcesLeadsData = {
    labels: ['WhatsApp', 'Instagram', 'Google', 'Facebook'],
    datasets: [{
        data: [1200, 800, 600, 400],
        backgroundColor: ['#25D366', '#E1306C', '#4285F4', '#1877F2'],
        borderWidth: 0,
        hoverOffset: 10
    }]
};

const chartData = {
    labels: Array.from({ length: 31 }, (_, i) => (i + 1).toString().padStart(2, '0')),
    datasets: [
        {
            label: 'Vendas',
            data: [1, 4, 1, 2, 5, 3, 2, 3, 4, 4, 6, 5, 7, 2, 3, 8, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#00d2ff',
            backgroundColor: 'rgba(0, 210, 255, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#00d2ff',
            pointBorderColor: '#fff',
            pointHoverRadius: 6,
        },
        {
            label: 'Atendimentos',
            data: [7, 25, 6, 4, 8, 14, 6, 8, 16, 18, 17, 19, 16, 3, 6, 25, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#9200ff',
            backgroundColor: 'transparent',
            tension: 0.4,
            pointBackgroundColor: '#9200ff',
            pointBorderColor: '#fff',
            pointHoverRadius: 6,
        }
    ]
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-center gap-8 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                    <!-- Tabs -->
                    <div class="flex border-b border-slate-200 dark:border-white/5 min-w-max">
                        <button 
                            @click="activeTab = 'vendas'"
                            class="px-6 py-2 text-xs font-bold transition-all"
                            :class="activeTab === 'vendas' ? 'text-indigo-600 dark:text-cyan-400 border-b-2 border-indigo-600 dark:border-cyan-400' : 'text-slate-400 dark:text-gray-500 hover:text-slate-600 dark:hover:text-gray-300'"
                        >
                            VENDAS
                        </button>
                        <button 
                            @click="activeTab = 'meta'"
                            class="px-6 py-2 text-xs font-bold transition-all"
                            :class="activeTab === 'meta' ? 'text-indigo-600 dark:text-cyan-400 border-b-2 border-indigo-600 dark:border-cyan-400' : 'text-slate-400 dark:text-gray-500 hover:text-slate-600 dark:hover:text-gray-300'"
                        >
                            META
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                    <!-- Filters -->
                    <div class="flex items-center gap-2 bg-white dark:bg-white/5 p-1 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none">
                        <div class="px-3 py-1.5 text-xs text-slate-500 dark:text-gray-400 font-semibold border-r border-slate-200 dark:border-white/5">03/2026</div>
                        <select class="bg-transparent border-none text-xs text-slate-900 dark:text-white font-semibold focus:ring-0 cursor-pointer pr-8">
                            <option class="bg-white dark:bg-slate-900">Sala de Vendas</option>
                        </select>
                    </div>

                    <!-- Print Button -->
                    <button class="bg-pink-600 p-2 rounded-full shadow-lg shadow-pink-600/20 hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 animate-fade-in-up stagger-1">
            <StatCard title="meta total" value="R$ 3.000.000" trend="+12.5%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>
            </StatCard>
            <StatCard title="valor vendido" value="R$ 1.840.150" color="text-indigo-600 dark:text-cyan-400" trend="+4.3%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5 text-indigo-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatCard>
            <StatCard title="saldo restante" value="R$ 1.159.850" color="text-purple-400" trend="-2.1%" :trend-up="false">
                <template #icon>
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </template>
            </StatCard>
            <StatCard title="meta/dia (superada)" value="R$ 291.762" color="text-green-400" trend="+15.8%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" />
                    </svg>
                </template>
            </StatCard>
        </div>

        <!-- Chart Section -->
        <div class="bg-white dark:bg-[#191e29]/50 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl p-4 shadow-xl dark:shadow-none relative overflow-hidden animate-fade-in-up stagger-2">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Vendas por dia</h3>
                
                <!-- Legend -->
                <div class="flex items-center gap-4 text-[10px] font-bold uppercase">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 dark:bg-cyan-400"></span>
                        <span class="text-gray-400 tracking-wider">Vendas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        <span class="text-gray-400 tracking-wider">Atendimentos</span>
                    </div>
                </div>
            </div>

            <SalesChart :data="chartData" />
            
            <GoalProgress :percentage="61" />
        </div>

        <!-- Lower Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4 animate-fade-in-up stagger-3">
            <!-- Vendas por Consultores -->
            <div class="bg-white dark:bg-[#191e29]/50 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-xl dark:shadow-none">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-wider border-l-4 border-indigo-500 dark:border-cyan-500 pl-4">Vendas por Consultores</h3>
                <ConsultantList :consultants="consultantsData" />
            </div>

            <!-- Vendas por Equipes -->
            <div class="bg-white dark:bg-[#191e29]/50 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-xl dark:shadow-none">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-wider border-l-4 border-purple-500 pl-4">Vendas por Equipes</h3>
                <BarChart :data="teamsData" />
            </div>
        </div>

        <!-- Distribution Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6 animate-fade-in-up stagger-4">
            <!-- Vendas por Origem -->
            <div class="bg-white dark:bg-[#191e29]/50 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-xl dark:shadow-none">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-wider border-l-4 border-indigo-500 dark:border-cyan-500 pl-4">Vendas por Origem</h3>
                <DoughnutChart :data="sourcesVendasData" />
            </div>

            <!-- Leads por Origem -->
            <div class="bg-white dark:bg-[#191e29]/50 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl p-6 shadow-xl dark:shadow-none">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-wider border-l-4 border-pink-500 pl-4">Leads por Origem</h3>
                <DoughnutChart :data="sourcesLeadsData" />
            </div>
        </div>

        <!-- Seller Information Section -->
        <div class="mt-6 animate-fade-in-up stagger-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-indigo-600 dark:bg-cyan-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-600/20 dark:shadow-cyan-600/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wider">Informações dos Vendedores</h2>
                
                <button class="ml-auto bg-indigo-500 dark:bg-cyan-500 p-2 rounded-full shadow-lg shadow-indigo-500/20 dark:shadow-cyan-500/20 hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <RankingCard title="Promotores" :sellers="rankingSellers" :header-color="'bg-indigo-500 dark:bg-cyan-500'" />
                <RankingCard title="Consultores" :sellers="rankingSellers" :header-color="'bg-indigo-600 dark:bg-cyan-600'" />
                <RankingCard title="Supervisores" :sellers="rankingSellers" :header-color="'bg-indigo-700 dark:bg-cyan-700'" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
select {
    appearance: none;
    -webkit-appearance: none;
}
</style>
