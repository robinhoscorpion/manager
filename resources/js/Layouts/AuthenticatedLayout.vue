<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Toast from '@/Components/Toast.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import '@/../css/dashboard_style.css';

const pageLoading = ref(false);
let loadingTimeout = null;

onMounted(() => {
    router.on('start', () => {
        pageLoading.value = true;
    });

    router.on('finish', () => {
        // Atraso de 400ms para manter a fluidez do fantasma
        loadingTimeout = setTimeout(() => {
            pageLoading.value = false;
        }, 400);
    });
});

onUnmounted(() => {
    clearTimeout(loadingTimeout);
});

const showingMobileMenu = ref(false);
const user = usePage().props.auth.user;

const toggleMobileMenu = () => {
    showingMobileMenu.value = !showingMobileMenu.value;
};

const salesRoomOpen = ref(false);
const financeiroOpen = ref(false);
const posVendaOpen = ref(false);
const _configOpen = ref(false);

// Watch for route changes to auto-expand menus
watch(() => usePage().url, () => {
    if (route().current('sales.*')) salesRoomOpen.value = true;
    if (route().current('finance.*')) financeiroOpen.value = true;
    if (route().current('after-sales.*')) posVendaOpen.value = true;
    if (route().current('admin.*')) _configOpen.value = true;
}, { immediate: true });

const toggleSalesRoom = () => {
    salesRoomOpen.value = !salesRoomOpen.value;
};

const toggleFinanceiro = () => {
    financeiroOpen.value = !financeiroOpen.value;
};

const togglePosVenda = () => {
    posVendaOpen.value = !posVendaOpen.value;
};

const configOpen = computed(() => _configOpen.value);

const toggleConfig = () => {
    _configOpen.value = !_configOpen.value;
};

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
};

// Sidebar Collapsed State
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value);
};
</script>

