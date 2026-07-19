<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    },
    imageUrl: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const availableTags = [
    '[NOME_TITULAR]', '[DATA_NASCIMENTO]', '[CPF]', '[EMAIL]', '[CELULAR]',
    '[NOME_CONJUGE]', '[ID_ATENDIMENTO]', '[DATA]', '[LOCAL]', '[QR_CODE]'
];

const mockData = {
    '[NOME_TITULAR]': 'João Carlos da Silva',
    '[DATA_NASCIMENTO]': '15/05/1985',
    '[CPF]': '123.456.789-00',
    '[EMAIL]': 'joao.silva@exemplo.com',
    '[CELULAR]': '(11) 98765-4321',
    '[NOME_CONJUGE]': 'Maria Oliveira da Silva',
    '[ID_ATENDIMENTO]': '04129',
    '[DATA]': '18/07/2026',
    '[LOCAL]': 'Resort Principal (Mesa 05)',
    '[CEP]': '01001-000',
    '[RUA]': 'Praça da Sé Principal',
    '[NUMERO]': '123',
    '[QR_CODE]': '[QR_CODE]'
};

const elements = ref([]);

// Sync from props
onMounted(() => {
    if (props.modelValue && props.modelValue.elements) {
        elements.value = props.modelValue.elements;
    }
});

watch(elements, (newVal) => {
    emit('update:modelValue', { elements: newVal });
}, { deep: true });

const addTag = (tag) => {
    elements.value.push({
        id: Date.now(),
        tag: tag,
        isStatic: false,
        x: 50, // Stored as percentage
        y: 50, // Stored as percentage
        fontSize: 14,
        color: '#000000',
        bold: false,
        align: 'left'
    });
};

const addStaticText = () => {
    elements.value.push({
        id: Date.now(),
        content: 'Novo Texto',
        isStatic: true,
        x: 50, // Stored as percentage
        y: 50, // Stored as percentage
        fontSize: 14,
        color: '#000000',
        bold: false,
        align: 'left'
    });
};

const removeElement = (index) => {
    elements.value.splice(index, 1);
};

// Simple drag logic
const draggingElement = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

const startDrag = (event, index) => {
    draggingElement.value = index;
    const el = elements.value[index];
    
    // Get mouse position relative to the element's top-left corner
    const rect = event.target.getBoundingClientRect();
    dragOffset.value = {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top
    };
    
    document.addEventListener('mousemove', handleDrag);
    document.addEventListener('mouseup', stopDrag);
};

const handleDrag = (event) => {
    if (draggingElement.value === null) return;
    
    const container = document.getElementById('image-template-container');
    const containerRect = container.getBoundingClientRect();
    
    let newX = event.clientX - containerRect.left - dragOffset.value.x;
    let newY = event.clientY - containerRect.top - dragOffset.value.y;
    
    // Constrain to container boundaries
    newX = Math.max(0, Math.min(newX, containerRect.width - 20));
    newY = Math.max(0, Math.min(newY, containerRect.height - 20));
    
    // Convert to percentages relative to the image container
    elements.value[draggingElement.value].x = (newX / containerRect.width) * 100;
    elements.value[draggingElement.value].y = (newY / containerRect.height) * 100;
};

const stopDrag = () => {
    draggingElement.value = null;
    document.removeEventListener('mousemove', handleDrag);
    document.removeEventListener('mouseup', stopDrag);
};

</script>

