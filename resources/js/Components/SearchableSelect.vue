<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Pesquisar...'
    },
    label: String,
    error: String,
    disabled: {
        type: Boolean,
        default: false
    },
    multiple: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const container = ref(null);

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    const s = search.value.toLowerCase();
    return props.options.filter(opt => 
        opt.label.toLowerCase().includes(s) || 
        opt.value.toString().toLowerCase().includes(s)
    );
});

const selectedLabel = computed(() => {
    if (props.multiple) {
        if (!Array.isArray(props.modelValue) || props.modelValue.length === 0) return '';
        if (props.modelValue.length === 1) {
            const selected = props.options.find(opt => opt.value === props.modelValue[0]);
            return selected ? selected.label : '';
        }
        return `${props.modelValue.length} selecionados`;
    }
    const selected = props.options.find(opt => opt.value === props.modelValue);
    return selected ? selected.label : '';
});

const isSelected = (value) => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.includes(value);
    }
    return props.modelValue === value;
};

const selectOption = (option) => {
    if (props.multiple) {
        let newValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const index = newValue.indexOf(option.value);
        if (index > -1) {
            newValue.splice(index, 1);
        } else {
            newValue.push(option.value);
        }
        emit('update:modelValue', newValue);
    } else {
        emit('update:modelValue', option.value);
        isOpen.value = false;
        search.value = '';
    }
};

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative w-full" ref="container">
        <label v-if="label" class="text-[9px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">
            {{ label }}
        </label>
        
        <div 
            @click="!disabled && (isOpen = !isOpen)"
            class="w-full bg-white dark:bg-white/[0.03] border rounded-xl px-4 h-[38px] text-slate-900 dark:text-white text-[13px] flex items-center justify-between hover:bg-slate-50 dark:hover:bg-white/[0.06] transition-all shadow-sm dark:shadow-none"
            :class="[
                error ? 'border-red-500/50 hover:border-red-500/80 shadow-[0_0_10px_rgba(239,68,68,0.1)]' : 'border-slate-200 dark:border-white/10',
                isOpen && !error ? 'border-indigo-500/40' : '',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]"
        >
            <span class="truncate leading-none" :class="{ 'text-slate-400 dark:text-gray-500': !modelValue }">
                {{ selectedLabel || placeholder }}
            </span>
            <svg 
                class="w-4 h-4 text-gray-500 transition-transform duration-300 shrink-0"
                :class="{ 'rotate-180': isOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div 
            v-if="isOpen"
            class="absolute z-[100] mt-2 w-full bg-white dark:bg-[#1a1f2e] border border-slate-200 dark:border-white/10 rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200"
        >
            <div class="p-2 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/[0.02]">
                <input 
                    v-model="search"
                    type="text"
                    autofocus
                    class="w-full bg-white dark:bg-black/20 border border-slate-200 dark:border-white/5 rounded-lg px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500/30 placeholder:text-slate-400 dark:placeholder:text-gray-600 transition-all font-medium"
                    placeholder="Filtrar..."
                    @click.stop
                >
            </div>
            
            <div class="max-h-60 overflow-y-auto custom-scrollbar">
                <div 
                    v-for="opt in filteredOptions" 
                    :key="opt.value"
                    @click.stop="selectOption(opt)"
                    class="px-4 py-2 text-[12px] text-slate-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition-colors flex items-center justify-between"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-500/5 text-indigo-600 dark:text-indigo-400 font-bold': isSelected(opt.value) }"
                >
                    <span>{{ opt.label }}</span>
                    <svg v-if="isSelected(opt.value)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-[10px] text-gray-600 uppercase font-bold text-center italic">
                    Nenhum resultado encontrado
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-[10px] text-red-500 font-bold uppercase tracking-widest px-1 animate-in fade-in slide-in-from-top-1 duration-200">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>
