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
        backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd'],
        borderRadius: 6,
    }]
};

const sourcesVendasData = {
    labels: ['WhatsApp', 'Instagram', 'Google', 'Facebook'],
    datasets: [{
        data: [45, 25, 20, 10],
        backgroundColor: ['#25D366', '#E1306C', '#4285F4', '#1877F2'],
        borderWidth: 0,
        hoverOffset: 8
    }]
};

const sourcesLeadsData = {
    labels: ['WhatsApp', 'Instagram', 'Google', 'Facebook'],
    datasets: [{
        data: [1200, 800, 600, 400],
        backgroundColor: ['#25D366', '#E1306C', '#4285F4', '#1877F2'],
        borderWidth: 0,
        hoverOffset: 8
    }]
};

const chartData = {
    labels: Array.from({ length: 31 }, (_, i) => (i + 1).toString().padStart(2, '0')),
    datasets: [
        {
            label: 'Vendas',
            data: [1, 4, 1, 2, 5, 3, 2, 3, 4, 4, 6, 5, 7, 2, 3, 8, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.08)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
        {
            label: 'Atendimentos',
            data: [7, 25, 6, 4, 8, 14, 6, 8, 16, 18, 17, 19, 16, 3, 6, 25, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#a78bfa',
            backgroundColor: 'transparent',
            tension: 0.4,
            pointBackgroundColor: '#a78bfa',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
        }
    ]
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <!-- ═══════════════════════════════════════════════════
                 Dashboard Header — Unified bar
            ════════════════════════════════════════════════════ -->
            <div class="dash-header">

                <!-- ── Left block: title + subtitle ── -->
                <div class="dash-header-left">
                    <div class="dash-period-badge">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Março 2026
                    </div>
                    <h1 class="dash-title">Dashboard</h1>
                    <p class="dash-subtitle">Visão geral de performance da Sala de Vendas</p>
                </div>

                <!-- ── Right block: controls ── -->
                <div class="dash-header-right">

                    <!-- Sala de Vendas selector -->
                    <div class="dash-selector">
                        <div class="dash-selector-icon">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span>Sala de Vendas</span>
                        <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <!-- Divider -->
                    <div class="dash-divider"></div>

                    <!-- Tabs -->
                    <div class="dash-tabs">
                        <button
                            @click="activeTab = 'vendas'"
                            class="dash-tab"
                            :class="activeTab === 'vendas' ? 'dash-tab-active' : 'dash-tab-inactive'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Vendas
                        </button>
                        <button
                            @click="activeTab = 'meta'"
                            class="dash-tab"
                            :class="activeTab === 'meta' ? 'dash-tab-active' : 'dash-tab-inactive'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Meta
                        </button>
                    </div>
                </div>
            </div>
        </template>



        <!-- ── KPI Cards ────────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade-in-up stagger-1">
            <StatCard title="Meta Total" value="R$ 3.000.000" trend="+12,5%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Valor Vendido" value="R$ 1.840.150" trend="+4,3%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Saldo Restante" value="R$ 1.159.850" trend="-2,1%" :trend-up="false">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Meta/Dia (Superada)" value="R$ 291.762" trend="+15,8%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" />
                    </svg>
                </template>
            </StatCard>
        </div>

        <!-- ── Sales Chart ─────────────────────────────────── -->
        <div class="card mb-6 animate-fade-in-up stagger-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Vendas por dia</h3>
                    <p class="card-subtitle">Evolução diária de vendas e atendimentos</p>
                </div>
                <div class="legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #6366f1;"></span>
                        <span>Vendas</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #a78bfa;"></span>
                        <span>Atendimentos</span>
                    </div>
                </div>
            </div>

            <SalesChart :data="chartData" />
            <GoalProgress :percentage="61" />
        </div>

        <!-- ── Secondary KPI Cards (Atendimentos & Sócios) ── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade-in-up stagger-2">
            <StatCard title="Total de Vendas" value="R$ 1.840.150" trend="+8,4%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Novos Sócios" value="142" trend="+12,5%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Atendimentos" value="324" trend="-2,1%" :trend-up="false">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                </template>
            </StatCard>

            <StatCard title="Aproveitamento" value="43,8%" trend="+5,2%" :trend-up="true">
                <template #icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </template>
            </StatCard>
        </div>


        <!-- ── Informações dos Vendedores ────────────────── -->
        <div class="animate-fade-in-up stagger-5">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Informações dos Vendedores</h2>
                    <p class="section-subtitle">Ranking por categoria de atuação</p>
                </div>
                <button class="action-btn" title="Imprimir">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <RankingCard title="Promotores" :sellers="rankingSellers" :header-color="'bg-indigo-500'" />
                <RankingCard title="Consultores" :sellers="rankingSellers" :header-color="'bg-violet-500'" />
                <RankingCard title="Supervisores" :sellers="rankingSellers" :header-color="'bg-purple-600'" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Tab Buttons ──────────────────────────────────────── */
.tab-btn {
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.15s ease;
    cursor: pointer;
    border: none;
    outline: none;
}

.tab-btn-active {
    background: #ffffff;
    color: #6366f1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dark .tab-btn-active {
    background: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    box-shadow: none;
}

.tab-btn-inactive {
    background: transparent;
    color: #64748b;
}

.tab-btn-inactive:hover {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.5);
}

.dark .tab-btn-inactive {
    color: #475569;
}

.dark .tab-btn-inactive:hover {
    color: #94a3b8;
    background: rgba(255,255,255,0.04);
}

/* ── Filter Pills ────────────────────────────────────── */
.filter-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.filter-pill:hover {
    border-color: var(--primary-border);
    color: var(--text-main);
}

/* ── Action Button ───────────────────────────────────── */
.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.action-btn:hover {
    border-color: var(--primary-border);
    color: var(--primary);
    background: var(--primary-light);
}

/* ── Cards ───────────────────────────────────────────── */
.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow-card);
    transition: box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
}

