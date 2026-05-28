<script setup>
import { useEditor, EditorContent, BubbleMenu, FloatingMenu } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import ImageResize from 'tiptap-extension-resize-image';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Placeholder from '@tiptap/extension-placeholder';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TaskList } from '@tiptap/extension-task-list';
import { TaskItem } from '@tiptap/extension-task-item';
import { ref, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Comece a escrever aqui... (Dica: digite "/" para comandos rápidos)' },
    uploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
const uploading = ref(false);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
        ImageResize,
        TextAlign.configure({ types: ['heading', 'paragraph', 'table'] }),
        Underline,
        TextStyle,
        Color,
        Placeholder.configure({ placeholder: props.placeholder }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        TaskList,
        TaskItem.configure({
            nested: true,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-invert max-w-none min-h-[400px] outline-none px-8 py-6 text-gray-200 text-sm leading-relaxed',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
        editor.value.commands.setContent(val || '', false);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const fileInput = ref(null);

const triggerUpload = () => {
    fileInput.value?.click();
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (props.uploadUrl) {
        uploading.value = true;
        try {
            const formData = new FormData();
            formData.append('image', file);
            const { data } = await axios.post(props.uploadUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            editor.value?.chain().focus().setImage({ src: data.url }).run();
        } catch (err) {
            console.error('Upload failed:', err);
        } finally {
            uploading.value = false;
        }
    } else {
        const reader = new FileReader();
        reader.onload = (e) => {
            editor.value?.chain().focus().setImage({ src: e.target.result }).run();
        };
        reader.readAsDataURL(file);
    }
    event.target.value = '';
};

const colors = [
    '#ffffff', '#ef4444', '#f97316', '#eab308', '#22c55e',
    '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#94a3b8',
];

// Table helpers
const addTable = () => editor.value.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
</script>

<template>
    <div class="rich-editor-wrapper border border-white/10 rounded-2xl overflow-hidden bg-[#0d1321] transition-all focus-within:border-cyan-500/50 shadow-2xl">
        <!-- Main Toolbar -->
        <div v-if="editor" class="editor-toolbar flex flex-wrap items-center gap-1 p-3 border-b border-white/5 bg-white/[0.03]">
            <!-- History -->
            <div class="flex items-center gap-0.5 pr-2 mr-2 border-r border-white/10">
                <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="toolbar-btn" title="Desfazer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="toolbar-btn" title="Refazer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
                </button>
            </div>

            <!-- Typography Group -->
            <div class="flex items-center gap-0.5 pr-2 mr-2 border-r border-white/10">
                <button type="button" @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive('bold') }"
                    class="toolbar-btn" title="Negrito">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive('italic') }"
                    class="toolbar-btn" title="Itálico">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive('underline') }"
                    class="toolbar-btn" title="Sublinhado">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
                </button>
                
                <!-- Headings Dropdown-like -->
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive('heading', { level: 1 }) }"
                    class="toolbar-btn text-xs font-black" title="H1">H1</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive('heading', { level: 2 }) }"
                    class="toolbar-btn text-xs font-black" title="H2">H2</button>
            </div>

            <!-- Alignment -->
            <div class="flex items-center gap-0.5 pr-2 mr-2 border-r border-white/10">
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive({ textAlign: 'left' }) }"
                    class="toolbar-btn" title="Alinhar Esquerda">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive({ textAlign: 'center' }) }"
                    class="toolbar-btn" title="Centralizar">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
                </button>
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
                    :class="{ 'bg-cyan-600/30 text-cyan-400': editor.isActive({ textAlign: 'right' }) }"
                    class="toolbar-btn" title="Alinhar Direita">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
                </button>
            </div>

            <!-- Tables -->
            <div class="flex items-center gap-0.5 pr-2 mr-2 border-r border-white/10">
                <button type="button" @click="addTable" class="toolbar-btn" title="Inserir Tabela">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM8 20H4v-4h4v4zm0-6H4v-4h4v4zm0-6H4V4h4v4zm6 12h-4v-4h4v4zm0-6h-4v-4h4v4zm0-6h-4V4h4v4zm6 12h-4v-4h4v4zm0-6h-4v-4h4v4zm0-6h-4V4h4v4z"/></svg>
                </button>
                
                <template v-if="editor.isActive('table')">
                    <button type="button" @click="editor.chain().focus().addColumnBefore().run()" class="toolbar-btn" title="Add Coluna Esquerda">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h8V2zm-2 18H5V4h6v16zm10-9h-3V8h-2v3h-3v2h3v3h2v-3h3v-2z"/></svg>
                    </button>
                    <button type="button" @click="editor.chain().focus().addRowBefore().run()" class="toolbar-btn" title="Add Linha Acima">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 13H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2zM4 21v-6h16v6H4zm7-11V7H8V5h3V2h2v3h3v2h-3v3h-2z"/></svg>
                    </button>
                    <button type="button" @click="editor.chain().focus().deleteTable().run()" class="toolbar-btn text-red-500 hover:text-red-400" title="Apagar Tabela">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    </button>
                </template>
            </div>

            <!-- Media -->
            <div class="flex items-center gap-0.5">
                <button type="button" @click="triggerUpload" :disabled="uploading" class="toolbar-btn" title="Inserir Imagem">
                    <svg v-if="!uploading" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </button>
                <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleImageUpload" />
                
                <div class="flex items-center gap-0.5 relative group/color">
                    <button type="button" class="toolbar-btn relative" title="Cor do Texto">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 2L5.5 16h2.25l1.12-3h6.25l1.12 3h2.25L13 2h-2zm-1.38 9L12 4.67 14.38 11H9.62z"/></svg>
                        <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-3 h-0.5 rounded-full bg-cyan-400"></div>
                    </button>
                    <div class="absolute top-full left-0 mt-2 bg-[#1a1f2e] border border-white/10 rounded-xl p-2 shadow-2xl hidden group-hover/color:grid grid-cols-5 gap-1 z-50 w-[140px]">
                        <button v-for="c in colors" :key="c" type="button"
                            @click="editor.chain().focus().setColor(c).run()"
                            class="w-5 h-5 rounded-md border border-white/10 hover:scale-125 transition-transform"
                            :style="{ backgroundColor: c }"></button>
                        <button type="button" @click="editor.chain().focus().unsetColor().run()"
                            class="w-5 h-5 rounded-md border border-white/10 hover:scale-125 transition-transform bg-transparent flex items-center justify-center text-[8px] text-gray-500 font-bold">✕</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bubble Menu (Selection-based) -->
        <BubbleMenu v-if="editor" :editor="editor" :tippy-options="{ duration: 100 }">
            <div class="flex items-center gap-0.5 p-1 px-2 bg-[#1a1f2e] border border-white/10 rounded-xl shadow-2xl">
                <button type="button" @click="editor.chain().focus().toggleBold().run()" class="toolbar-btn-sm" :class="{ 'text-cyan-400': editor.isActive('bold') }">B</button>
                <button type="button" @click="editor.chain().focus().toggleItalic().run()" class="toolbar-btn-sm" :class="{ 'text-cyan-400': editor.isActive('italic') }">I</button>
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()" class="toolbar-btn-sm" :class="{ 'text-cyan-400': editor.isActive('underline') }">U</button>
            </div>
        </BubbleMenu>

        <!-- Floating Menu (Empty line-based) -->
        <FloatingMenu v-if="editor" :editor="editor" :tippy-options="{ duration: 100 }">
            <div class="flex items-center gap-1 p-2 bg-[#1a1f2e] border border-white/10 rounded-xl shadow-2xl">
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" class="floating-btn">H1</button>
                <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" class="floating-btn">H2</button>
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()" class="floating-btn">Lista</button>
                <button type="button" @click="addTable" class="floating-btn">Tabela</button>
            </div>
        </FloatingMenu>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="editor-content" />
    </div>
