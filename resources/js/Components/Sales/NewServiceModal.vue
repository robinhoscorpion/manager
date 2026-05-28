<script setup>
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ProposalFormModal from '@/Components/Sales/ProposalFormModal.vue';
import { professions } from '@/Constants/Professions';
import { states } from '@/Constants/States';
import { nationalities } from '@/Constants/Nationalities';
import { SERVICE_STATUS } from '@/Constants/ServiceStatus';

const props = defineProps({
    show: Boolean,
    availableAvatars: Array,
    qualifications: Array,
    complimentaryItems: Array,
    initialData: Object,
});

const emit = defineEmits(['close', 'create']);

const statuses = Object.values(SERVICE_STATUS).map(s => ({
    label: s.label,
    value: s.value,
    color: s.color
}));

const errors = computed(() => usePage().props.errors);

const isEdit = computed(() => !!props.initialData);
const isReadOnly = ref(true);
const showProposalModal = ref(false);

const form = ref({
    date: new Date().toLocaleDateString('pt-BR'),
    time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) + 'h',
    clients: '',
    local: 'hotel',
    opc_id: null,
    qualification: 'Q',
    status: 'MESA',
    
    // Novas informações do Titular
    isEstrangeiro: false,
    nome: '',
    cpf: '',
    rg: '',
    nacionalidade: 'Brasileira',
    dataNascimento: '',
    idade: '',
    profissao: '',
    estadoCivil: 'solteiro',
    celular1: '',
    celular2: '',
    email: '',
    
    // Cônjuge
    temConjuge: false,
    nomeConjuge: '',
    dataNascimentoConjuge: '',
    idadeConjuge: '',
    profissaoConjuge: '',
    
    // Família e Relação
    quantidadeFilhos: 0,
    tempoJuntos: '',
    rendaFamiliar: '',
    
    // Endereço
    cep: '',
    rua: '',
    bairro: '',
    numero: '',
    cidade: '',
    estado: '',
    pais: 'Brasil',
    complemento: '',
    pontoReferencia: '',
    cortesia: [],
    observacoes: '',
});

// Watch para carregar dados iniciais (Edição/Visualização)
watch(() => props.initialData, (newVal) => {
    isReadOnly.value = !!newVal;
    if (newVal) {
        // Mapeamento De -> Para (Considerando relações)
        form.value.date = newVal.date;
        form.value.time = newVal.time;
        form.value.local = newVal.local;
        form.value.status = newVal.status;
        form.value.opc_id = newVal.opc_id;
        form.value.qualification = newVal.qualification;
        form.value.cortesia = Array.isArray(newVal.cortesia) ? newVal.cortesia : (newVal.cortesia ? [newVal.cortesia] : []);
        form.value.observacoes = newVal.observacoes;
        
        // Dados do Cliente (Relação)
        if (newVal.client) {
            form.value.isEstrangeiro = !!(newVal.client.nacionalidade && newVal.client.nacionalidade !== 'Brasileira');
            form.value.nome = newVal.client.nome;
            form.value.cpf = newVal.client.cpf;
            form.value.rg = newVal.client.rg;
            form.value.nacionalidade = newVal.client.nacionalidade || 'Brasileira';
            form.value.dataNascimento = newVal.client.data_nascimento;
            form.value.idade = newVal.client.idade;
            form.value.profissao = newVal.client.profissao;
            form.value.estadoCivil = newVal.client.estado_civil;
            form.value.celular1 = newVal.client.celular1;
            form.value.celular2 = newVal.client.celular2;
            form.value.email = newVal.client.email;
            
            // Endereço (Relação do Cliente)
            if (newVal.client.address) {
                form.value.cep = newVal.client.address.cep;
                form.value.rua = newVal.client.address.rua;
                form.value.bairro = newVal.client.address.bairro;
                form.value.numero = newVal.client.address.numero;
                form.value.cidade = newVal.client.address.cidade;
                form.value.estado = newVal.client.address.estado;
                form.value.complemento = newVal.client.address.complemento;
                form.value.pontoReferencia = newVal.client.address.ponto_referencia;
            }
        }
        
        // Dados específicos do atendimento (Cônjuge/Família)
        form.value.temConjuge = !!newVal.tem_conjuge;
        form.value.nomeConjuge = newVal.nome_conjuge;
        form.value.dataNascimentoConjuge = newVal.data_nascimento_conjuge;
        form.value.idadeConjuge = newVal.idade_conjuge;
        form.value.profissaoConjuge = newVal.profissao_conjuge;
        form.value.quantidadeFilhos = newVal.quantidade_filhos;
        form.value.tempoJuntos = newVal.tempo_juntos;
        form.value.rendaFamiliar = newVal.renda_familiar;
    } else {
        // Reset para novo atendimento
        Object.assign(form.value, {
            date: new Date().toLocaleDateString('pt-BR'),
            time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) + 'h',
            isEstrangeiro: false,
            nome: '', cpf: '', rg: '', nacionalidade: 'Brasileira', dataNascimento: '', idade: '', profissao: '', 
            estadoCivil: 'solteiro', celular1: '', celular2: '', email: '',
            temConjuge: false, nomeConjuge: '', dataNascimentoConjuge: '', 
            idadeConjuge: '', profissaoConjuge: '',
            quantidadeFilhos: 0, tempoJuntos: '', rendaFamiliar: '',
            cep: '', rua: '', bairro: '', numero: '', cidade: '', estado: '',
            complemento: '', pontoReferencia: '', cortesia: [], observacoes: '',
        });
    }
}, { immediate: true });

