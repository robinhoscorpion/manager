<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    paymentMethods: Array,
});

const showEditModal = ref(false);
const editingMethod = ref(null);

const form = useForm({
    name: '',
    type: 'boleto',
    description: '',
    is_active: true,
});

const types = [
    { label: 'Boleto', value: 'boleto' },
    { label: 'Cartão de Crédito', value: 'credit_card' },
    { label: 'Cartão de Débito', value: 'debit_card' },
    { label: 'PIX', value: 'pix' },
    { label: 'Dinheiro (Espécie)', value: 'cash' },
    { label: 'Cheque', value: 'check' },
    { label: 'Transferência Bancária', value: 'transfer' },
    { label: 'Outros', value: 'other' },
];

const openCreateModal = () => {
    editingMethod.value = null;
    form.reset();
    showEditModal.value = true;
};

const openEditModal = (method) => {
    editingMethod.value = method;
    form.name = method.name;
    form.type = method.type;
    form.description = method.description;
    form.is_active = !!method.is_active;
    showEditModal.value = true;
};

const submit = () => {
    if (editingMethod.value) {
        form.put(route('admin.payment_methods.update', editingMethod.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.payment_methods.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteMethod = (id) => {
    if (confirm('Tem certeza que deseja remover esta forma de pagamento?')) {
        form.delete(route('admin.payment_methods.destroy', id));
    }
};

const closeModal = () => {
    showEditModal.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Formas de Pagamento" />

    <AuthenticatedLayout>
        <div class="p-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter leading-none">Formas de Pagamento</h2>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em] mt-2 flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                        Gestão de Nomenclaturas e Tipos
                    </p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-2.5 rounded-xl font-black uppercase text-[10px] tracking-widest transition-all shadow-xl shadow-emerald-500/20 active:scale-95 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    Cadastrar Nova
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="method in paymentMethods" 
                    :key="method.id"
                    class="bg-[#1a1f2e]/60 border border-white/5 rounded-2xl p-5 hover:border-emerald-500/30 transition-all group"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg v-if="method.type === 'credit_card' || method.type === 'debit_card'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-width="2"/></svg>
                            <svg v-else-if="method.type === 'boleto'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 7h.01M7 12h.01M7 17h.01M10 7h10M10 12h10M10 17h10" stroke-width="3" stroke-linecap="round"/></svg>
                            <svg v-else-if="method.type === 'pix'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2"/></svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditModal(method)" class="p-2 bg-white/5 hover:bg-white/10 rounded-lg text-gray-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2"/></svg>
                            </button>
                            <button @click="deleteMethod(method.id)" class="p-2 bg-red-500/10 hover:bg-red-500/20 rounded-lg text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-black text-white uppercase tracking-tight">{{ method.name }}</h3>
                            <span v-if="!method.is_active" class="px-2 py-0.5 bg-red-500/10 border border-red-500/20 text-red-500 text-[8px] font-black uppercase rounded">Inativo</span>
                        </div>
                        <p class="text-[9px] font-bold text-emerald-400/60 uppercase tracking-widest mb-3">
                            Tipo: {{ types.find(t => t.value === method.type)?.label || method.type }}
                        </p>
                        <p v-if="method.description" class="text-[11px] text-gray-500 leading-relaxed italic">{{ method.description }}</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="paymentMethods.length === 0" class="col-span-full py-20 flex flex-col items-center justify-center text-center bg-[#1a1f2e]/40 rounded-3xl border border-dashed border-white/5">
                    <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" stroke-width="2"/></svg>
                    </div>
                    <h3 class="text-white font-black uppercase tracking-widest text-sm">Nenhuma forma cadastrada</h3>
                    <p class="text-gray-500 text-xs mt-2">Clique em "Cadastrar Nova" para começar.</p>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro/Edição -->
        <Modal :show="showEditModal" @close="closeModal" max-width="lg">
            <div class="bg-[#0d1117] border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-white/5 bg-gradient-to-r from-emerald-500/10 to-transparent">
                    <h3 class="text-lg font-black text-white uppercase tracking-tighter">
                        {{ editingMethod ? 'Editar Forma de Pagamento' : 'Nova Forma de Pagamento' }}
                    </h3>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Nomenclatura (Ex: Boleto Bancário R2)</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder:text-gray-700"
                            placeholder="DIGITE O NOME"
                            required
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Tipo Técnico</label>
                            <select 
                                v-model="form.type" 
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white appearance-none"
                            >
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Status</label>
                            <div class="flex items-center h-[46px] px-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-gray-500 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    <span class="ml-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ form.is_active ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-1">Descrição (Opcional)</label>
                        <textarea 
                            v-model="form.description" 
                            rows="3" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white resize-none"
                            placeholder="OUTRAS INFORMAÇÕES..."
                        ></textarea>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-gray-400 font-black uppercase text-[10px] tracking-widest rounded-xl transition-all"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-[2] py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black uppercase text-[10px] tracking-widest rounded-xl shadow-xl shadow-emerald-500/20 active:scale-95 transition-all"
                        >
                            {{ editingMethod ? 'Salvar Alterações' : 'Cadastrar Forma' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
select option {
    background-color: #0d1117;
    color: white;
}
</style>