</template>

<style scoped>
.toolbar-btn {
    @apply w-10 h-10 flex items-center justify-center rounded-xl text-gray-400 hover:text-white hover:bg-white/10 transition-all disabled:opacity-20 disabled:cursor-not-allowed;
}

.toolbar-btn-sm {
    @apply w-7 h-7 flex items-center justify-center rounded-lg text-[10px] font-black text-gray-400 hover:text-white hover:bg-white/10 transition-all;
}

.floating-btn {
    @apply px-3 py-1.5 rounded-lg text-[10px] font-bold text-gray-400 hover:text-white hover:bg-white/10 bg-white/5 transition-all;
}

.editor-content :deep(.tiptap) {
    min-height: 400px;
    outline: none;
    padding: 2rem;
    color: #cbd5e1;
    font-size: 0.9375rem;
    line-height: 1.8;
}

.editor-content :deep(.tiptap p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #475569;
    pointer-events: none;
    height: 0;
    font-style: italic;
}

/* Table Styles */
.editor-content :deep(.tiptap table) {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 1.5rem 0;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.editor-content :deep(.tiptap table td),
.editor-content :deep(.tiptap table th) {
    min-width: 1em;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1rem;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.editor-content :deep(.tiptap table th) {
    font-weight: bold;
    text-align: left;
    background-color: rgba(255, 255, 255, 0.05);
    color: #fff;
}

.editor-content :deep(.tiptap table .selectedCell:after) {
    z-index: 2;
    position: absolute;
    content: "";
    left: 0; right: 0; top: 0; bottom: 0;
    background: rgba(34, 211, 238, 0.1);
    pointer-events: none;
}

.editor-content :deep(.tiptap table .column-resize-handle) {
    position: absolute;
    right: -2px; top: 0; bottom: -2px;
    width: 4px;
    background-color: #06b6d4;
    pointer-events: none;
}

/* Image Resize Styles */
.editor-content :deep(.tiptap img) {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 1.5rem 0;
    border-radius: 1rem;
    cursor: default;
}

.editor-content :deep(.tiptap img.ProseMirror-selectednode) {
    outline: 3px solid #06b6d4;
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
}

/* Task List */
.editor-content :deep(.tiptap ul[data-type="taskList"]) {
    list-style: none;
    padding: 0;
}

.editor-content :deep(.tiptap ul[data-type="taskList"] li) {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.editor-content :deep(.tiptap ul[data-type="taskList"] li label) {
    flex: 0 0 auto;
    margin-right: 0.75rem;
    user-select: none;
}

.editor-content :deep(.tiptap ul[data-type="taskList"] li label input) {
    @apply w-4 h-4 rounded border-white/20 bg-white/5 text-cyan-500 focus:ring-cyan-500/50;
}

/* Typography refinements */
.editor-content :deep(.tiptap h1) { font-size: 2.25rem; font-weight: 900; color: #fff; margin: 2rem 0 1rem; text-transform: uppercase; letter-spacing: -0.05em; }
.editor-content :deep(.tiptap h2) { font-size: 1.75rem; font-weight: 800; color: #f1f5f9; margin: 1.5rem 0 0.75rem; }
.editor-content :deep(.tiptap h3) { font-size: 1.25rem; font-weight: 700; color: #cbd5e1; margin: 1.25rem 0 0.5rem; }
.editor-content :deep(.tiptap blockquote) { border-left: 4px solid #06b6d4; padding-left: 1.5rem; color: #94a3b8; font-style: italic; margin: 1.5rem 0; }
.editor-content :deep(.tiptap hr) { border: none; border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 2.5rem 0; }
</style>
