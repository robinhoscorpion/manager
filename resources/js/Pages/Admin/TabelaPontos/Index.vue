<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    resorts: Array,
});

// Computa a lista plana de todas as colunas de dados de um resort
const getDataColumns = (resort) => resort.accommodationGroups.flatMap(g => g.columns);

// Imprime apenas a tabela
const printTable = () => window.print();
</script>

<template>
    <Head title="Tabela de Pontos" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#f5f0e8] font-sans py-8 px-4 print:bg-white print:py-0 print:px-0">

            <!-- Toolbar (oculto na impressão) -->
            <div class="max-w-[1400px] mx-auto mb-6 flex items-center justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Tabela de Pontos</h1>
                    <p class="text-sm text-slate-500">Itacaré Vacation Club</p>
                </div>
                <button
                    @click="printTable"
                    class="flex items-center gap-2 bg-[#3d5a2e] hover:bg-[#2e4520] text-white px-5 py-2.5 rounded-xl transition-all shadow-md text-sm font-semibold"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir / Exportar PDF
                </button>
            </div>

            <!-- Conteúdo Imprimível -->
            <div class="max-w-[1400px] mx-auto">

                <!-- Cabeçalho da Tabela -->
                <div class="text-center mb-6 print:mb-4">
                    <div class="flex items-center justify-center gap-3 mb-1">
                        <div class="h-px flex-1 bg-[#3d5a2e]/20"></div>
                        <p class="text-xs font-bold tracking-[0.3em] text-[#3d5a2e] uppercase">Itacaré Vacation Club</p>
                        <div class="h-px flex-1 bg-[#3d5a2e]/20"></div>
                    </div>
                    <h1 class="text-4xl font-black text-[#1a2e10] uppercase tracking-tight leading-none print:text-3xl">
                        Tabela de Pontos
                    </h1>
                    <p class="text-sm font-semibold text-slate-500 tracking-widest uppercase mt-1">
                        Por Tipo de Acomodação e Quantidade de Pessoas
                    </p>
                </div>

                <!-- Uma tabela por resort -->
                <div
                    v-for="resort in resorts"
                    :key="resort.id"
                    class="mb-3 overflow-hidden rounded-xl shadow-sm print:rounded-none print:mb-2 print:shadow-none print:break-inside-avoid"
                >
                    <table class="w-full border-collapse text-[11px] bg-white">
                        <!-- ── Cabeçalho ── -->
                        <thead>
                            <!-- Linha 1: Nome do resort + Grupos de acomodação + ANTECEDÊNCIA + PERÍODO -->
                            <tr>
                                <!-- Nome do resort (rowspan 2) -->
                                <th
                                    :class="resort.headerColor"
                                    class="text-white font-black text-[13px] uppercase tracking-wide px-3 py-3 text-left border border-white/20 align-middle"
                                    rowspan="2"
                                    style="min-width: 130px; width: 130px;"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-lg">{{ resort.icon }}</span>
                                        <span class="leading-tight">{{ resort.name }}</span>
                                    </div>
                                </th>

                                <!-- Antecedência (rowspan 2) -->
                                <th
                                    class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider px-2 py-2 text-center border border-slate-200 align-middle"
                                    rowspan="2"
                                    style="min-width: 80px; width: 80px;"
                                >
                                    Antecedência
                                </th>

                                <!-- Grupos de acomodação (colspan dinâmico) -->
                                <th
                                    v-for="group in resort.accommodationGroups"
                                    :key="group.label"
                                    :colspan="group.columns.length"
                                    :class="resort.headerColor"
                                    class="text-white font-bold text-center px-2 py-2 border border-white/20 uppercase tracking-wide"
                                >
                                    {{ group.label }}
                                </th>

                                <!-- Período (rowspan 2) -->
                                <th
                                    class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider px-2 py-2 text-center border border-slate-200 align-middle"
                                    rowspan="2"
                                    style="min-width: 160px;"
                                >
                                    Período
                                </th>
                            </tr>

                            <!-- Linha 2: Sub-colunas PAX -->
                            <tr>
                                <th
                                    v-for="col in getDataColumns(resort)"
                                    :key="col.key"
                                    :class="resort.headerColor"
                                    class="text-white/90 font-semibold text-center px-2 py-1.5 border border-white/20 text-[10px] uppercase tracking-wider"
                                    style="min-width: 72px;"
                                >
                                    {{ col.label }}
                                </th>
                            </tr>
                        </thead>

                        <!-- ── Corpo ── -->
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in resort.rows"
                                :key="rowIndex"
                                :class="rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
                                class="hover:bg-amber-50/40 transition-colors"
                            >
                                <!-- Temporada -->
                                <td class="font-bold text-slate-800 px-3 py-2 border border-slate-200/70 uppercase text-[10.5px] tracking-wide">
                                    {{ row.season }}
                                </td>

                                <!-- Antecedência -->
                                <td class="text-center font-semibold text-slate-600 px-2 py-2 border border-slate-200/70 text-[10px]">
                                    {{ row.days }}
                                </td>


                                <td v-for="col in getDataColumns(resort)" :key="col.key"
                                    class="px-2 py-4 border border-slate-200/70 text-center font-bold text-slate-700 relative group transition-colors hover:bg-slate-50/50"
                                    @dblclick="startEdit(row, col.key)">
                                    
                                    <div v-if="editingCell === row.season_id + '_' + col.key" class="absolute inset-0 flex items-center justify-center p-1 bg-white shadow-lg ring-2 ring-[#3d5a2e] z-10">
                                        <input 
                                            type="number" 
                                            v-model="editValue"
                                            @blur="saveEdit(row, col.key)"
                                            @keyup.enter="saveEdit(row, col.key)"
                                            @keyup.esc="cancelEdit"
                                            class="w-full text-center text-sm font-bold text-slate-800 border-none focus:ring-0 p-0"
                                            autofocus
                                        />
                                    </div>
                                    <div v-else class="flex flex-col items-center gap-1 cursor-pointer" title="Duplo clique para editar">
                                        {{ row[col.key] || '-' }}
                                        <svg class="w-3 h-3 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </div>
                                </td>

                                <!-- Período -->
                                <td class="text-center text-[9.5px] font-semibold text-slate-600 px-2 py-2 border border-slate-200/70 uppercase leading-relaxed tracking-wide whitespace-pre-line">
                                    {{ row.period }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rodapé -->
                <div class="text-center mt-6 print:mt-4">
                    <div class="flex items-center justify-center gap-8">
                        <p class="text-xs font-bold tracking-[0.25em] text-slate-500 uppercase">Ventus Hospitality Share</p>
                        <div class="w-px h-4 bg-slate-300"></div>
                        <p class="text-xs font-bold tracking-[0.25em] text-[#3d5a2e] uppercase">Itacaré Vacation Club</p>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Valores em pontos. Sujeito a alterações sem aviso prévio.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }
    .print\:hidden { display: none !important; }
    table { font-size: 9px !important; }
    th, td { padding: 3px 4px !important; }
}
</style>