const calculateAge = (dob, targetField) => {
    if (!dob) return;
    const birthDate = new Date(dob);
    if (isNaN(birthDate.getTime())) return;
    
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    form.value[targetField] = age;
};

const lookupCEP = async () => {
    const cep = form.value.cep.replace(/\D/g, '');
    if (cep.length !== 8) return;

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        if (!data.erro) {
            form.value.rua = data.logradouro;
            form.value.bairro = data.bairro;
            form.value.cidade = data.localidade;
            form.value.estado = data.uf;
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
    }
};



const maskPhone = (value) => {
    if (!value) return '';
    let val = value.replace(/\D/g, '');
    if (val.length > 11) val = val.substring(0, 11);
    if (val.length > 10) {
        return `(${val.substring(0, 2)}) ${val.substring(2, 7)}-${val.substring(7)}`;
    } else if (val.length > 6) {
        return `(${val.substring(0, 2)}) ${val.substring(2, 6)}-${val.substring(6)}`;
    } else if (val.length > 2) {
        return `(${val.substring(0, 2)}) ${val.substring(2)}`;
    } else if (val.length > 0) {
        return `(${val}`;
    }
    return val;
};

const maskCEP = (value) => {
    if (!value) return '';
    let val = value.replace(/\D/g, '').substring(0, 8);
    if (val.length > 5) {
        return `${val.substring(0, 5)}-${val.substring(5)}`;
    }
    return val;
};

const maskCPF = (value) => {
    if (!value) return '';
    let val = value.replace(/\D/g, '').substring(0, 11);
    if (val.length > 9) {
        return `${val.substring(0, 3)}.${val.substring(3, 6)}.${val.substring(6, 9)}-${val.substring(9)}`;
    } else if (val.length > 6) {
        return `${val.substring(0, 3)}.${val.substring(3, 6)}.${val.substring(6)}`;
    } else if (val.length > 3) {
        return `${val.substring(0, 3)}.${val.substring(3)}`;
    }
    return val;
};

const onInputMask = (e, field, type) => {
    let val = e.target.value;
    if (type === 'phone') form.value[field] = maskPhone(val);
    if (type === 'cep') form.value[field] = maskCEP(val);
    if (type === 'cpf') {
        form.value[field] = form.value.isEstrangeiro ? val.toUpperCase() : maskCPF(val);
    }
};

const close = () => {
    emit('close');
};

const submit = () => {
    // Sincroniza o campo clients para a tabela principal
    form.value.clients = form.value.nome;
    if (form.value.temConjuge && form.value.nomeConjuge) {
        form.value.clients += ' & ' + form.value.nomeConjuge;
    }

    if (isEdit.value) {
        router.put(route('sales.atendimentos.update', props.initialData.id), form.value, {
            onSuccess: () => {
                close();
            },
        });
    } else {
        router.post(route('sales.atendimentos.store'), form.value, {
            onSuccess: () => {
                close();
            },
        });
    }
};

const locations = [
    { label: 'Hotel', value: 'hotel' },
    { label: 'Sindicato', value: 'sindicato' },
    { label: 'Externo', value: 'externo' },
    { label: 'Aeroporto', value: 'aeroporto' },
];