<template>
    <div class="dashboard-layout" :class="{ 'collapsed': sidebarCollapsed }">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'show': showingMobileMenu }">
            <div class="sidebar-content">
                <div class="sidebar-header px-2 py-4">
                    <Link :href="route('dashboard')" class="flex items-center justify-center w-full group transition-transform hover:scale-105">
                        <ApplicationLogo class="w-auto h-12" />
                    </Link>
                </div>

                <nav class="sidebar-nav">
                    <Link 
                        :href="route('dashboard')" 
                        class="nav-item" 
                        :class="{ 'active': route().current('dashboard') }"
                        title="Dashboard"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </Link>

                    <!-- Sala de Vendas Group -->
                    <div v-if="can('atendimentos.ver')" class="nav-group" :class="{ 'open': salesRoomOpen }">
                        <div class="nav-item nav-item-toggle" @click="toggleSalesRoom">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Sala de Vendas</span>
                            </div>
                            <svg class="nav-item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        
                        <div class="nav-sub-menu">
                            <Link :href="route('sales.agendamentos.index')" class="nav-sub-item" :class="{ 'active': route().current('sales.agendamentos.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('sales.agendamentos.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Agendamentos
                            </Link>
                            <Link :href="route('sales.atendimentos')" class="nav-sub-item" :class="{ 'active': route().current('sales.atendimentos') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('sales.atendimentos') ? 'opacity-100' : 'opacity-30'"></div>
                                Atendimentos
                            </Link>
                        </div>
                    </div>

                    <!-- Financeiro Group -->
                    <div class="nav-group" :class="{ 'open': financeiroOpen }">
                        <div class="nav-item nav-item-toggle" @click="toggleFinanceiro">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Financeiro</span>
                            </div>
                            <svg class="nav-item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        
                        <div class="nav-sub-menu">
                            <Link :href="route('finance.receivables.index')" class="nav-sub-item" :class="{ 'active': route().current('finance.receivables.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('finance.receivables.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Recebíveis
                            </Link>
                            <Link :href="route('finance.sales-control.index')" class="nav-sub-item" :class="{ 'active': route().current('finance.sales-control.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('finance.sales-control.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Controle de Vendas
                            </Link>
                        </div>
                    </div>

                    <!-- Pós-venda Group -->
                    <div class="nav-group" :class="{ 'open': posVendaOpen }">
                        <div class="nav-item nav-item-toggle" @click="togglePosVenda">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Pós-venda</span>
                            </div>
                            <svg class="nav-item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        
                        <div class="nav-sub-menu">
                            <Link :href="route('after-sales.welcome.index')" class="nav-sub-item" :class="{ 'active': route().current('after-sales.welcome.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('after-sales.welcome.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Boas-vindas
                            </Link>
                            <Link :href="route('after-sales.contract-delivery.index')" class="nav-sub-item" :class="{ 'active': route().current('after-sales.contract-delivery.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('after-sales.contract-delivery.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Gestão de Contratos
                            </Link>
                            <Link href="#" class="nav-sub-item">
                                <div class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></div>
                                Onboarding
                            </Link>
                            <Link href="#" class="nav-sub-item">
                                <div class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></div>
                                Pendências documentais
                            </Link>

                            <Link href="#" class="nav-sub-item">
                                <div class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></div>
                                Treinamentos
                            </Link>
                            <Link href="#" class="nav-sub-item">
                                <div class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></div>
                                Acompanhamentos
                            </Link>
                            <Link href="#" class="nav-sub-item">
                                <div class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></div>
                                Campanhas de relacionamento
                            </Link>
                            <Link :href="route('after-sales.birthdays.index')" class="nav-sub-item" :class="{ 'active': route().current('after-sales.birthdays.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('after-sales.birthdays.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Aniversariantes
                            </Link>
                        </div>
                    </div>


                    <Link 
                        v-if="can('funcionarios.ver')"
                        :href="route('employees.index')" 
                        class="nav-item" 
                        :class="{ 'active': route().current('employees.*') }"
                        title="Funcionários"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Funcionários</span>
                    </Link>

                    <Link 
                        v-if="can('usuarios.ver')"
                        :href="route('users.index')" 
                        class="nav-item" 
                        :class="{ 'active': route().current('users.*') }"
                        title="Usuários do Sistema"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Usuários</span>
                    </Link>

                    <Link 
                        v-if="can('cargos.gerenciar')"
                        :href="route('roles.index')" 
                        class="nav-item" 
                        :class="{ 'active': route().current('roles.*') }"
                        title="Cargos e Permissões"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Cargos</span>
                    </Link>

                    <div v-if="can('atendimentos.gerenciar')" class="nav-group" :class="{ 'open': configOpen }">
                        <div class="nav-item nav-item-toggle" @click="toggleConfig">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Configurações</span>
                            </div>
                            <svg class="nav-item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        
                        <div class="nav-sub-menu">
                            <Link :href="route('admin.settings.columns.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.settings.columns.index') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.settings.columns.index') ? 'opacity-100' : 'opacity-30'"></div>
                                Colunas do Dashboard
                            </Link>
                            <Link :href="route('admin.proposal_templates.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.proposal_templates.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.proposal_templates.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Modelos de Proposta
                            </Link>
                            <Link :href="route('admin.contract_templates.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.contract_templates.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.contract_templates.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Modelos de Contrato
                            </Link>
                            <Link :href="route('admin.products.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.products.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.products.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Gestão de Produtos
                            </Link>
                            <Link :href="route('admin.maintenance.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.maintenance.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.maintenance.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Gestão de Manutenção
                            </Link>
                            <Link :href="route('admin.payment_methods.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.payment_methods.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.payment_methods.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Formas de Pagamento
                            </Link>
                            <Link :href="route('admin.qualifications.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.qualifications.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.qualifications.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Tipos de Qualificação
                            </Link>
                            <Link :href="route('admin.complimentary_items.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.complimentary_items.*') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.complimentary_items.*') ? 'opacity-100' : 'opacity-30'"></div>
                                Cortesias
                            </Link>
                            <Link v-if="can('usuarios.editar')" :href="route('admin.audit-logs.index')" class="nav-sub-item" :class="{ 'active': route().current('admin.audit-logs.index') }">
                                <div class="w-1.5 h-1.5 rounded-full bg-current" :class="route().current('admin.audit-logs.index') ? 'opacity-100' : 'opacity-30'"></div>
                                Logs do Sistema
                            </Link>
                        </div>
                    </div>
                </nav>

                <div class="sidebar-footer">
                    <div class="user-profile">
                        <div class="user-info text-left">
                            <div class="user-name">{{ user.name }}</div>
                            <div class="user-email text-[10px]">{{ user.email }}</div>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex flex-col gap-1">
                        <Link :href="route('profile.edit')" class="nav-item py-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Perfil</span>
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="nav-item py-2 w-full text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Sair</span>
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Header -->
        <header class="mobile-header">
            <button @click="toggleMobileMenu" class="menu-toggle">
                <svg v-if="!showingMobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <Link :href="route('dashboard')" class="flex items-center transition-transform hover:scale-105">
                <ApplicationLogo class="w-auto h-8" />
            </Link>
        </header>

        <!-- Overlay for mobile menu -->
        <div 
            v-if="showingMobileMenu" 
            @click="showingMobileMenu = false"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[95] md:hidden"
        ></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <!-- Page Header slot -->
                <div class="topbar-left flex items-start sm:items-center gap-4">
                    <button @click="toggleSidebar" class="menu-toggle hidden md:flex mt-1 sm:mt-0" title="Recolher menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="flex-1 min-w-0">
                        <slot name="header" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="topbar-right">
                    <!-- Theme Toggle Removed -->

                    <GlobalSearch />
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <slot />
            </div>
        </main>

        <!-- Global Toast Notifications -->
        <Toast />
    </div>
</template>
