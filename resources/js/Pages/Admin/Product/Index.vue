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
        <div class="py-12 bg-[#020617] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Gestão de Produtos</h1>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-[0.3em] mt-1">Configuração Comercial e Contratual</p>
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="bg-cyan-600 hover:bg-cyan-500 text-white font-black uppercase text-[10px] tracking-widest px-6 py-3 rounded-xl transition-all shadow-lg shadow-cyan-500/20 active:scale-95 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Produto
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 p-1 bg-white/[0.02] border border-white/5 rounded-2xl w-fit">
                    <button 
                        v-for="type in productTypes" 
                        :key="type.id"
                        @click="activeTypeTab = type.id"
                        class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="activeTypeTab === type.id ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-500/20' : 'text-gray-500 hover:text-white'"
                    >
                        {{ type.name }}
                    </button>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="product in filteredProducts" 
                        :key="product.id"
                        class="bg-[#0d1117] border border-white/5 rounded-2xl group hover:border-cyan-500/30 transition-all overflow-hidden flex flex-col shadow-2xl"
                    >
                        <div class="p-6 space-y-4 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2">
                                    <div class="bg-cyan-500/10 border border-cyan-500/20 px-2 py-1 rounded text-[9px] font-black text-cyan-400 uppercase tracking-widest">
                                        {{ product.product_type?.name }}
                                    </div>
                                    <div 
                                        class="px-2 py-1 rounded text-[9px] font-black uppercase tracking-widest border"
                                        :class="product.is_active ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border-red-500/20 text-red-400'"
                                    >
                                        {{ product.is_active ? 'Ativo' : 'Inativo' }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="openEditModal(product)" class="text-gray-600 hover:text-cyan-400 p-1 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button @click="deleteProduct(product.id)" class="text-gray-600 hover:text-red-400 p-1 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-black text-lg uppercase tracking-tighter group-hover:text-cyan-400 transition-colors">{{ product.name }}</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">{{ product.duration }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-[9px] font-black text-gray-600 uppercase tracking-widest">Preço Base / Máximo</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-white font-black">{{ formatCurrency(product.price) }}</span>
                                    <span class="text-[10px] text-gray-500">/ {{ formatCurrency(product.min_price) }}</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/5 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-black/40 rounded-lg p-2 border border-white/5">
                                        <p class="text-[8px] font-black text-gray-600 uppercase tracking-widest mb-1">Taxa Contrato</p>
                                        <p class="text-[10px] text-white font-bold">{{ product.contract_fee > 0 ? formatCurrency(product.contract_fee) : 'Isento' }}</p>
                                    </div>
                                    <div class="bg-black/40 rounded-lg p-2 border border-white/5">
                                        <p class="text-[8px] font-black text-gray-600 uppercase tracking-widest mb-1">Entrada Mín.</p>
                                        <p class="text-[10px] text-white font-bold">{{ product.min_down_payment_percentage }}%</p>
                                    </div>
                                </div>
                                <div v-if="product.proposal_template" class="bg-blue-500/5 rounded-lg p-2 border border-blue-500/10 flex justify-between items-center mt-2">
                                    <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest">Layout Proposta</span>
                                    <span class="text-[10px] text-white font-bold">{{ product.proposal_template.name }}</span>
                                </div>
                                <div v-if="product.contract_template" class="bg-indigo-500/5 rounded-lg p-2 border border-indigo-500/10 flex justify-between items-center mt-1">
                                    <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Modelo Contrato</span>
                                    <span class="text-[10px] text-white font-bold">{{ product.contract_template.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <button 
                        @click="openCreateModal"
                        class="border-2 border-dashed border-white/5 rounded-2xl h-full min-h-[220px] flex flex-col items-center justify-center p-8 group hover:border-cyan-500/30 transition-all hover:bg-white/[0.01]"
                    >
                        <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-cyan-500/10 transition-colors mb-4">
                            <svg class="w-6 h-6 text-gray-600 group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest group-hover:text-gray-300 transition-colors">Novo Registro</p>
                    </button>
                </div>

            </div>
        </div>

        <!-- Compact Edit/Create Modal -->
        <Modal :show="showEditModal" @close="closeModal">
            <div class="bg-[#0d1117] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-white/5 bg-gradient-to-r from-cyan-600/20 to-transparent flex items-center justify-between">
                    <h2 class="text-xl font-black text-white uppercase tracking-tighter">{{ editingProduct ? 'Editar Produto' : 'Novo Produto' }}</h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5 overflow-y-auto custom-scrollbar">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Categoria de Produto</label>
                            <select v-model="form.product_type_id" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                                <option v-for="type in productTypes" :key="type.id" :value="type.id" class="bg-[#0b0f19]">{{ type.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nome Comercial</label>
                            <input v-model="form.name" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Ex: Produto VIP">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Valor Venda (Base)</label>
                                <input :value="maskCurrency(form.price)" @input="onPriceInput($event, 'price')" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Valor Teto (Máx)</label>
                                <input :value="maskCurrency(form.min_price)" @input="onPriceInput($event, 'min_price')" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Duração</label>
                                <input v-model="form.duration" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Ex: 10 Anos">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">{{ quantityLabel }}</label>
                                <input v-model="form.quantity" type="number" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Taxa Contrato (R$)</label>
                                <input :value="maskCurrency(form.contract_fee)" @input="onPriceInput($event, 'contract_fee')" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Entrada Mín. (%)</label>
                                <input v-model="form.min_down_payment_percentage" type="number" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white/[0.02] p-4 rounded-xl border border-white/5">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                <span class="ml-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Produto Ativo na Sala</span>
                            </label>
                        </div>
                    </div>

                    <!-- Contract & Proposal Config Block -->
                    <div class="pt-5 border-t border-white/10 space-y-4">
                        <p class="text-[10px] font-black text-cyan-400 uppercase tracking-widest ml-1">Configurações de Documentos</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Layout da Proposta (HTML)</label>
                                <select v-model="form.proposal_template_id" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                                    <option value="" class="bg-[#0b0f19]">Nenhum (Usar Padrão do Sistema)</option>
                                    <option v-for="temp in proposalTemplates" :key="temp.id" :value="temp.id" class="bg-[#0b0f19]">{{ temp.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Modelo de Contrato (Individual)</label>
                                <select v-model="form.contract_template_id" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                                    <option value="" class="bg-[#0b0f19]">Nenhum (Usar Padrão Global)</option>
                                    <option v-for="temp in contractTemplates" :key="temp.id" :value="temp.id" class="bg-[#0b0f19]">{{ temp.name }}</option>
                                </select>
                                <p class="text-[8px] text-gray-500 mt-1 px-1">* Se não informado, o sistema utilizará o contrato marcado como "Global Padrão".</p>
                            </div>
                            <div class="col-span-2 space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Formato do Número de Contrato</label>
                                <select v-model="form.contract_format" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                                    <option value="prefix_sep_seq">Prefixo-Seq (35-100)</option>
                                    <option value="prefix_seq">PrefixoSeq (35100)</option>
                                    <option value="seq_only">Apenas Sequencial (100)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Prefixo</label>
                                <input v-model="form.contract_prefix" type="text" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50" placeholder="Ex: 35">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Seq. Inicial</label>
                                <input v-model="form.current_sequence" type="number" class="w-full bg-white/[0.03] border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm outline-none focus:border-cyan-500/50">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-white/10">
                        <button type="button" @click="closeModal" class="flex-1 py-3.5 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-[2] py-3.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg shadow-cyan-500/20">{{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}</button>
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
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>