// statuses foi movido para o topo e definido via SERVICE_STATUS

const maritals = [
    { label: 'Solteiro(a)', value: 'solteiro' },
    { label: 'Casado(a)', value: 'casado' },
    { label: 'União Estável', value: 'uniao_estavel' },
    { label: 'Divorciado(a)', value: 'divorciado' },
    { label: 'Viúvo(a)', value: 'viuvo' },
];

const incomes = [
    { label: 'ATÉ R$ 3.000', value: '3k' },
    { label: 'R$ 3.001 - R$ 5.000', value: '3-5k' },
    { label: 'R$ 5.001 - R$ 8.000', value: '5-8k' },
    { label: 'R$ 8.001 - R$ 12.000', value: '8-12k' },
    { label: 'R$ 12.001 - R$ 20.000', value: '12-20k' },
    { label: 'ACIMA DE R$ 20.000', value: '20k+' },
];

const togetherOptions = Array.from({ length: 50 }, (_, i) => ({
    label: `${i + 1} ${i === 0 ? 'ano' : 'anos'}`,
    value: `${i + 1} ${i === 0 ? 'ano' : 'anos'}`
}));

const giftOptions = computed(() => {
    const options = props.complimentaryItems?.map(item => ({
        label: item.name.toUpperCase(),
        value: item.code
    })) || [];
    return [{ label: 'NENHUMA', value: 'nenhuma' }, ...options];
});

// Lógica para limpar seleções se 'nenhuma' for escolhida ou vice-versa
watch(() => form.value.cortesia, (newVal, oldVal) => {
    if (!Array.isArray(newVal)) return;
    
    const hadNone = oldVal?.includes('nenhuma');
    const hasNone = newVal.includes('nenhuma');

    if (hasNone && !hadNone) {
        // Se acabou de selecionar 'nenhuma', remove todo o resto
        form.value.cortesia = ['nenhuma'];
    } else if (hasNone && newVal.length > 1) {
        // Se tinha 'nenhuma' e selecionou outra coisa, remove 'nenhuma'
        form.value.cortesia = newVal.filter(c => c !== 'nenhuma');
    }
}, { deep: true });

</script>

