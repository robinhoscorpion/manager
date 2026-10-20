<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    // { role: 'any' | 'opc' | 'liner' | 'closer' | 'mkt', userId: number | null }
    modelValue: {
        type: Object,
        default: () => ({ role: 'any', userId: null })
    },
    people: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const roles = [
    { label: 'Qualquer função', value: 'any' },
    { label: 'OPC', value: 'opc' },
    { label: 'Liner', value: 'liner' },
    { label: 'Closer', value: 'closer' },
    { label: 'MKT', value: 'mkt' },
];

const isOpen = ref(false);
const search = ref('');

// Funcionários da função escolhida (pessoa com vários cargos aparece em todas as funções dela)
const peopleForRole = computed(() => props.modelValue.role === 'any'
    ? props.people
    : props.people.filter(p => p.functions?.includes(props.modelValue.role)));

const filteredPeople = computed(() => {
    const term = search.value.trim().toLowerCase();
    return term ? peopleForRole.value.filter(p => p.name.toLowerCase().includes(term)) : peopleForRole.value;
});

const functionLabel = (fn) => roles.find(r => r.value === fn)?.label ?? fn;

const selectedLabel = computed(() => {
    const person = props.people.find(p => p.id === props.modelValue.userId);
    if (!person) return 'Toda a Equipe';
    const role = roles.find(r => r.value === props.modelValue.role);
    return props.modelValue.role === 'any' ? person.name : `${role.label} · ${person.name}`;
});

const setRole = (role) => {
    // Se o funcionário escolhido não exerce a nova função, limpa a seleção
    const person = props.people.find(p => p.id === props.modelValue.userId);
    const keepUser = person && (role === 'any' || person.functions?.includes(role));
    emit('update:modelValue', { role, userId: keepUser ? props.modelValue.userId : null });
};

const selectPerson = (userId) => {
    emit('update:modelValue', { ...props.modelValue, userId });
    isOpen.value = false;
    search.value = '';
};

const clear = () => {
    emit('update:modelValue', { role: 'any', userId: null });
    isOpen.value = false;
    search.value = '';
};

const pickerContainer = ref(null);
const handleClickOutside = (event) => {
    if (pickerContainer.value && !pickerContainer.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="relative inline-block text-left" ref="pickerContainer">
        <button
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between gap-2 bg-white dark:bg-[#1a1f2e] px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors shadow-sm"
        >
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ selectedLabel }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 shrink-0 ml-1 transition-transform" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            v-if="isOpen"
            class="absolute top-full right-0 mt-2 w-72 bg-white rounded-xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] z-[100] border border-gray-100 overflow-hidden animate-slide-up p-3"
        >
            <!-- Função no atendimento -->
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1 mb-1.5">Função no atendimento</div>
            <div class="flex flex-wrap gap-1.5 mb-3">
                <button
                    v-for="role in roles"
                    :key="role.value"
                    @click="setRole(role.value)"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all"
                    :class="modelValue.role === role.value ? 'bg-[#0095da] text-white shadow shadow-blue-500/20' : 'bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800'"
                >
                    {{ role.label }}
                </button>
            </div>

            <!-- Funcionário -->
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1 mb-1.5">Funcionário</div>
            <input
                v-model="search"
                type="text"
                placeholder="Pesquisar..."
                class="w-full mb-2 px-3 py-2 text-xs rounded-lg border border-gray-200 focus:border-[#0095da] focus:ring-1 focus:ring-[#0095da] text-gray-800"
            >
            <div class="flex flex-col gap-1 max-h-60 overflow-y-auto">
                <button
                    v-for="person in filteredPeople"
                    :key="person.id"
                    @click="selectPerson(person.id)"
                    class="flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg transition-all"
                    :class="modelValue.userId === person.id ? 'bg-[#0095da] text-white' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-800'"
                >
                    <span class="text-xs font-bold truncate">{{ person.name }}</span>
                    <span 
                        v-if="person.functions?.length > 1"
                        class="text-[9px] font-bold uppercase tracking-wider shrink-0"
                        :class="modelValue.userId === person.id ? 'text-white/70' : 'text-gray-400'"
                    >{{ person.functions.map(functionLabel).join(' · ') }}</span>
                    <svg v-if="modelValue.userId === person.id" class="w-4 h-4 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                <div v-if="!filteredPeople.length" class="text-xs text-gray-400 text-center py-3">Nenhum funcionário encontrado</div>
            </div>

            <button
                v-if="modelValue.userId"
                @click="clear"
                class="w-full mt-3 px-3 py-2 rounded-lg text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 transition-all"
            >
                Limpar filtro de equipe
            </button>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