<template>
    <div class="flex flex-col gap-4">
        
        <div class="flex flex-wrap items-center gap-2 mb-2 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <button 
                @click.prevent="addStaticText"
                class="px-3 py-1.5 text-[10px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1 shadow-sm uppercase tracking-widest mr-2"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Adicionar Texto Livre
            </button>
            <div class="h-6 w-px bg-slate-300 dark:bg-slate-600 mr-2"></div>
            <button 
                v-for="tag in availableTags" 
                :key="tag"
                @click.prevent="addTag(tag)"
                class="px-2.5 py-1.5 text-[10px] font-bold text-white bg-brand-green hover:bg-[#485638] rounded-lg transition-colors flex items-center gap-1 shadow-sm uppercase tracking-widest"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ tag }}
            </button>
        </div>

        <div class="flex gap-4">
            <!-- Canvas Area -->
            <div class="flex-grow flex items-start justify-center bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xl overflow-auto p-4" style="min-height: 400px;">
                
                <!-- Exact Image Container (Tightly wraps the image) -->
                <div 
                    id="image-template-container"
                    class="relative inline-block shadow-sm"
                >
                    <img v-if="imageUrl" :src="imageUrl" class="max-w-full h-auto block select-none pointer-events-none" />
                    <div v-else class="w-[400px] h-[300px] flex items-center justify-center text-slate-400 font-bold uppercase tracking-widest text-xs border-2 border-dashed border-slate-300 dark:border-slate-600">
                        Nenhuma imagem carregada
                    </div>

                    <!-- Draggable Elements -->
                    <div 
                        v-for="(el, index) in elements" 
                    :key="el.id"
                    @mousedown.stop="startDrag($event, index)"
                    class="absolute cursor-move border border-dashed border-brand-green/50 hover:border-solid hover:border-brand-green whitespace-nowrap select-none group flex items-center gap-2"
                    :style="{ 
                        left: el.x + '%', 
                        top: el.y + '%',
                        fontSize: el.fontSize + 'px',
                        color: el.color,
                        fontWeight: el.bold ? 'bold' : 'normal',
                        textAlign: el.align || 'left',
                        lineHeight: 1.2
                    }"
                >
                    <template v-if="el.isStatic">
                        <span class="bg-white/50 backdrop-blur-sm px-1 rounded-sm border border-transparent hover:border-blue-300 block" style="white-space: pre-wrap;">{{ el.content || 'Texto Vazio' }}</span>
                    </template>
                    <template v-else-if="el.tag === '[QR_CODE]'">
                        <div class="bg-white/80 p-1 rounded inline-block">
                            <div class="w-20 h-20 bg-slate-200/80 flex items-center justify-center text-[10px] text-slate-500 font-bold uppercase">QR Code</div>
                        </div>
                    </template>
                    <template v-else>
                        <span class="bg-white/50 backdrop-blur-sm px-1 rounded-sm">{{ mockData[el.tag] || el.tag }}</span>
                    </template>
                    
                    <!-- Delete button that appears on hover -->
                    <button @click.stop="removeElement(index)" class="opacity-0 group-hover:opacity-100 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center absolute -top-2.5 -right-2.5 shadow-md z-10 transition-opacity">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            </div>

            <!-- Side Panel for Element Properties -->
            <div class="w-64 flex-shrink-0 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 overflow-y-auto max-h-[600px]">
                <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Campos Adicionados</h4>
                
                <div v-if="elements.length === 0" class="text-[10px] text-slate-500 text-center py-4 uppercase tracking-widest font-bold">
                    Nenhum campo adicionado
                </div>
                
                <div v-for="(el, index) in elements" :key="el.id" class="mb-4 bg-white dark:bg-slate-900 p-3 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-brand-green uppercase tracking-widest">{{ el.isStatic ? 'Texto Livre' : el.tag }}</span>
                    </div>
                    
                    <div class="space-y-2">
                        <div v-if="el.isStatic" class="mb-2">
                            <label class="text-[9px] font-bold text-slate-500 uppercase">Texto</label>
                            <textarea v-model="el.content" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 text-xs text-slate-900 dark:text-white resize-y"></textarea>
                        </div>
                        <div v-if="el.isStatic" class="mb-2">
                            <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Alinhamento</label>
                            <div class="flex gap-1 bg-slate-50 dark:bg-slate-800 p-1 rounded-md border border-slate-200 dark:border-slate-700">
                                <button @click="el.align = 'left'" :class="['flex-1 py-1 text-xs rounded transition-colors', el.align === 'left' ? 'bg-white dark:bg-slate-600 shadow-sm text-brand-green font-bold' : 'text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700']">Esq</button>
                                <button @click="el.align = 'center'" :class="['flex-1 py-1 text-xs rounded transition-colors', el.align === 'center' ? 'bg-white dark:bg-slate-600 shadow-sm text-brand-green font-bold' : 'text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700']">Centro</button>
                                <button @click="el.align = 'right'" :class="['flex-1 py-1 text-xs rounded transition-colors', el.align === 'right' ? 'bg-white dark:bg-slate-600 shadow-sm text-brand-green font-bold' : 'text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700']">Dir</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 uppercase">Tamanho da Fonte</label>
                            <input v-model.number="el.fontSize" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 text-xs" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-[9px] font-bold text-slate-500 uppercase flex-1">Cor</label>
                            <input v-model="el.color" type="color" class="w-8 h-8 rounded cursor-pointer" />
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer mt-1">
                            <input type="checkbox" v-model="el.bold" class="rounded text-brand-green focus:ring-brand-green" />
                            <span class="text-[9px] font-bold text-slate-700 dark:text-slate-300 uppercase">Negrito</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
