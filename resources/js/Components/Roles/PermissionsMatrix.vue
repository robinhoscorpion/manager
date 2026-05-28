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
</script>

<template>
    <div class="space-y-10">
        <div v-for="(permissions, group) in allPermissions" :key="group" class="relative">
            <!-- Group Header -->
            <div class="flex items-center gap-4 mb-5">
                <h4 class="text-[9px] font-black text-purple-400 uppercase tracking-[0.3em] whitespace-nowrap bg-purple-500/5 px-3 py-1.5 rounded-md border border-purple-500/10">
                    {{ group }}
                </h4>
                <div class="h-px bg-gradient-to-r from-purple-500/20 to-transparent w-full"></div>
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
                            ? 'bg-purple-500/10 border-purple-500/30' 
                            : 'bg-white/5 border-white/5 hover:border-white/10',
                        isAdmin ? 'cursor-not-allowed opacity-80' : 'cursor-pointer hover:bg-white/[0.08]'
                    ]"
                >
                    <!-- Checkbox state display -->
                    <div 
                        class="w-5 h-5 rounded-md border flex items-center justify-center transition-all duration-300 shrink-0"
                        :class="isPermissionSelected(permission.id) ? 'bg-purple-500 border-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.4)]' : 'bg-white/5 border-white/10'"
                    >
                        <svg v-if="isPermissionSelected(permission.id)" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <span class="block text-[11px] font-bold text-white uppercase tracking-tight group-hover:text-purple-400 transition-colors">
                            {{ permission.name }}
                        </span>
                        <span class="block text-[8px] text-gray-600 font-bold tracking-widest uppercase mt-0.5 opacity-60">
                            {{ permission.slug }}
                        </span>
                    </div>

                    <!-- Admin override indicator -->
                    <div v-if="isAdmin" class="absolute top-2 right-2">
                        <svg class="w-2.5 h-2.5 text-purple-400 opacity-30" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div v-if="isAdmin" class="bg-blue-500/5 border border-blue-500/10 rounded-xl p-5 flex gap-4 mt-8">
            <div class="p-2.5 bg-blue-500/10 rounded-lg shrink-0 h-fit">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h5 class="text-[11px] font-black text-white uppercase tracking-widest leading-none">Acesso Full Administrador</h5>
                <p class="text-[10px] text-gray-500 mt-2 leading-relaxed font-medium">
                    As permissões do cargo **Administrador** são protegidas e imutáveis para garantir a segurança operacional do sistema.
                </p>
            </div>
        </div>
    </div>
</template>
