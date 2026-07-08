<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    products: Array,
    productTypes: Array,
    proposalTemplates: Array,
    contractTemplates: Array,
});

const activeTypeTab = ref(props.productTypes[0]?.id || 1);
const showEditModal = ref(false);
const editingProduct = ref(null);

const filteredProducts = computed(() => {
    return props.products.filter(p => p.product_type_id == activeTypeTab.value);
});

const form = useForm({
    product_type_id: '',
    name: '',
    price: 0,
    min_price: 0,
    duration: '',
    quantity: 0,
    description: '',
    contract_prefix: '',
    contract_format: 'seq_only',
    current_sequence: 1,
    is_active: true,
    min_down_payment_percentage: 0,
    contract_fee: 0,
    proposal_template_id: '',
    contract_template_id: '',
});

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    form.product_type_id = activeTypeTab.value;
    showEditModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.product_type_id = product.product_type_id;
    form.name = product.name;
    form.price = product.price;
    form.min_price = product.min_price;
    form.duration = product.duration;
    form.quantity = product.quantity;
    form.description = product.description;
    form.contract_prefix = product.contract_prefix;
    form.contract_format = product.contract_format;
    form.current_sequence = product.current_sequence;
    form.is_active = !!product.is_active;
    form.min_down_payment_percentage = product.min_down_payment_percentage || 0;
    form.contract_fee = product.contract_fee || 0;
    form.proposal_template_id = product.proposal_template_id || '';
    form.contract_template_id = product.contract_template_id || '';

    showEditModal.value = true;
};

