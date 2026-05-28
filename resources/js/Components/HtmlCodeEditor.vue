<script setup>
import { ref, watch, shallowRef } from 'vue';
import { Codemirror } from 'vue-codemirror';
import { html } from '@codemirror/lang-html';
import { oneDark } from '@codemirror/theme-one-dark';

const props = defineProps({
    modelValue: { type: String, default: '' },
    height: { type: [String, Number], default: 600 }
});

const emit = defineEmits(['update:modelValue']);

const activeTab = ref('code'); // 'code' or 'preview'
const localContent = ref(props.modelValue || '');

const extensions = [html(), oneDark];

// Codemirror View instance can be tracked if needed
const view = shallowRef();

const handleReady = (payload) => {
    view.value = payload.view;
};

watch(() => props.modelValue, (newVal) => {
    if (newVal !== localContent.value) {
        localContent.value = newVal || '';
    }
});

const updateContent = (value) => {
    localContent.value = value;
    emit('update:modelValue', localContent.value);
};

const formatHeight = () => {
    return typeof props.height === 'number' ? `${props.height}px` : props.height;
};
</script>

<template>
    <div class="html-editor-wrapper bg-[#0d1321] border border-white/10 rounded-xl overflow-hidden shadow-2xl flex flex-col">
        <!-- Tabs Header -->
        <div class="flex items-center bg-black/40 border-b border-white/5 px-2 pt-2 gap-1">
            <button 
                type="button" 
                @click="activeTab = 'code'"
                class="px-6 py-3 rounded-t-lg font-black uppercase tracking-widest text-[10px] transition-all flex items-center gap-2"
                :class="activeTab === 'code' ? 'bg-[#282c34] text-cyan-400 shadow-md' : 'text-gray-500 hover:text-gray-300 hover:bg-white/[0.02]'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Código HTML (CodeMirror)
            </button>
            <button 
                type="button" 
                @click="activeTab = 'preview'"
                class="px-6 py-3 rounded-t-lg font-black uppercase tracking-widest text-[10px] transition-all flex items-center gap-2"
                :class="activeTab === 'preview' ? 'bg-[#282c34] text-emerald-400 shadow-md' : 'text-gray-500 hover:text-gray-300 hover:bg-white/[0.02]'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Visualizar Modelo
            </button>
        </div>

        <!-- Editor Content -->
        <div class="flex-1 bg-[#282c34] border-t border-black/20 relative">
            
            <!-- Code Tab (CodeMirror) -->
            <div
                v-show="activeTab === 'code'"
                class="w-full text-[13.5px]"
                :style="{ minHeight: formatHeight(), height: formatHeight() }"
            >
                <codemirror
                    v-model="localContent"
                    placeholder="<!-- Escreva ou cole o seu código HTML puro aqui... -->"
                    :style="{ height: formatHeight() }"
                    :autofocus="false"
                    :indent-with-tab="true"
                    :tab-size="4"
                    :extensions="extensions"
                    @ready="handleReady"
                    @change="updateContent"
                />
            </div>

            <!-- Preview Tab -->
            <div 
                v-show="activeTab === 'preview'" 
                class="w-full overflow-y-auto bg-white/5"
                :style="{ minHeight: formatHeight(), height: formatHeight() }"
            >
                <div 
                    class="html-preview p-8 min-h-full"
                    v-html="localContent || '<div style=\'text-align:center; padding-top:40px; color:#475569; font-style:italic; font-family:sans-serif\'>A visualização aparecerá aqui quando o HTML for inserido...</div>'"
                ></div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* CM Adjustments to match VSCode aesthetic */
.html-editor-wrapper :deep(.cm-editor) {
    outline: none !important;
}
.html-editor-wrapper :deep(.cm-scroller) {
    font-family: 'JetBrains Mono', 'Fira Code', 'Menlo', 'Monaco', 'Courier New', monospace;
    line-height: 1.6;
}
</style>
