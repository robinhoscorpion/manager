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


        <div class="py-12">
            <div class="max-w-Full mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Premium Header Aligned with Table -->
                <div class="relative bg-white/[0.02] backdrop-blur-3xl border border-white/5 px-6 py-4 rounded-xl mb-6 shadow-2xl group transition-all duration-500 z-20">
                    <!-- Decorative background glow container (Nested to allow dropdowns to escape) -->
                    <div class="absolute inset-0 overflow-hidden rounded-xl pointer-events-none">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px] group-hover:bg-indigo-500/15 transition-all duration-700"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500/10 rounded-full blur-[100px] group-hover:bg-purple-500/15 transition-all duration-700"></div>
                    </div>

                    <div class="relative flex flex-col lg:flex-row items-center lg:items-center justify-between gap-4 sm:gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-3 shrink-0 w-full lg:w-auto justify-center lg:justify-start">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-white/10 flex items-center justify-center shadow-lg">
                                <svg class="w-4.5 h-4.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-center lg:text-left">
                                <h2 class="text-lg font-black text-white uppercase tracking-tighter leading-none">Funcionários</h2>
                                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-1 flex items-center justify-center lg:justify-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Quadro Geral
                                </p>
                            </div>
                        </div>

                        <!-- Actions & Search Hub -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <!-- Search Bar -->
                            <div class="relative group/search flex-1 sm:w-64">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 group-focus-within/search:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input 
                                    type="text" 
                                    placeholder="BUSCAR COLABORADOR..." 
                                    class="w-full bg-white/[0.03] border border-white/10 rounded-xl pl-11 pr-4 py-2.5 text-[9px] font-bold text-white uppercase tracking-widest placeholder:text-gray-600 focus:outline-none focus:border-indigo-500/40 focus:bg-white/[0.05] transition-all"
                                >
                            </div>

                            <!-- Deluxe Separator -->
                            <div class="hidden lg:block w-[1px] h-6 bg-white/10 mx-1"></div>

                            <!-- Add Button -->
                            <Link 
                                v-if="can('funcionarios.gerenciar')"
                                :href="route('employees.create')" 
                                class="group relative px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl font-black uppercase text-[9px] tracking-[0.2em] transition-all duration-300 shadow-xl shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-95 flex items-center justify-center gap-3 shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Adicionar Colaborador
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/[0.03] border-b border-white/5">
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">Funcionário</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">Cargo / Função</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden sm:table-cell">Tipo</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden md:table-cell">Vínculo Usuário</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden lg:table-cell">Salário</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] hidden xl:table-cell">Carga Horária</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
                                    <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="employee in employees.data" :key="employee.id" class="group hover:bg-white/[0.02] transition-all duration-200">
                                    <!-- 1. Funcionário -->
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border border-white/10 flex items-center justify-center text-indigo-400 font-bold text-xs shadow-lg group-hover:scale-110 transition-transform">
                                                {{ employee.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-white font-bold tracking-tight uppercase text-xs group-hover:text-indigo-400 transition-colors line-clamp-1">{{ employee.name }}</span>
                                        </div>
                                    </td>
                                    
                                    <!-- 2. Cargo / Função -->
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col">
                                            <span class="text-white font-bold text-[11px] uppercase tracking-tight">{{ employee.position || 'Não Definido' }}</span>
                                            <span class="text-[8px] text-gray-500 font-black uppercase tracking-widest mt-0.5">{{ employee.role || 'Operacional' }}</span>
                                        </div>
                                    </td>

                                    <!-- 3. Tipo -->
                                    <td class="px-5 py-3 hidden sm:table-cell">
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/20 text-[9px] font-black text-indigo-400 uppercase tracking-widest whitespace-nowrap">{{ employee.type || 'CLT' }}</span>
                                    </td>

                                    <!-- 4. Vínculo Usuário -->
                                    <td class="px-5 py-3 hidden md:table-cell">
                                        <div v-if="employee.user" class="flex items-center gap-2 group/user hover:opacity-100 transition-opacity">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                                            <div class="flex flex-col leading-none">
                                                <span class="text-white font-bold text-[10px] uppercase tracking-tight">{{ employee.user.name }}</span>
                                                <span class="text-[8px] text-blue-400 font-black uppercase tracking-widest mt-1">Conta Ativa</span>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-2 opacity-30">
                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-600"></div>
                                            <span class="text-[9px] text-gray-500 font-black uppercase tracking-widest">Sem Vínculo</span>
                                        </div>
                                    </td>

                                    <!-- 5. Salário -->
                                    <td class="px-5 py-3 hidden lg:table-cell">
                                        <span class="text-white font-black tracking-tighter text-xs">{{ formatCurrency(employee.salary) }}</span>
                                    </td>

                                    <!-- 6. Carga Horária -->
                                    <td class="px-5 py-3 text-gray-400 font-medium text-xs hidden xl:table-cell">
                                        {{ employee.workload || '44 Horas' }}
                                    </td>

                                    <!-- 7. Status -->
                                    <td class="px-5 py-3 text-center">
                                        <div v-if="can('funcionarios.gerenciar')" class="flex justify-center">
                                            <button 
                                                @click="toggleStatus(employee, 'status')"
                                                :disabled="updatingStatusId === `${employee.id}-status`"
                                                class="relative inline-flex h-5 w-10 items-center rounded-full transition-all duration-300 focus:outline-none"
                                                :class="[
                                                    employee.status ? 'bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.3)]' : 'bg-gray-700',
                                                    updatingStatusId === `${employee.id}-status` ? 'opacity-50 cursor-wait' : ''
                                                ]"
                                            >
                                                <span 
                                                    class="inline-flex h-3 w-3 transform rounded-full bg-white transition-transform duration-300 items-center justify-center"
                                                    :class="employee.status ? 'translate-x-6' : 'translate-x-1'"
                                                >
                                                    <svg v-if="updatingStatusId === `${employee.id}-status`" class="animate-spin h-2 w-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- 8. Ação -->
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Link 
                                                v-if="can('funcionarios.gerenciar')"
                                                :href="route('employees.edit', employee.id)" 
                                                class="p-1.5 text-gray-500 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-md transition-all"
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
                                                class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-md transition-all"
                                                title="Remover"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="employees.data.length === 0">
                                    <td colspan="8" class="px-8 py-20 text-center text-gray-500 font-bold uppercase tracking-widest text-sm">
                                        Nenhum colaborador encontrado no momento.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links.last_page > 1" class="mt-12 flex justify-center gap-3">
                    <template v-for="(link, k) in employees.links" :key="k">
                        <div v-if="link.url === null" 
                            class="px-6 py-3 text-gray-600 border border-white/5 rounded-2xl text-[10px] font-black uppercase tracking-widest" 
                            v-html="link.label" 
                        />
                        <Link v-else :href="link.url" 
                            class="px-6 py-3 text-[10px] font-black uppercase tracking-widest border border-white/5 rounded-2xl transition-all duration-300"
                            :class="{ 'bg-indigo-600 text-white border-indigo-500 shadow-xl shadow-indigo-500/20': link.active, 'text-gray-400 hover:bg-white/5 hover:text-white': !link.active }"
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
