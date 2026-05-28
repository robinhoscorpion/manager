<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Toast from '@/Components/Toast.vue';
import { Link, usePage } from '@inertiajs/vue3';
import GlobalSearch from '@/Components/GlobalSearch.vue';

const sidebarOpen = ref(true);
const showingNavigationDropdown = ref(false);
const user = usePage().props.auth.user;

const salesRoomOpen = ref(false);
const _configOpen = ref(false);

// Watch for route changes to auto-expand menus
watch(() => usePage().url, () => {
    if (route().current('sales.*')) salesRoomOpen.value = true;
    if (route().current('admin.*')) _configOpen.value = true;
}, { immediate: true });

const toggleSalesRoom = () => {
    salesRoomOpen.value = !salesRoomOpen.value;
};

const configOpen = computed(() => _configOpen.value);

const toggleConfig = () => {
    _configOpen.value = !_configOpen.value;
};

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
};

// Theme Management (Optional - but kept for compatibility)
const theme = ref('light');

const toggleTheme = () => {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', theme.value);
    updateTheme();
};

const updateTheme = () => {
    if (theme.value === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

onMounted(() => {
    theme.value = localStorage.getItem('theme') || 'light';
    updateTheme();
});
</script>

<template>
    <div class="flex h-screen bg-[#F8FAFC] dark:bg-[#0F172A] font-sans text-slate-900 dark:text-white antialiased overflow-hidden transition-colors duration-300">
        
        <!-- Sidebar (Desktop) -->
        <aside 
            :class="sidebarOpen ? 'w-[260px]' : 'w-0'"
            class="hidden flex-shrink-0 flex-col bg-[#171717] text-[#ECECEC] transition-all duration-300 md:flex overflow-hidden relative z-50 border-r border-slate-800"
        >
            <div class="flex h-full flex-col p-3 w-[260px]">
                
                <!-- Top Actions -->
                <div class="flex items-center justify-between mb-4 mt-1 px-1">
                    <Link :href="route('dashboard')" class="flex items-center gap-3 group text-left hover:opacity-80 transition-opacity">
                        <ApplicationLogo class="h-8 w-auto drop-shadow-md text-white fill-current" />
                        <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-cyan-400">MANAGER</span>
                    </Link>
                    
                    <button @click="sidebarOpen = false" class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors" title="Fechar sidebar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation List -->
                <nav class="flex-1 overflow-y-auto space-y-1 mt-2 custom-scrollbar">
                    <div class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Principal</div>
                    
                    <Link
                        :href="route('dashboard')"
                        :class="[route().current('dashboard') ? 'bg-[#2A2B32] text-white font-semibold' : 'text-gray-300 hover:bg-[#2A2B32]', 'group flex items-center rounded-lg px-2.5 py-2.5 text-sm transition-colors']"
                    >
                        <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('dashboard') ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="truncate">Dashboard</span>
                    </Link>

                    <!-- Sala de Vendas -->
                    <div v-if="can('atendimentos.ver')" class="mt-1">
                        <button @click="toggleSalesRoom" :class="[route().current('sales.*') || salesRoomOpen ? 'bg-[#2A2B32] text-white' : 'text-gray-300 hover:bg-[#2A2B32]', 'w-full group flex items-center justify-between rounded-lg px-2.5 py-2.5 text-sm transition-colors']">
                            <div class="flex items-center">
                                <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('sales.*') || salesRoomOpen ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <span class="truncate" :class="{'font-semibold': route().current('sales.*') || salesRoomOpen}">Sala de Vendas</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-90': salesRoomOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        
                        <!-- Submenu -->
                        <div v-show="salesRoomOpen" class="mt-1 space-y-1 pl-9 pr-2">
                            <Link :href="route('sales.atendimentos')" :class="[route().current('sales.atendimentos') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('sales.atendimentos') ? 'bg-indigo-400' : 'bg-gray-600'"></div>
                                <span class="truncate">Atendimentos</span>
                            </Link>
                            <Link :href="route('sales.linha')" :class="[route().current('sales.linha') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('sales.linha') ? 'bg-indigo-400' : 'bg-gray-600'"></div>
                                <span class="truncate">Linha de Atendimento</span>
                            </Link>
                        </div>
                    </div>

                    <div class="px-2 pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Gestão</div>

                    <Link v-if="can('funcionarios.ver')" :href="route('employees.index')" :class="[route().current('employees.*') ? 'bg-[#2A2B32] text-white font-semibold' : 'text-gray-300 hover:bg-[#2A2B32]', 'group flex items-center rounded-lg px-2.5 py-2.5 text-sm transition-colors mt-1']">
                        <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('employees.*') ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="truncate">Funcionários</span>
                    </Link>

                    <Link v-if="can('usuarios.ver')" :href="route('users.index')" :class="[route().current('users.*') ? 'bg-[#2A2B32] text-white font-semibold' : 'text-gray-300 hover:bg-[#2A2B32]', 'group flex items-center rounded-lg px-2.5 py-2.5 text-sm transition-colors mt-1']">
                        <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('users.*') ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="truncate">Usuários</span>
                    </Link>

                    <Link v-if="can('cargos.gerenciar')" :href="route('roles.index')" :class="[route().current('roles.*') ? 'bg-[#2A2B32] text-white font-semibold' : 'text-gray-300 hover:bg-[#2A2B32]', 'group flex items-center rounded-lg px-2.5 py-2.5 text-sm transition-colors mt-1']">
                        <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('roles.*') ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="truncate">Cargos e Permissões</span>
                    </Link>

                    <div class="px-2 pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sistema</div>

                    <!-- Configurações -->
                    <div v-if="can('atendimentos.gerenciar')" class="mt-1">
                        <button @click="toggleConfig" :class="[route().current('admin.*') || configOpen ? 'bg-[#2A2B32] text-white' : 'text-gray-300 hover:bg-[#2A2B32]', 'w-full group flex items-center justify-between rounded-lg px-2.5 py-2.5 text-sm transition-colors']">
                            <div class="flex items-center">
                                <svg class="mr-2.5 h-4.5 w-4.5 shrink-0" :class="route().current('admin.*') || configOpen ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="truncate" :class="{'font-semibold': route().current('admin.*') || configOpen}">Configurações</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-90': configOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        
                        <!-- Submenu -->
                        <div v-show="configOpen" class="mt-1 space-y-1 pl-9 pr-2">
                            <Link :href="route('admin.settings.columns.index')" :class="[route().current('admin.settings.columns.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.settings.columns.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Colunas do Dashboard</span>
                            </Link>
                            <Link :href="route('admin.proposal_templates.index')" :class="[route().current('admin.proposal_templates.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.proposal_templates.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Modelos de Proposta</span>
                            </Link>
                            <Link :href="route('admin.contract_templates.index')" :class="[route().current('admin.contract_templates.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.contract_templates.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Modelos de Contrato</span>
                            </Link>
                            <Link :href="route('admin.products.index')" :class="[route().current('admin.products.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.products.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Gestão de Produtos</span>
                            </Link>
                            <Link :href="route('admin.maintenance.index')" :class="[route().current('admin.maintenance.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.maintenance.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Manutenção</span>
                            </Link>
                            <Link :href="route('admin.payment_methods.index')" :class="[route().current('admin.payment_methods.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.payment_methods.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Formas de Pagamento</span>
                            </Link>
                            <Link :href="route('admin.qualifications.index')" :class="[route().current('admin.qualifications.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.qualifications.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Tipos de Qualificação</span>
                            </Link>
                            <Link :href="route('admin.complimentary_items.index')" :class="[route().current('admin.complimentary_items.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.complimentary_items.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Cortesias</span>
                            </Link>
                            <Link v-if="can('usuarios.editar')" :href="route('admin.audit-logs.index')" :class="[route().current('admin.audit-logs.*') ? 'text-white bg-white/5' : 'text-gray-400 hover:text-white hover:bg-white/5', 'group flex items-center rounded-lg px-3 py-2 text-[13px] transition-colors']">
                                <div class="w-1.5 h-1.5 rounded-full mr-2.5" :class="route().current('admin.audit-logs.*') ? 'bg-indigo-400' : 'bg-gray-600'"></div><span class="truncate">Logs do Sistema</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User profile bottom -->
                <div class="mt-auto pt-4 pb-1 border-t border-white/5">
                    <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-lg p-2 text-sm hover:bg-[#2A2B32] transition-colors w-full text-left">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center text-white font-bold shrink-0 shadow-inner">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="block truncate font-medium text-white">{{ user.name }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ user.email }}</span>
                        </div>
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="mt-2 flex w-full items-center gap-3 rounded-lg p-2 text-sm text-gray-400 hover:text-rose-400 hover:bg-rose-400/10 transition-colors text-left">
                        <svg class="h-5 w-5 shrink-0 px-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span class="truncate">Encerrar Sessão</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col min-w-0 overflow-hidden relative">
            
            <!-- Open Sidebar Button (when sidebar is closed) -->
            <button 
                v-show="!sidebarOpen"
                @click="sidebarOpen = true"
                class="absolute top-4 left-4 z-20 p-2.5 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 rounded-xl transition-all hidden md:block border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md"
                title="Abrir sidebar"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </button>

            <!-- Mobile Header -->
            <header class="flex h-16 items-center justify-between bg-white dark:bg-[#1E293B] px-4 border-b border-slate-200 dark:border-slate-800 md:hidden z-30 relative shadow-sm">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-7 w-auto fill-current text-indigo-600 dark:text-indigo-400" />
                    <span class="font-bold text-slate-800 dark:text-white tracking-tight">MANAGER</span>
                </Link>
                <div class="flex items-center gap-2">
                    <button @click="toggleTheme" class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                        <svg v-if="theme === 'dark'" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Mobile Menu Dropdown -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="absolute inset-x-0 top-16 z-20 bg-white dark:bg-[#1E293B] border-b border-slate-200 dark:border-slate-800 md:hidden shadow-xl max-h-[calc(100vh-4rem)] overflow-y-auto">
                <div class="space-y-1 pb-3 pt-2 px-4">
                    <Link :href="route('dashboard')" class="block py-2.5 text-base font-medium text-slate-700 dark:text-slate-200">Dashboard</Link>
                    
                    <div v-if="can('atendimentos.ver')" class="py-2 border-t border-slate-100 dark:border-slate-800 mt-2">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Sala de Vendas</div>
                        <Link :href="route('sales.atendimentos')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Atendimentos</Link>
                        <Link :href="route('sales.linha')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Linha de Atendimento</Link>
                    </div>

                    <div class="py-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Gestão</div>
                        <Link v-if="can('funcionarios.ver')" :href="route('employees.index')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Funcionários</Link>
                        <Link v-if="can('usuarios.ver')" :href="route('users.index')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Usuários</Link>
                        <Link v-if="can('cargos.gerenciar')" :href="route('roles.index')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Cargos e Permissões</Link>
                    </div>

                    <div v-if="can('atendimentos.gerenciar')" class="py-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Configurações</div>
                        <Link :href="route('admin.settings.columns.index')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Colunas do Dashboard</Link>
                        <Link :href="route('admin.proposal_templates.index')" class="block py-2 pl-4 text-sm text-slate-600 dark:text-slate-400">Modelos de Proposta</Link>
                    </div>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 pb-4 pt-4 px-4 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center text-white font-bold">{{ user.name.charAt(0) }}</div>
                        <div>
                            <div class="text-base font-medium text-slate-800 dark:text-white">{{ user.name }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <Link :href="route('profile.edit')" class="block text-center py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300">Perfil</Link>
                        <Link :href="route('logout')" method="post" as="button" class="block w-full text-center py-2 border border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-900/20 rounded-lg text-sm font-medium text-rose-600 dark:text-rose-400">Sair</Link>
                    </div>
                </div>
            </div>

            <!-- Top Search and Header Area (Desktop) -->
            <div class="hidden md:flex items-center justify-between px-8 py-4 bg-white/70 dark:bg-[#1E293B]/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 transition-colors duration-300">
                <div class="flex-1 min-w-0" :class="!sidebarOpen ? 'ml-12' : ''">
                    <div v-if="$slots.header" class="max-w-7xl truncate text-lg font-semibold text-slate-800 dark:text-white tracking-tight">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-5 ml-4">
                    <button 
                        @click="toggleTheme" 
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm"
                        :title="theme === 'dark' ? 'Modo Claro' : 'Modo Escuro'"
                    >
                        <svg v-if="theme === 'dark'" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <div class="w-72">
                        <GlobalSearch />
                    </div>
                </div>
            </div>

            <!-- Page Content Scrollable Area -->
            <main class="flex-1 overflow-y-auto w-full custom-scrollbar">
                <div class="p-4 sm:p-6 lg:p-8 mx-auto max-w-screen-2xl w-full flex flex-col min-h-full">
                    <div v-if="$slots.header" class="md:hidden mb-6">
                        <slot name="header" />
                    </div>
                    
                    <slot />
                </div>
            </main>
        </div>

        <!-- Global Components -->
        <Toast />
    </div>
</template>

<style>
/* Custom Scrollbar to match doc-system.v2 style */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