.card:hover {
    box-shadow: var(--shadow-card-hover);
    transform: translateY(-3px);
    border-color: var(--primary-border);
}

.card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 12px;
}

.card-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.2;
    font-family: var(--font-heading);
}

.card-subtitle {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
}

/* ── Legend ──────────────────────────────────────────── */
.legend {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: var(--text-muted);
    font-weight: 500;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ── Section Header ──────────────────────────────────── */
.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 12px;
}

.section-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-main);
    font-family: var(--font-heading);
}

.section-subtitle {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
}

/* ── Dashboard Header ────────────────────────────────── */
.dash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}

.dash-header-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dash-period-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--primary);
    background: var(--primary-light);
    border: 1px solid var(--primary-border);
    padding: 3px 9px;
    border-radius: 999px;
    width: fit-content;
    margin-bottom: 6px;
    letter-spacing: 0.02em;
}

.dash-title {
    font-family: var(--font-heading);
    font-size: 22px;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.1;
    letter-spacing: -0.4px;
}

.dash-subtitle {
    font-size: 12.5px;
    color: var(--text-muted);
    margin-top: 3px;
}

/* Right block */
.dash-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 6px 10px;
    box-shadow: var(--shadow-card);
    flex-shrink: 0;
}

/* Divider */
.dash-divider {
    width: 1px;
    height: 20px;
    background: var(--border-color);
    flex-shrink: 0;
}

/* Sala selector */
.dash-selector {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    transition: all 0.15s ease;
    white-space: nowrap;
}

.dash-selector:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.dash-selector-icon {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: var(--primary-light);
    border: 1px solid var(--primary-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}

/* Tabs inside the header bar */
.dash-tabs {
    display: flex;
    gap: 2px;
}

.dash-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 7px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.dash-tab-active {
    background: var(--primary-light);
    color: var(--primary);
    border-color: var(--primary-border);
}

.dash-tab-inactive {
    background: transparent;
    color: var(--text-muted);
}

.dash-tab-inactive:hover {
    background: var(--nav-hover-bg);
    color: var(--text-secondary);
}

/* Icon button */
.dash-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.dash-icon-btn:hover {
    background: var(--primary-light);
    border-color: var(--primary-border);
    color: var(--primary);
}
</style>