const submit = () => {
    if (editingProduct.value) {
        form.put(route('admin.products.update', editingProduct.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.products.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProduct = (id) => {
    if (confirm('Tem certeza que deseja remover este produto?')) {
        form.delete(route('admin.products.destroy', id));
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};

const maskCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const onPriceInput = (e, field) => {
    let val = e.target.value.replace(/\D/g, '');
    form[field] = parseFloat(val) / 100;
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
};

const quantityLabel = computed(() => {
    if (!form.product_type_id) return 'Quantidade';
    const type = props.productTypes.find(t => t.id === form.product_type_id);
    if (!type) return 'Quantidade';
    const typeName = type.name.toLowerCase();
    if (typeName.includes('ponto')) return 'Qtd./Pontos';
    if (typeName.includes('diária') || typeName.includes('diaria')) return 'Qtd./Diárias';
    if (typeName.includes('cota')) return 'Nº Cotas';
    return 'Quantidade';
});

</script>

<template>
    <Head title="Gestão de Produtos" />

    <AuthenticatedLayout>
        <div class="min-h-screen font-sans bg-[var(--content-bg)] dark:bg-[#0f1219]">
            <div class="w-full sm:px-6 lg:px-8 h-auto pt-8">
                
                <!-- Premium Header & Toolbar -->
                <div class="bg-white dark:bg-slate-900 border border-brand-green/20 dark:border-brand-green/40 rounded-[20px] mb-6 shadow-sm dark:shadow-none flex flex-col relative z-20">
                    <div class="px-6 py-5 border-b border-slate-200/50 dark:border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-center sm:justify-start">
                            <div class="w-12 h-12 rounded-[14px] bg-brand-green/10 border border-brand-green/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight leading-none mb-1">Gestão de Produtos</h2>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-green animate-pulse"></span>
                                    Configuração Comercial e Contratual
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                            <button 
                                @click="openCreateModal"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-brand-green hover:bg-[#485638] text-white px-5 py-2.5 rounded-[12px] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Novo Produto</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 p-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-fit mb-6 shadow-sm">
                    <button 
                        v-for="type in productTypes" 
                        :key="type.id"
                        @click="activeTypeTab = type.id"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all"
                        :class="activeTypeTab === type.id ? 'bg-brand-green text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white'"
                    >
                        {{ type.name }}
                    </button>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="product in filteredProducts" 
                        :key="product.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-brand-green/30 dark:hover:border-brand-green/30 transition-all overflow-hidden flex flex-col shadow-sm hover:shadow-md"
                    >
                        <div class="p-6 space-y-4 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <div class="bg-brand-green/10 border border-brand-green/20 px-2 py-1 rounded-md text-[9px] font-bold text-brand-green uppercase tracking-widest">
                                        {{ product.product_type?.name }}
                                    </div>
                                    <div 
                                        class="px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest border"
                                        :class="product.is_active ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400'"
                                    >
                                        {{ product.is_active ? 'Ativo' : 'Inativo' }}
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="openEditModal(product)" class="text-slate-400 hover:text-brand-green p-1.5 transition-colors rounded-md hover:bg-brand-green/10">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteProduct(product.id)" class="text-slate-400 hover:text-red-500 p-1.5 transition-colors rounded-md hover:bg-red-50 dark:hover:bg-red-500/10">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-slate-900 dark:text-white font-bold text-lg tracking-tight group-hover:text-brand-green transition-colors">{{ product.name }}</h3>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{{ product.duration }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Preço Base / Máximo</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-slate-900 dark:text-white font-bold text-lg">{{ formatCurrency(product.price) }}</span>
                                    <span class="text-xs text-slate-500">/ {{ formatCurrency(product.min_price) }}</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50">
                                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Taxa Contrato</p>
                                        <p class="text-xs text-slate-900 dark:text-white font-bold">{{ product.contract_fee > 0 ? formatCurrency(product.contract_fee) : 'Isento' }}</p>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50">
                                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Entrada Mín.</p>
                                        <p class="text-xs text-slate-900 dark:text-white font-bold">{{ product.min_down_payment_percentage }}%</p>
                                    </div>
                                </div>
                                <div v-if="product.proposal_template" class="bg-blue-50 dark:bg-blue-500/5 rounded-xl p-2.5 border border-blue-100 dark:border-blue-500/10 flex justify-between items-center mt-2">
                                    <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Layout Proposta</span>
                                    <span class="text-[10px] text-slate-700 dark:text-white font-semibold line-clamp-1 text-right ml-2">{{ product.proposal_template.name }}</span>
                                </div>
                                <div v-if="product.contract_template" class="bg-brand-green/5 rounded-xl p-2.5 border border-brand-green/10 flex justify-between items-center mt-1">
                                    <span class="text-[9px] font-bold text-brand-green uppercase tracking-widest">Modelo Contrato</span>
                                    <span class="text-[10px] text-slate-700 dark:text-white font-semibold line-clamp-1 text-right ml-2">{{ product.contract_template.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl h-full min-h-[220px] flex flex-col items-center justify-center p-8 group hover:border-brand-green/30 transition-all hover:bg-slate-50 dark:hover:bg-slate-800/30"
                    >
                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover:bg-brand-green/10 transition-colors mb-4">
                            <svg class="w-6 h-6 text-slate-400 group-hover:text-brand-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest group-hover:text-brand-green transition-colors">Novo Registro</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Compact Edit/Create Modal -->
        <Modal :show="showEditModal" @close="closeModal" maxWidth="3xl">
            <div class="bg-white dark:bg-[#0f1219] rounded-[20px] overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-green/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ editingProduct ? 'Editar Produto' : 'Novo Produto' }}</h2>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6 overflow-y-auto custom-scrollbar bg-slate-50 dark:bg-transparent">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Categoria de Produto</label>
                            <select v-model="form.product_type_id" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                                <option v-for="type in productTypes" :key="type.id" :value="type.id" class="bg-white dark:bg-slate-800">{{ type.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Nome Comercial</label>
                            <input v-model="form.name" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm" placeholder="Ex: Produto VIP">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Valor Venda (Base)</label>
                                <input :value="maskCurrency(form.price)" @input="onPriceInput($event, 'price')" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Valor Teto (Máx)</label>
                                <input :value="maskCurrency(form.min_price)" @input="onPriceInput($event, 'min_price')" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Duração</label>
                                <input v-model="form.duration" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm" placeholder="Ex: 10 Anos">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">{{ quantityLabel }}</label>
                                <input v-model="form.quantity" type="number" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Taxa Contrato (R$)</label>
                                <input :value="maskCurrency(form.contract_fee)" @input="onPriceInput($event, 'contract_fee')" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Entrada Mín. (%)</label>
                                <input v-model="form.min_down_payment_percentage" type="number" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-green"></div>
                                <span class="ml-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Produto Ativo na Sala</span>
                            </label>
                        </div>
                    </div>

                    <!-- Contract & Proposal Config Block -->
                    <div class="pt-5 border-t border-slate-200 dark:border-slate-800 space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <h4 class="text-xs font-bold uppercase text-slate-900 dark:text-white tracking-wider">Configurações de Documentos</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="col-span-1 sm:col-span-2 space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Layout da Proposta (HTML)</label>
                                <select v-model="form.proposal_template_id" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                                    <option value="" class="bg-white dark:bg-slate-800">Nenhum (Usar Padrão do Sistema)</option>
                                    <option v-for="temp in proposalTemplates" :key="temp.id" :value="temp.id" class="bg-white dark:bg-slate-800">{{ temp.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-1 sm:col-span-2 space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Modelo de Contrato (Individual)</label>
                                <select v-model="form.contract_template_id" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                                    <option value="" class="bg-white dark:bg-slate-800">Nenhum (Usar Padrão Global)</option>
                                    <option v-for="temp in contractTemplates" :key="temp.id" :value="temp.id" class="bg-white dark:bg-slate-800">{{ temp.name }}</option>
                                </select>
                                <p class="text-[10px] text-slate-500 mt-1 px-1">* Se não informado, o sistema utilizará o contrato marcado como "Global Padrão".</p>
                            </div>
                            <div class="col-span-1 sm:col-span-2 space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Formato do Número de Contrato</label>
                                <select v-model="form.contract_format" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                                    <option value="prefix_sep_seq">Prefixo-Seq (35-100)</option>
                                    <option value="prefix_seq">PrefixoSeq (35100)</option>
                                    <option value="seq_only">Apenas Sequencial (100)</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Prefixo</label>
                                <input v-model="form.contract_prefix" type="text" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm" placeholder="Ex: 35">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider px-1">Seq. Inicial</label>
                                <input v-model="form.current_sequence" type="number" class="w-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-brand-green/50 focus:ring-1 focus:ring-brand-green/50 transition-all shadow-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="closeModal" class="flex-1 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white rounded-[12px] font-semibold text-sm transition-all shadow-sm">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3 bg-brand-green hover:bg-[#485638] text-white rounded-[12px] font-semibold text-sm transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
