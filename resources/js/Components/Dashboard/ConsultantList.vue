<script setup>
defineProps({
    consultants: Array
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5">
                    <th class="px-5 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest">Consultor</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest text-center">Vendas</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest text-center">Conversão</th>
                    <th class="px-5 py-3 text-[10px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest text-right">Valor Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="item in consultants" :key="item.name" class="group hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors border-b border-slate-50 dark:border-white/[0.03]">
                    <td class="px-5 py-2.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full border border-slate-200 dark:border-white/5 flex items-center justify-center overflow-hidden group-hover:scale-110 transition-transform bg-slate-100 dark:bg-white/5">
                                <img v-if="item.profile_photo_url" :src="item.profile_photo_url" :alt="item.name" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] font-bold text-slate-900 dark:text-white uppercase">
                                    {{ item.name.substring(0, 2) }}
                                </span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-slate-900 dark:text-white text-xs font-bold">{{ item.name }}</span>
                                <span class="text-[9px] text-slate-500 dark:text-gray-500 font-medium uppercase tracking-wider">Sênior</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-lg bg-cyan-100 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 text-[10px] font-black">{{ item.sales }}</span>
                    </td>
                    <td class="px-5 py-2.5 min-w-[100px]">
                        <div class="flex flex-col gap-1">
                            <div class="flex justify-between text-[9px] font-bold">
                                <span class="text-slate-500 dark:text-gray-400">{{ Math.floor(Math.random() * 20 + 70) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-white/5 h-1 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-cyan-500 to-purple-500 h-full rounded-full" :style="{ width: Math.floor(Math.random() * 20 + 70) + '%' }"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-2.5 text-right">
                        <div class="text-slate-900 dark:text-white text-xs font-black tracking-tight">{{ formatCurrency(item.total) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