<template>
    <Modal :show="show" @close="close" :closeable="false" max-width="80%">
        <div class="h-[90vh] flex flex-col bg-white dark:bg-[#0d1117] border border-slate-200 dark:border-white/5 rounded-xl overflow-hidden relative shadow-2xl">
            <!-- Header (Fixed) -->
            <div class="p-5 sm:px-8 sm:py-6 border-b border-slate-100 dark:border-white/5 relative z-20 shrink-0">
                <div class="absolute top-0 right-0 w-64 h-32 bg-cyan-500/5 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="relative flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 dark:bg-cyan-500/20 border border-indigo-500/30 dark:border-cyan-500/30 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                                {{ !isEdit ? 'Novo Atendimento Prime' : (isReadOnly ? 'Atendimento (Visualização)' : 'Atendimento (Edição)') }}
                            </h2>
                            <p class="text-[9px] text-indigo-600 dark:text-cyan-400/60 font-bold uppercase tracking-widest mt-0.5">
                                {{ !isEdit ? 'Ficha de Qualificação Completa' : (isReadOnly ? 'Detalhamento do Registro' : 'Edição de Dados cadastrados') }}
                            </p>
                        </div>
                    </div>
                    <button @click="close" class="text-slate-400 dark:text-gray-500 hover:text-slate-600 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 sm:px-8 sm:py-6 space-y-8 custom-scrollbar">
                
                <!-- Bloco 1: Identificação & Documentos -->
                <section class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-2">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-6 bg-indigo-500 dark:bg-cyan-500 rounded-full"></span>
                            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">1. Identificação & Documentos</h3>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer scale-90">
                            <input type="checkbox" v-model="form.isEstrangeiro" :disabled="isReadOnly" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 dark:bg-white/5 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 dark:after:bg-gray-500 after:border-slate-300 dark:after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:bg-amber-500 peer-checked:bg-amber-500/20 disabled:opacity-50"></div>
                            <span class="ml-2 text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Estrangeiro?</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-12 gap-x-4 gap-y-2">
                        <div class="col-span-12 sm:col-span-6 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Nome Completo</label>
                            <input v-model="form.nome" type="text" placeholder="NOME DO TITULAR" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px] transition-all uppercase" :class="errors.nome ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.nome">{{ errors.nome[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-12 sm:col-span-3 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">{{ form.isEstrangeiro ? 'Documento / Passaporte' : 'CPF (Opcional)' }}</label>
                            <input :value="form.cpf" @input="onInputMask($event, 'cpf', 'cpf')" type="text" :placeholder="form.isEstrangeiro ? 'DOCUMENTO' : '000.000.000-00'" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px] transition-all uppercase" :class="errors.cpf ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.cpf">{{ errors.cpf[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-12 sm:col-span-3 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">RG (Opcional)</label>
                            <input v-model="form.rg" type="text" placeholder="RG" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px] transition-all uppercase" :class="errors.rg ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.rg">{{ errors.rg[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <SearchableSelect v-model="form.nacionalidade" :options="nationalities" label="Nacionalidade" placeholder="SELECIONE" :error="errors.nacionalidade" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.nacionalidade"></div>
                        </div>
                        <div class="col-span-6 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Nascimento</label>
                            <input v-model="form.dataNascimento" @change="calculateAge(form.dataNascimento, 'idade')" type="date" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px]" :class="errors.dataNascimento ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.dataNascimento">{{ errors.dataNascimento[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-6 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Idade</label>
                            <input v-model="form.idade" type="number" readonly :disabled="isReadOnly" class="w-full h-[38px] bg-slate-100 dark:bg-white/[0.02] border border-slate-200 dark:border-white/5 rounded-xl px-4 text-indigo-600 dark:text-cyan-400 font-bold text-[13px]">
                            <div class="h-[14px]"></div>
                        </div>
                    </div>
                </section>

                <!-- Bloco 2: Contato & Profissional -->
                <section class="space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/5 pb-2">
                        <span class="w-1.5 h-6 bg-emerald-500 dark:bg-emerald-400 rounded-full"></span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">2. Contato & Profissional</h3>
                    </div>
                    <div class="grid grid-cols-12 gap-x-4 gap-y-2">
                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Celular Principal</label>
                            <input :value="form.celular1" @input="onInputMask($event, 'celular1', 'phone')" type="text" placeholder="(00) 00000-0000" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px]" :class="errors.celular1 ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.celular1">{{ errors.celular1[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Celular 2 (Recado)</label>
                            <input :value="form.celular2" @input="onInputMask($event, 'celular2', 'phone')" type="text" placeholder="(00) 00000-0000" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px]">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">E-mail</label>
                            <input v-model="form.email" type="email" placeholder="exemplo@email.com" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border rounded-xl px-4 text-slate-900 dark:text-white text-[13px]" :class="errors.email ? 'border-red-500/50' : 'border-slate-200 dark:border-white/10'">
                            <p class="h-[14px] text-[10px] text-red-500 font-bold uppercase mt-1 px-1 transition-all" v-if="errors.email">{{ errors.email[0] }}</p>
                            <div class="h-[14px]" v-else></div>
                        </div>
                        <div class="col-span-12 sm:col-span-5 flex flex-col">
                            <SearchableSelect v-model="form.profissao" :options="professions" label="Profissão" placeholder="SELECIONE" :error="errors.profissao" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.profissao"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <SearchableSelect v-model="form.estadoCivil" :options="maritals" label="Estado Civil" :error="errors.estadoCivil" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.estadoCivil"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-3 flex flex-col">
                            <SearchableSelect v-model="form.tempoJuntos" :options="togetherOptions" label="Tempo Juntos" placeholder="TEMPO..." :error="errors.tempoJuntos" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.tempoJuntos"></div>
                        </div>
                    </div>
                </section>

                <!-- Bloco 3: Composição Familiar & Renda -->
                <section class="bg-slate-50/50 dark:bg-white/[0.02] p-6 rounded-2xl border border-slate-100 dark:border-white/5 relative group/conjuge space-y-6">
                    <div class="absolute top-0 right-0 p-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.temConjuge" :disabled="isReadOnly" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 dark:bg-white/5 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 dark:after:bg-gray-500 after:border-slate-300 dark:after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:bg-pink-500 peer-checked:bg-pink-500/20 disabled:opacity-50"></div>
                            <span class="ml-3 text-[10px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">{{ form.temConjuge ? 'Com Cônjuge' : 'Sem Cônjuge' }}</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/5 pb-2">
                        <span class="w-1.5 h-6 bg-pink-500 rounded-full"></span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">3. Composição Familiar & Renda</h3>
                    </div>

                    <div class="grid grid-cols-12 gap-x-4 gap-y-6">
                        <!-- Dados do Cônjuge (Condicional) -->
                        <div v-if="form.temConjuge" class="col-span-12 grid grid-cols-12 gap-x-4 gap-y-2 p-4 bg-white dark:bg-white/5 rounded-xl border border-pink-500/10 animate-in fade-in slide-in-from-top-2">
                            <div class="col-span-12 sm:col-span-6 flex flex-col">
                                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Nome do Cônjuge</label>
                                <input v-model="form.nomeConjuge" type="text" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-transparent border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px] uppercase">
                                <div class="h-[14px]"></div>
                            </div>
                            <div class="col-span-6 sm:col-span-3 flex flex-col">
                                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Nascimento</label>
                                <input v-model="form.dataNascimentoConjuge" @change="calculateAge(form.dataNascimentoConjuge, 'idadeConjuge')" type="date" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-transparent border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px]">
                                <div class="h-[14px]"></div>
                            </div>
                            <div class="col-span-6 sm:col-span-3 flex flex-col">
                                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Idade</label>
                                <input v-model="form.idadeConjuge" type="number" readonly class="w-full h-[38px] bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/5 rounded-xl px-4 text-pink-500 font-bold text-[13px]">
                                <div class="h-[14px]"></div>
                            </div>
                            <div class="col-span-12 sm:col-span-12 flex flex-col">
                                <SearchableSelect v-model="form.profissaoConjuge" :options="professions" label="Profissão do Cônjuge" placeholder="SELECIONE" :disabled="isReadOnly" />
                                <div class="h-[14px]"></div>
                            </div>
                        </div>

                        <div class="col-span-12 sm:col-span-4 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Quantidade de Filhos</label>
                            <input v-model="form.quantidadeFilhos" type="number" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px]">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-8 flex flex-col">
                            <SearchableSelect v-model="form.rendaFamiliar" :options="incomes" label="Renda Familiar Mensal" placeholder="SELECIONE A FAIXA DE RENDA" :error="errors.rendaFamiliar" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.rendaFamiliar"></div>
                        </div>
                    </div>
                </section>

                <!-- Bloco 4: Origem, Endereço & Logística -->
                <section class="space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/5 pb-2">
                        <span class="w-1.5 h-6 bg-orange-500 rounded-full"></span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">4. Origem, Endereço & Logística</h3>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-x-4 gap-y-2">
                        <div class="col-span-12 sm:col-span-3 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">CEP</label>
                            <div class="relative h-[38px]">
                                <input :value="form.cep" @input="onInputMask($event, 'cep', 'cep')" @blur="lookupCEP" type="text" placeholder="00000-000" :disabled="isReadOnly" class="w-full h-full bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px]">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2" v-if="!errors.cep">
                                    <svg class="w-3 h-3 text-orange-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>
                            </div>
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-7 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Logradouro (Rua/Av)</label>
                            <input v-model="form.rua" type="text" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px] uppercase">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-2 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Nº</label>
                            <input v-model="form.numero" type="text" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px] uppercase">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-5 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Bairro</label>
                            <input v-model="form.bairro" type="text" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px] uppercase">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-5 flex flex-col">
                            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Cidade</label>
                            <input v-model="form.cidade" type="text" :disabled="isReadOnly" class="w-full h-[38px] bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-xl px-4 text-slate-900 dark:text-white text-[13px] uppercase">
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-2 flex flex-col">
                            <SearchableSelect v-model="form.estado" :options="states" label="UF" placeholder="UF" :disabled="isReadOnly" />
                            <div class="h-[14px]"></div>
                        </div>
                        
                        <div class="col-span-12 sm:col-span-6 flex flex-col">
                            <SearchableSelect v-model="form.local" :options="locations" label="Local de Atendimento" :error="errors.local" :disabled="isReadOnly" />
                            <div class="h-[14px]" v-if="!errors.local"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-6 flex flex-col">
                            <SearchableSelect v-model="form.opc_id" :options="availableAvatars.map(av => ({ label: av.name, value: av.id }))" label="OPC Responsável" placeholder="NENHUM" :disabled="isReadOnly" />
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-6 flex flex-col">
                            <SearchableSelect v-model="form.qualification" :options="qualifications.map(q => ({ label: q.code + ' - ' + q.name, value: q.code }))" label="Qualificação Atual" placeholder="SELECIONE" :disabled="isReadOnly || (form.status !== 'MESA' && form.status !== 'table')" />
                            <div class="h-[14px]"></div>
                        </div>
                        <div class="col-span-12 sm:col-span-6 flex flex-col">
                            <SearchableSelect v-model="form.cortesia" :options="giftOptions" label="Cortesias Entregues" placeholder="SELECIONE" multiple :disabled="isReadOnly" />
                            <div class="h-[14px]"></div>
                        </div>
                    </div>
                </section>

                <!-- Bloco 5: Observações -->
                <section class="space-y-3">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-white/5 pb-2">
                        <span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
                        <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">5. Observações do Atendimento</h3>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest px-1 block mb-1 h-[11px] leading-none">Detalhes Adicionais</label>
                        <textarea v-model="form.observacoes" rows="3" :disabled="isReadOnly" placeholder="DETALHES RELEVANTES SOBRE O PERFIL, NEGOCIAÇÃO OS LOGÍSTICA..." class="w-full bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-3 text-slate-900 dark:text-white text-[13px] focus:border-cyan-500/40 transition-all uppercase placeholder:text-slate-300 dark:placeholder:text-white/10 resize-none disabled:opacity-50"></textarea>
                    </div>
                </section>
            </div>

            <!-- Footer (Fixed) -->
            <div class="p-5 sm:px-8 sm:py-4 border-t border-slate-100 dark:border-white/5 bg-white dark:bg-[#0d1117] relative z-20 shrink-0">
                <!-- Modo Edição de Registro Existente -->
                <div v-if="isEdit" class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <button 
                        @click="isReadOnly ? close() : (isReadOnly = true)"
                        class="order-2 sm:order-1 flex-1 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-500 dark:text-gray-500 hover:text-slate-700 dark:hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all"
                    >
                        {{ isReadOnly ? 'Fechar' : 'Cancelar Edição' }}
                    </button>
                    <button 
                        @click="isReadOnly ? (isReadOnly = false) : submit()"
                        class="order-1 sm:order-2 flex-[2] py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 dark:from-cyan-600 dark:to-blue-600 hover:from-indigo-500 hover:to-blue-500 dark:hover:from-cyan-500 dark:hover:to-blue-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-indigo-500/10 dark:shadow-cyan-500/10 active:scale-95 transition-all"
                    >
                        {{ isReadOnly ? 'Editar Atendimento' : 'Salvar Alterações' }}
                    </button>
                    <!-- Botão de Proposta -->
                    <button 
                        v-if="isReadOnly"
                        @click="showProposalModal = true"
                        class="order-0 sm:order-last flex-[3] py-2.5 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl active:scale-95 transition-all flex items-center justify-center gap-2"
                        :class="initialData.proposal ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-500/20' : 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-500/20'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ initialData.proposal ? 'Visualizar Proposta' : 'Preencher Proposta' }}
                    </button>
                </div>
                <!-- Modo Novo Registro -->
                <div v-else class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <button 
                        @click="close"
                        class="order-2 sm:order-1 flex-1 py-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-500 dark:text-gray-500 hover:text-slate-700 dark:hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all"
                    >
                        Descartar
                    </button>
                    <button 
                        @click="submit"
                        class="order-1 sm:order-2 flex-[2] py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 dark:from-cyan-600 dark:to-blue-600 hover:from-indigo-500 hover:to-blue-500 dark:hover:from-cyan-500 dark:hover:to-blue-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-indigo-500/10 dark:shadow-cyan-500/10 active:scale-95 transition-all"
                    >
                        Cadastrar Atendimento
                    </button>
                </div>
            </div>
        </div>
    </Modal>

    <!-- Modal de Proposta -->
    <ProposalFormModal 
        :show="showProposalModal" 
        :service="initialData" 
        @close="showProposalModal = false" 
    />
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

select option {
    background-color: white !important;
    color: #0f172a !important;
}

.dark select option {
    background-color: #0d1117 !important;
    color: white !important;
}
</style>
