<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const updatingStatusId = ref(null);

const props = defineProps({
    employees: Object
});

const can = (permission) => {
    const permissions = usePage().props.auth.permissions || [];
    return permissions.includes(permission);
};

const deleteEmployee = (id) => {
    if (confirm('Tem certeza que deseja remover este funcionário?')) {
        router.delete(route('employees.destroy', id));
    }
};

const formatCurrency = (value) => {
    if (value === 'Indefinido') return value;
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const toggleStatus = (employee, field) => {
    if (updatingStatusId.value) return;

    const originalValue = employee[field];
    updatingStatusId.value = `${employee.id}-${field}`;
    
    // Optimistic flip
    employee[field] = !employee[field];

    router.put(route('employees.update', employee.id), {
        ...employee,
        [field]: employee[field]
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            updatingStatusId.value = null;
        },
        onError: () => {
            employee[field] = originalValue;
        }
    });
};
</script>

<template>
    <Head title="Controle de Funcionários" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    
                    <!-- Top Row: Title & Main Action -->
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Funcionários</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Quadro Geral
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <!-- Search Bar -->
                            <div class="relative group/search flex-1 sm:w-64">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within/search:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input 
                                    type="text" 
                                    placeholder="BUSCAR COLABORADOR..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl pl-11 pr-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all"
                                >
                            </div>

                            <Link 
                                v-if="can('funcionarios.gerenciar')"
                                :href="route('employees.create')" 
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Adicionar Colaborador</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Data Table Wrapper -->
                <div class="ledger-card flex flex-col overflow-hidden mb-12 bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] shadow-sm dark:shadow-none">
                    
                    <!-- Table Header -->
                    <div class="ledger-header-row flex items-center gap-3 px-6 py-3">
                        <div class="flex-1 min-w-[200px] text-left ledger-th !text-black">Funcionário</div>
                        <div class="w-40 text-left ledger-th !text-black">Cargo / Função</div>
                        <div class="w-24 text-center ledger-th !text-black hidden sm:block">Tipo</div>
                        <div class="w-36 text-center ledger-th !text-black hidden md:block">Vínculo Usuário</div>
                        <div class="w-32 text-right ledger-th !text-black hidden lg:block">Salário</div>
                        <div class="w-32 text-center ledger-th !text-black hidden xl:block">Carga Horária</div>
                        <div class="w-24 text-center ledger-th !text-black">Status</div>
                        <div class="w-24 text-right ledger-th !text-black">Ação</div>
                    </div>

                    <!-- Table Body -->
                    <div class="w-full">
                        <template v-if="employees.data && employees.data.length > 0">
                            <div 
                                v-for="employee in employees.data" :key="employee.id"
                                class="ledger-row flex items-center gap-3 px-6 py-3 group relative transition-colors duration-200"
                            >
                                <!-- Accent bar on hover -->
                                <div class="ledger-row-accent"></div>

                                <!-- 1. Funcionário -->
                                <div class="flex-1 min-w-[200px] flex items-center gap-3 truncate">
                                    <div class="w-8 h-8 rounded-lg bg-brand-green/10 border border-brand-green/20 flex items-center justify-center text-brand-green font-bold text-xs shadow-sm group-hover:scale-110 transition-transform shrink-0">
                                        {{ employee.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="ledger-client-name group-hover:text-brand-green transition-colors truncate" :title="employee.name">
                                        {{ employee.name }}
                                    </span>
                                </div>
                                
                                <!-- 2. Cargo / Função -->
                                <div class="w-40 flex flex-col justify-center">
                                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight truncate" :title="employee.position">{{ employee.position || 'Não Definido' }}</span>
                                    <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest mt-0.5 truncate">{{ employee.role || 'Operacional' }}</span>
                                </div>

                                <!-- 3. Tipo -->
                                <div class="w-24 hidden sm:flex justify-center items-center">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest border border-slate-200 dark:border-slate-700">
                                        {{ employee.type || 'CLT' }}
                                    </span>
                                </div>

                                <!-- 4. Vínculo Usuário -->
                                <div class="w-36 hidden md:flex justify-center items-center">
                                    <div v-if="employee.user" class="flex items-center gap-2 group/user">
                                        <div class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></div>
                                        <div class="flex flex-col leading-none">
                                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight truncate max-w-[100px]" :title="employee.user.name">{{ employee.user.name }}</span>
                                            <span class="text-[8px] text-brand-green font-black uppercase tracking-widest mt-1">Conta Ativa</span>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2 opacity-50">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600"></div>
                                        <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Sem Vínculo</span>
                                    </div>
                                </div>

                                <!-- 5. Salário -->
                                <div class="w-32 hidden lg:flex justify-end items-center">
                                    <span class="text-slate-700 dark:text-slate-300 font-black tracking-tighter text-xs">
                                        {{ formatCurrency(employee.salary) }}
                                    </span>
                                </div>

                                <!-- 6. Carga Horária -->
                                <div class="w-32 hidden xl:flex justify-center items-center">
                                    <span class="text-slate-500 dark:text-slate-400 font-medium text-[11px] tracking-wide">
                                        {{ employee.workload || '44 Horas' }}
                                    </span>
                                </div>

                                <!-- 7. Status -->
                                <div class="w-24 flex justify-center items-center">
                                    <div v-if="can('funcionarios.gerenciar')" class="flex justify-center">
                                        <button 
                                            @click="toggleStatus(employee, 'status')"
                                            :disabled="updatingStatusId === `${employee.id}-status`"
                                            class="relative inline-flex h-5 w-10 items-center rounded-full transition-all duration-300 focus:outline-none"
                                            :class="[
                                                employee.status ? 'bg-brand-green shadow-sm shadow-brand-green/30' : 'bg-slate-300 dark:bg-slate-600',
                                                updatingStatusId === `${employee.id}-status` ? 'opacity-50 cursor-wait' : ''
                                            ]"
                                        >
                                            <span 
                                                class="inline-flex h-3 w-3 transform rounded-full bg-white transition-transform duration-300 items-center justify-center shadow-sm"
                                                :class="employee.status ? 'translate-x-6' : 'translate-x-1'"
                                            >
                                                <svg v-if="updatingStatusId === `${employee.id}-status`" class="animate-spin h-2 w-2 text-brand-green" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </span>
                                        </button>
                                    </div>
                                    <div v-else>
                                         <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest whitespace-nowrap"
                                              :class="employee.status ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-slate-50 dark:bg-slate-500/10 text-slate-500 border border-slate-200 dark:border-slate-500/20'"
                                         >
                                             {{ employee.status ? 'Ativo' : 'Inativo' }}
                                         </span>
                                    </div>
                                </div>

                                <!-- 8. Ação -->
                                <div class="w-24 flex items-center justify-end gap-1.5">
                                    <Link 
                                        v-if="can('funcionarios.gerenciar')"
                                        :href="route('employees.edit', employee.id)" 
                                        class="p-1.5 text-slate-400 hover:text-brand-green hover:bg-brand-green/10 rounded-md transition-all"
                                        title="Visualizar/Editar"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button 
                                        v-if="can('funcionarios.gerenciar')"
                                        @click="deleteEmployee(employee.id)"
                                        class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-md transition-all"
                                        title="Remover"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="px-8 py-20 text-center text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-sm">
                            Nenhum colaborador encontrado no momento.
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links && employees.links.length > 3" class="flex justify-center gap-2 mb-12">
                    <template v-for="(link, k) in employees.links" :key="k">
                        <div v-if="link.url === null" 
                            class="px-4 py-2 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700/50 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-50 dark:bg-slate-800/30" 
                            v-html="link.label" 
                        />
                        <Link v-else :href="link.url" 
                            class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border rounded-xl transition-all duration-300"
                            :class="{ 
                                'bg-brand-green text-white border-brand-green shadow-md': link.active, 
                                'text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800': !link.active 
                            }"
                            v-html="link.label" />
                    </template>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
