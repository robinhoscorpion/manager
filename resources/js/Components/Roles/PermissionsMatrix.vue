<script setup>
import { computed } from 'vue';

const props = defineProps({
    allPermissions: Object, // Grouped by group
    selectedPermissions: Array,
    isAdmin: Boolean
});

const emit = defineEmits(['update:selectedPermissions']);

const togglePermission = (id) => {
    if (props.isAdmin) return;
    
    const newSelected = [...props.selectedPermissions];
    const index = newSelected.indexOf(id);
    if (index > -1) {
        newSelected.splice(index, 1);
    } else {
        newSelected.push(id);
    }
    emit('update:selectedPermissions', newSelected);
};

const isPermissionSelected = (id) => {
    return props.isAdmin || props.selectedPermissions.includes(id);
};

const formatSubmoduleName = (str) => {
    const map = {
        'boas_vindas': 'Boas-vindas',
        'gestao_contratos': 'Gestão de Contratos',
        'protocolos': 'Protocolos',
        'onboarding': 'Onboarding',
        'pendencias': 'Pendências Documentais',
        'treinamentos': 'Treinamentos',
        'acompanhamentos': 'Acompanhamentos',
        'campanhas': 'Campanhas de Relacionamento',
        'aniversariantes': 'Aniversariantes',
        'agendamentos': 'Agendamentos',
        'atendimentos': 'Atendimentos',
        'recebiveis': 'Recebíveis',
        'controle_vendas': 'Controle de Vendas',
        'usuarios': 'Usuários',
        'funcionarios': 'Funcionários',
        'cargos': 'Cargos e Permissões',
        'colunas': 'Colunas do Dashboard',
        'modelos_proposta': 'Modelos de Proposta',
        'modelos_contrato': 'Modelos de Contrato',
        'produtos': 'Produtos',
        'manutencao': 'Manutenção',
        'formas_pagamento': 'Formas de Pagamento',
        'qualificacao': 'Tipos de Qualificação',
        'cortesias': 'Cortesias',
        'metas': 'Metas da Plataforma',
        'logs': 'Logs do Sistema',
        'dashboard': 'Dashboard Geral'
    };
    
    if (map[str]) return map[str];
    
    // Default formatting: replace _ with space and capitalize
    return str.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const groupedPermissions = computed(() => {
    const result = {};
    for (const group in props.allPermissions) {
        result[group] = {};
        props.allPermissions[group].forEach(permission => {
            const parts = permission.slug.split('.');
            let submoduleKey = parts[0];
            
            // For certain modules, the submodule is the second part
            if (['pos_venda', 'configuracoes'].includes(parts[0]) && parts.length >= 3) {
                submoduleKey = parts[1];
            } else if (parts.length >= 2) {
                submoduleKey = parts[0];
            }
            
            const submoduleName = formatSubmoduleName(submoduleKey);
            
            if (!result[group][submoduleName]) {
                result[group][submoduleName] = [];
            }
            result[group][submoduleName].push(permission);
        });
    }
    return result;
});
</script>

<template>
    <div class="space-y-10">
        <div v-for="(submodules, group) in groupedPermissions" :key="group" class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <!-- Group Header -->
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-xl bg-brand-green/10 border border-brand-green/20 flex items-center justify-center shadow-sm">
                    <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ group }}</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Módulo Principal</p>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Submodules -->
                <div v-for="(permissions, submoduleName) in submodules" :key="submoduleName" class="relative pl-5 sm:pl-7 border-l-2 border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-brand-green ring-4 ring-white dark:ring-slate-900"></div>
                        <h4 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest flex items-center gap-2">
                            {{ submoduleName }}
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[9px] text-slate-400 font-bold">
                                {{ permissions.length }} ações
                            </span>
                        </h4>
                    </div>

                    <!-- Permissions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div 
                            v-for="permission in permissions" 
                            :key="permission.id"
                            @click="togglePermission(permission.id)"
                            class="group relative flex items-center gap-4 p-3.5 rounded-xl border transition-all duration-300"
                            :class="[
                                isPermissionSelected(permission.id) 
                                    ? 'bg-brand-green/10 border-brand-green/30 shadow-sm' 
                                    : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600',
                                isAdmin ? 'cursor-not-allowed opacity-80' : 'cursor-pointer hover:bg-white dark:hover:bg-slate-800'
                            ]"
                        >
                            <!-- Checkbox state display -->
                            <div 
                                class="w-5 h-5 rounded-md border flex items-center justify-center transition-all duration-300 shrink-0"
                                :class="isPermissionSelected(permission.id) ? 'bg-brand-green border-brand-green shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600'"
                            >
                                <svg v-if="isPermissionSelected(permission.id)" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <div class="flex-1">
                                <span class="block text-[11px] font-bold text-slate-900 dark:text-white uppercase tracking-tight group-hover:text-brand-green transition-colors">
                                    {{ permission.name }}
                                </span>
                                <span class="block text-[8px] text-slate-500 font-bold tracking-widest uppercase mt-0.5">
                                    {{ permission.slug }}
                                </span>
                            </div>

                            <!-- Admin override indicator -->
                            <div v-if="isAdmin" class="absolute top-2 right-2">
                                <svg class="w-2.5 h-2.5 text-brand-green opacity-40" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div v-if="isAdmin" class="bg-blue-50 dark:bg-blue-500/5 border border-blue-100 dark:border-blue-500/10 rounded-xl p-5 flex gap-4 mt-8">
            <div class="p-2.5 bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg shrink-0 h-fit">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h5 class="text-[11px] font-bold text-blue-900 dark:text-blue-400 uppercase tracking-widest leading-none">Acesso Full Administrador</h5>
                <p class="text-[10px] text-blue-800 dark:text-blue-300/70 mt-2 leading-relaxed font-medium">
                    As permissões do cargo **Administrador** são protegidas e imutáveis para garantir a segurança operacional do sistema.
                </p>
            </div>
        </div>
    </div>
</template>
