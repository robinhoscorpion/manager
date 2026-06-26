<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    service: Object,
});

const emit = defineEmits(['close']);

const products = ref([]);
const isViewMode = ref(false);
const isEditMode = ref(false);
const loadingProducts = ref(false);
const activeTypeTab = ref(null);

const form = useForm({
    client_id: '',
    sales_service_id: '',
    product_id: '',
    total_value: 0,
    quantity: 0,
    observations: '',
    payments: []
});

const paymentCategories = [
    { key: 'taxa_contrato', label: 'Taxa de Contrato', color: 'bg-amber-500', icon: 'M9 12l2 2 4-4' },
    { key: 'entrada', label: 'Entrada', color: 'bg-emerald-500', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'saldo', label: 'Saldo', color: 'bg-blue-500', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { key: 'taxa_manutencao', label: 'Taxa de Manutenção', color: 'bg-purple-500', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
];

const getCategoryMissingBalance = (category) => {
    if (!selectedProduct.value) return 0;
    
    const currentSum = form.payments
        .filter(p => p.category === category)
        .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);

    if (category === 'taxa_contrato') {
        const target = parseFloat(selectedProduct.value.contract_fee) || 0;
        return Math.max(0, target - currentSum);
    } 
    else if (category === 'taxa_manutencao') {
        const target = (parseInt(selectedProduct.value.maintenance_fee_installments) || 12) * (parseFloat(selectedProduct.value.maintenance_fee_value) || 0);
        return Math.max(0, target - currentSum);
    }
    else if (category === 'entrada' || category === 'saldo') {
        // Entrada e Saldo dividem o Valor Total da Proposta. O que falta é o remainingBalance.
        return Math.max(0, remainingBalance.value);
    }
    return 0;
};

const addPaymentLine = (category) => {
    const linesInCategory = form.payments.filter(p => p.category === category).length;
    if (linesInCategory < 5) {
        const missingBalance = getCategoryMissingBalance(category);
        
        form.payments.push({
            category: category,
            payment_method: getDefaultPaymentMethod(),
            installments: 1,
            installment_value: missingBalance,
            total_value: missingBalance,
            start_date: new Date().toISOString().split('T')[0]
        });
    }
};

const autoBalanceCategory = (category, ignoreIndex = -1) => {
    if (!selectedProduct.value) return;

    // 1. Auto-balance para taxas (Contrato e Manutenção)
    if (category === 'taxa_contrato' || category === 'taxa_manutencao') {
        const catLines = form.payments.filter(p => p.category === category);
        if (catLines.length > 0) {
            const lastLine = catLines[catLines.length - 1];
            const lastLineGlobalIndex = form.payments.indexOf(lastLine);
            
            if (ignoreIndex !== lastLineGlobalIndex) {
                const sumExceptLast = catLines
                    .filter(p => p !== lastLine)
                    .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
                    
                let target = 0;
                if (category === 'taxa_contrato') target = parseFloat(selectedProduct.value.contract_fee) || 0;
                if (category === 'taxa_manutencao') target = (parseInt(selectedProduct.value.maintenance_fee_installments) || 12) * (parseFloat(selectedProduct.value.maintenance_fee_value) || 0);

                let diff = target - sumExceptLast;
                if (diff < 0) diff = 0;
                
                lastLine.total_value = diff;
                lastLine.installment_value = diff / (parseInt(lastLine.installments) || 1);
            }
        }
    }

    // 2. Auto-balance para Entrada e Saldo (afeta a última linha de Saldo)
    if (category === 'entrada' || category === 'saldo') {
        const saldoLines = form.payments.filter(p => p.category === 'saldo');
        if (saldoLines.length > 0) {
            const lastSaldo = saldoLines[saldoLines.length - 1];
            const lastSaldoGlobalIndex = form.payments.indexOf(lastSaldo);
            
            if (ignoreIndex !== lastSaldoGlobalIndex) {
                const sumExceptLastSaldo = form.payments
                    .filter(p => (p.category === 'entrada' || p.category === 'saldo') && p !== lastSaldo)
                    .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
                    
                let diff = form.total_value - sumExceptLastSaldo;
                if (diff < 0) diff = 0;
                
                lastSaldo.total_value = diff;
                lastSaldo.installment_value = diff / (parseInt(lastSaldo.installments) || 1);
            }
        }
    }
};

const removePaymentLine = (index) => {
    const category = form.payments[index].category;
    form.payments.splice(index, 1);
    autoBalanceCategory(category);
    calculateGrandTotal();
};

const updateLineTotal = (index) => {
    const line = form.payments[index];
    const installments = parseInt(line.installments) || 1;
    const value = parseFloat(line.installment_value) || 0;
    const newTotalLine = installments * value;

    if (line.category === 'entrada' && selectedProduct.value) {
        const othersSum = form.payments
            .filter((p, i) => p.category === 'entrada' && i !== index)
            .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
        
        const percentualLimite = parseFloat(selectedProduct.value.min_down_payment_percentage) || 100;
        const limiteMaximo = (parseFloat(selectedProduct.value.price) * percentualLimite) / 100;
        const totalComNovaLinha = othersSum + newTotalLine;

        if (totalComNovaLinha > (limiteMaximo + 0.01)) {
            const valorDisponivel = Math.max(0, limiteMaximo - othersSum);
            
            if (value > 0 && Math.floor(valorDisponivel / value) >= 1) {
                line.installments = Math.floor(valorDisponivel / value);
                line.total_value = line.installments * value;
                showLimitWarning(`A entrada atingiu o teto de ${percentualLimite}%. Reduzimos a quantidade de parcelas para ${line.installments} para respeitar o limite.`);
            } else {
                line.installments = 1;
                line.installment_value = valorDisponivel;
                line.total_value = valorDisponivel;
                showLimitWarning(`A entrada atingiu o limite de ${percentualLimite}% (${maskCurrency(limiteMaximo)}). O valor da parcela foi ajustado.`);
            }
            
            autoBalanceCategory(line.category, index);
            calculateGrandTotal();
            return;
        }
    }

    line.total_value = newTotalLine;
    autoBalanceCategory(line.category, index);
    calculateGrandTotal();
};

const paymentsSum = computed(() => {
    return form.payments
        .filter(p => p.category === 'entrada' || p.category === 'saldo')
        .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
});

const getCategoryTotal = (category) => {
    return form.payments
        .filter(p => p.category === category)
        .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
};

const remainingBalance = computed(() => {
    return form.total_value - paymentsSum.value;
});

const calculateGrandTotal = () => {
    // Total fixo
};

const onInstallmentsInput = (index) => {
    const line = form.payments[index];
    const installments = parseInt(line.installments) || 1;
    
    // Quando o usuário muda a quantidade de parcelas, o valor total da linha se mantém,
    // e o sistema apenas recalcula o valor de cada parcela dividindo o total.
    line.installment_value = (parseFloat(line.total_value) || 0) / installments;
    
    calculateGrandTotal();
};

const onLinePriceInput = (e, index) => {
    let val = e.target.value.replace(/\D/g, '');
    form.payments[index].installment_value = parseFloat(val) / 100;
    updateLineTotal(index);
};

const quantityLabel = computed(() => {
    if (!selectedProduct.value || !selectedProduct.value.product_type) return 'Quantidade';
    const typeName = selectedProduct.value.product_type.name.toLowerCase();
    if (typeName.includes('ponto')) return 'Qtd. de Pontos';
    if (typeName.includes('diária') || typeName.includes('diaria')) return 'Qtd. de Diárias';
    if (typeName.includes('cota')) return 'Número de Cotas';
    return `Quantidade (${selectedProduct.value.product_type.name})`;
});

const productTypes = computed(() => {
    const types = [];
    products.value.forEach(p => {
        if (p.product_type && !types.find(t => t.id === p.product_type.id)) {
            types.push(p.product_type);
        }
    });
    return types;
});

const filteredProducts = computed(() => {
    if (!activeTypeTab.value && productTypes.value.length > 0) {
        activeTypeTab.value = productTypes.value[0].id;
    }
    return products.value.filter(p => p.product_type_id === activeTypeTab.value);
});

const selectedProduct = computed(() => {
    return products.value.find(p => p.id === form.product_id);
});

const hasCpf = computed(() => {
    return !!props.service?.client?.cpf;
});

const fetchProducts = async () => {
    loadingProducts.value = true;
    try {
        const response = await fetch(route('api.products'));
        products.value = await response.json();
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
    } finally {
        loadingProducts.value = false;
    }
};

const loadProposalData = () => {
    const service = props.service;
    if (!service) return;

    if (service.proposal) {
        isViewMode.value = true;
        isEditMode.value = false;
        
        if (service.proposal.product) {
            activeTypeTab.value = service.proposal.product.product_type_id;
        }

        form.defaults({
            client_id: service.client_id,
            sales_service_id: service.id,
            product_id: service.proposal.product_id,
            total_value: parseFloat(service.proposal.total_value) || 0,
            quantity: service.proposal.quantity || 0,
            observations: service.proposal.observations || '',
            payments: service.proposal.payments ? service.proposal.payments.map(p => ({
                category: p.category,
                payment_method: p.payment_method,
                installments: p.installments,
                installment_value: parseFloat(p.installment_value),
                total_value: parseFloat(p.total_value),
                start_date: p.start_date
            })) : []
        });
        form.reset();
    } else {
        isViewMode.value = false;
        isEditMode.value = false;
        activeTypeTab.value = null;
        form.defaults({
            client_id: service.client_id,
            sales_service_id: service.id,
            product_id: '',
            total_value: 0,
            quantity: 0,
            observations: '',
            payments: []
        });
        form.reset();
    }
    form.clearErrors();
};

const enableEdit = () => {
    isViewMode.value = false;
    isEditMode.value = true;
};

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        loadProposalData();
    }
}, { immediate: true });

onMounted(() => {
    fetchProducts();
    fetchPaymentMethods();
});

watch(() => form.product_id, (newProductId) => {
    if ((isViewMode.value && !isEditMode.value) || !newProductId) return; 
    
    const product = products.value.find(p => p.id === newProductId);
    if (product && !isEditMode.value) {
        form.quantity = product.quantity || 0;
        
        // Define o valor total padrão como o preço cheio do produto
        const productPrice = parseFloat(product.price) || parseFloat(product.min_price) || 0;
        form.total_value = productPrice;
        
        // Inicializar Pagamentos com Padrões do Produto
        const initialPayments = [];

        // 1. Taxa de Contrato
        if (parseFloat(product.contract_fee) > 0) {
            initialPayments.push({
                category: 'taxa_contrato',
                payment_method: getDefaultPaymentMethod('pix'),
                installments: 1,
                installment_value: parseFloat(product.contract_fee),
                total_value: parseFloat(product.contract_fee),
                start_date: new Date().toISOString().split('T')[0]
            });
        }

        // 2. Taxa de Manutenção (Com lógica de carência)
        if (!product.is_maintenance_exempt && parseFloat(product.maintenance_fee_value) > 0) {
            const today = new Date();
            const startYear = today.getFullYear() + (parseInt(product.maintenance_fee_delay_years) || 0);
            const startMonth = today.getMonth() + 1; // Início no próximo mês após carência de anos
            const startDay = parseInt(product.maintenance_fee_day) || 10;
            
            // Garantir que a data seja válida (ex: evitar 31 de fevereiro)
            const startDate = new Date(startYear, startMonth, startDay);
            
            initialPayments.push({
                category: 'taxa_manutencao',
                payment_method: getDefaultPaymentMethod('boleto'),
                installments: parseInt(product.maintenance_fee_installments) || 12,
                installment_value: parseFloat(product.maintenance_fee_value),
                total_value: (parseInt(product.maintenance_fee_installments) || 12) * parseFloat(product.maintenance_fee_value),
                start_date: startDate.toISOString().split('T')[0]
            });
        }

        // 3. Linha de Entrada (Pré-preenchida com o mínimo exigido)
        const minDownPaymentValue = (productPrice * (parseFloat(product.min_down_payment_percentage) || 0)) / 100;
        
        initialPayments.push({
            category: 'entrada',
            payment_method: getDefaultPaymentMethod('pix'),
            installments: 1,
            installment_value: minDownPaymentValue,
            total_value: minDownPaymentValue,
            start_date: new Date().toISOString().split('T')[0]
        });

        // 4. Saldo (Pré-preenchido com o restante a pagar)
        const saldoValue = productPrice - minDownPaymentValue;
        if (saldoValue > 0) {
            // Data de vencimento da primeira parcela do saldo: 1 mês para frente
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            
            initialPayments.push({
                category: 'saldo',
                payment_method: getDefaultPaymentMethod('boleto'),
                installments: 1, // Padrão 1x, o usuário pode alterar as parcelas
                installment_value: saldoValue,
                total_value: saldoValue,
                start_date: nextMonth.toISOString().split('T')[0]
            });
        }

        form.payments = initialPayments;
    }
});

const currentDownPaymentTotal = computed(() => {
    return form.payments
        .filter(p => p.category === 'entrada')
        .reduce((acc, curr) => acc + (parseFloat(curr.total_value) || 0), 0);
});

const minRequiredDownPayment = computed(() => {
    if (!selectedProduct.value || !selectedProduct.value.min_down_payment_percentage) return 0;
    return (selectedProduct.value.price * selectedProduct.value.min_down_payment_percentage) / 100;
});

const isDownPaymentValid = computed(() => {
    if (isViewMode.value) return true;
    return currentDownPaymentTotal.value >= minRequiredDownPayment.value;
});

const isBalanceMatch = computed(() => {
    if (isViewMode.value) return true;
    return Math.abs(remainingBalance.value) < 0.01;
});

const entryPercentage = computed(() => {
    if (!selectedProduct.value || !selectedProduct.value.price) return 0;
    return (currentDownPaymentTotal.value / parseFloat(selectedProduct.value.price)) * 100;
});

const isDownPaymentOverLimit = computed(() => {
    if (!selectedProduct.value || isViewMode.value) return false;
    const percentLimit = parseFloat(selectedProduct.value.min_down_payment_percentage) || 100;
    return entryPercentage.value > (percentLimit + 0.1);
});

const isConfirmingApproval = ref(false);
const isArtificialProcessing = ref(false);
const isShowingLimitWarning = ref(false);
const limitWarningMessage = ref('');

const showLimitWarning = (message) => {
    limitWarningMessage.value = message;
    isShowingLimitWarning.value = true;
};

const approveProposal = () => {
    if (!props.service.proposal) return;
    isConfirmingApproval.value = true;
};

const confirmApprove = () => {
    const startTime = Date.now();
    isArtificialProcessing.value = true;

    form.post(route('sales.propostas.approve', props.service.proposal.id), {
        onSuccess: () => {
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, 5000 - elapsed); // Garante 5s de animação
            
            setTimeout(() => {
                isArtificialProcessing.value = false;
                isConfirmingApproval.value = false;
                emit('close');
                form.reset();
            }, remaining);
        },
        onError: () => {
            isArtificialProcessing.value = false;
        }
    });
};

const submit = () => {
    if (!isDownPaymentValid.value || isDownPaymentOverLimit.value) return;
    
    if (isEditMode.value && props.service.proposal) {
        form.put(route('sales.propostas.update', props.service.proposal.id), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    } else {
        form.post(route('sales.propostas.store'), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    }
};

const paymentMethods = ref([]);
const loadingPaymentMethods = ref(false);

const fetchPaymentMethods = async () => {
    loadingPaymentMethods.value = true;
    try {
        const response = await fetch(route('api.payment-methods'));
        paymentMethods.value = await response.json();
    } catch (error) {
        console.error('Erro ao buscar formas de pagamento:', error);
    } finally {
        loadingPaymentMethods.value = false;
    }
};

const maskCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
};

const getDefaultPaymentMethod = (preferredType = '') => {
    if (!paymentMethods.value || paymentMethods.value.length === 0) return 'pix';
    const preferred = paymentMethods.value.find(m => m.type === preferredType);
    return (preferred || paymentMethods.value[0]).value;
};

const displayValue = ref('R$ 0,00');

watch(() => form.total_value, (val) => {
    displayValue.value = maskCurrency(val);
});

const onPriceInput = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    form.total_value = parseFloat(val) / 100;
    
    // Auto-balanceia a última linha do Saldo se o valor total for alterado manualmente
    autoBalanceCategory('saldo');
};

const close = () => {
    emit('close');
};

</script>

<template>
    <Modal :show="show" @close="close" :closeable="false" max-width="5xl">
        <div v-if="show" class="h-[90vh] flex flex-col bg-white/95 dark:bg-[#0f1219]/95 backdrop-blur-3xl border border-white/60 dark:border-white/10 rounded-2xl overflow-hidden relative shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_60px_-15px_rgba(0,0,0,0.5)] transition-all duration-500">
            <!-- Header -->
            <div class="border-b border-white/40 dark:border-white/10 bg-white/40 dark:bg-emerald-500/5 flex items-center justify-between backdrop-blur-xl relative z-10"
                 :class="isViewMode ? 'p-4 px-6' : 'p-6'">
                <div class="flex items-center gap-5">
                    <div class="rounded-2xl flex items-center justify-center shadow-xl transition-all"
                         :class="[
                            isViewMode ? 'bg-amber-500/10 border border-amber-500/20 w-10 h-10' : 
                            (isEditMode ? 'bg-indigo-500/10 dark:bg-cyan-500/10 border border-indigo-500/20 dark:border-cyan-500/20 w-12 h-12' : 'bg-indigo-500/10 dark:bg-emerald-500/10 border border-indigo-500/20 dark:border-emerald-500/20 w-12 h-12')
                         ]">
                        <svg class="transition-all" :class="[isViewMode ? 'w-5 h-5 text-amber-500' : (isEditMode ? 'w-6 h-6 text-indigo-600 dark:text-cyan-400' : 'w-6 h-6 text-indigo-600 dark:text-emerald-400')]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-black text-slate-900 dark:text-white uppercase tracking-tighter transition-all"
                            :class="isViewMode ? 'text-base' : 'text-lg'">
                            {{ isViewMode ? 'Visualizar Proposta' : (isEditMode ? 'Editar Proposta Salva' : 'Montar Nova Proposta') }}
                        </h2>
                        <p class="text-[9px] font-bold uppercase tracking-widest mt-0.5"
                           :class="isViewMode ? 'text-amber-600 dark:text-amber-400/60' : (isEditMode ? 'text-indigo-600 dark:text-cyan-400/60' : 'text-indigo-600 dark:text-emerald-400/60')">
                            {{ isViewMode ? 'Documento de Venda Consolidado' : (isEditMode ? 'Alteração de Dados Financeiros' : 'Configuração Comercial e Financeira') }}
                        </p>
                    </div>
                </div>

                <button 
                    v-if="isViewMode && service?.proposal?.status !== 'approved'"
                    @click="enableEdit"
                    class="px-4 py-2 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-amber-500 hover:text-white transition-all flex items-center gap-2"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Proposta
                </button>
            </div>

            <!-- Content -->
            <div 
                class="overflow-y-auto flex-1 custom-scrollbar transition-all duration-300 bg-white/50 dark:bg-transparent"
                :class="isViewMode ? 'p-4 space-y-4' : 'p-8 space-y-8'"
            >
                <!-- Cliente Info -->
                <div class="bg-white/80 dark:bg-white/[0.02] backdrop-blur-md border border-slate-200/60 dark:border-white/5 rounded-2xl p-5 flex items-center justify-between shadow-sm dark:shadow-none hover:shadow-md transition-shadow"
                     :class="{ 'py-3 px-4': isViewMode }">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-0.5">Cliente / Titular</p>
                        <p class="text-slate-900 dark:text-white font-black" :class="isViewMode ? 'text-sm' : 'text-base'">{{ service?.client?.nome }}</p>
                    </div>
                    <div v-if="selectedProduct" class="text-center px-6 border-x border-slate-200/50 dark:border-white/5">
                        <p class="text-[9px] font-black text-emerald-500 uppercase tracking-[0.2em] mb-0.5">Duração</p>
                        <p class="text-slate-900 dark:text-white font-black" :class="isViewMode ? 'text-xs' : 'text-sm'">{{ selectedProduct.duration || 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-0.5">{{ service?.proposal?.contract_number ? 'Contrato' : 'Protocolo' }}</p>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-500/10 dark:bg-cyan-500/10 rounded-md">
                            <svg class="w-3 h-3 text-indigo-500 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                            <p class="text-indigo-600 dark:text-cyan-400 font-mono font-black tracking-wider" :class="isViewMode ? 'text-[11px]' : 'text-sm'">
                                 {{ service?.proposal?.contract_number || `#${service?.id}` }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alerta de CPF Ausente -->
                <div v-if="!hasCpf && !isViewMode" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-center gap-4 animate-pulse">
                    <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black text-red-500 uppercase tracking-widest">Documento Necessário</h4>
                        <p class="text-[11px] text-red-600/70 dark:text-red-400/60 font-medium leading-tight">O cadastro deste cliente não possui **CPF ou Documento**. É obrigatório preenchê-lo no cadastro do atendimento antes de gerar a proposta.</p>
                    </div>
                </div>

                <!-- Categorias de Produto -->
                <div :class="isViewMode ? 'space-y-3' : 'space-y-5'">
                    <div class="flex gap-2 p-1.5 bg-white/60 dark:bg-white/[0.02] backdrop-blur-sm border border-slate-200/50 dark:border-white/5 rounded-2xl w-fit shadow-sm dark:shadow-none">
                        <button 
                            v-for="type in productTypes" 
                            :key="type.id"
                            @click="activeTypeTab = type.id; form.product_id = ''"
                            :disabled="isViewMode"
                            class="px-5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50"
                            :class="[
                                activeTypeTab === type.id ? 'bg-indigo-600 dark:bg-emerald-500 text-white shadow-lg shadow-indigo-500/20 dark:shadow-emerald-500/20 scale-105' : 'text-slate-500 dark:text-gray-500 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white',
                                isViewMode ? 'py-1.5' : 'py-2'
                            ]"
                        >
                            {{ type.name }}
                        </button>
                    </div>

                    <!-- Produto -->
                    <div class="space-y-1 relative z-20">
                        <SearchableSelect 
                            v-model="form.product_id"
                            :options="filteredProducts.map(p => ({ label: p.name, value: p.id }))"
                            label="Catálogo de Produtos"
                            placeholder="Selecione na categoria acima..."
                            :error="form.errors.product_id"
                            :disabled="isViewMode"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" :class="{ 'gap-3': isViewMode }">
                    <!-- Quantidade -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest px-1">{{ quantityLabel }}</label>
                        <div class="relative group">
                            <input 
                                v-model="form.quantity"
                                type="number"
                                :readonly="(!!selectedProduct?.quantity) || isViewMode"
                                :disabled="isViewMode"
                                class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200 dark:border-white/10 rounded-2xl px-5 text-slate-900 dark:text-white font-black focus:border-indigo-500/40 dark:focus:border-emerald-500/40 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-emerald-500/10 transition-all outline-none shadow-sm disabled:opacity-60"
                                :class="[
                                    isViewMode ? 'py-2.5 text-base' : 'py-3.5 text-xl',
                                    { 'border-red-500/50 focus:ring-red-500/10': form.errors.quantity, 'bg-slate-50 dark:bg-white/[0.02] border-dashed text-slate-500': (!!selectedProduct?.quantity) || isViewMode }
                                ]"
                            >
                            <div v-if="!!selectedProduct?.quantity" class="absolute right-4 top-1/2 -translate-y-1/2">
                                <span class="text-[9px] font-black bg-indigo-500/10 dark:bg-cyan-500/10 text-indigo-600 dark:text-cyan-400 px-2.5 py-1 rounded-md uppercase tracking-[0.2em]">Automático</span>
                            </div>
                        </div>
                    </div>

                    <!-- Valor Total Consolidado -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest px-1">Valor Total Final (Proposta)</label>
                        <div class="relative group">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-indigo-500/50 dark:text-emerald-500/50 font-black group-focus-within:text-indigo-500 dark:group-focus-within:text-emerald-400 transition-colors"
                                  :class="isViewMode ? 'text-lg' : 'text-xl'">R$</span>
                            <input 
                                :value="maskCurrency(form.total_value).replace('R$', '').trim()"
                                @input="onPriceInput"
                                type="text"
                                :disabled="isViewMode"
                                class="w-full bg-indigo-50/50 dark:bg-[#0f1219]/80 border border-indigo-200 dark:border-emerald-500/20 rounded-2xl pl-14 pr-5 text-indigo-900 dark:text-emerald-400 font-black focus:border-indigo-500/50 dark:focus:border-emerald-500/50 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-emerald-500/10 transition-all outline-none disabled:opacity-60 shadow-inner"
                                :class="[
                                    isViewMode ? 'py-2.5 text-xl' : 'py-3.5 text-2xl',
                                    { 'bg-slate-50 dark:bg-white/[0.02] text-slate-500 dark:text-gray-400 border-slate-200 dark:border-white/10': isViewMode }
                                ]"
                            >
                        </div>
                    </div>
                </div>

                <!-- Sessões de Pagamento Dinâmicas -->
                <div class="pt-4" :class="isViewMode ? 'space-y-4' : 'space-y-8'">
                    <div v-for="cat in paymentCategories" :key="cat.key" class="space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-1.5">
                            <div class="flex items-center gap-2.5">
                                <div :class="['rounded-lg flex items-center justify-center shadow-lg', cat.color, isViewMode ? 'w-5 h-5' : 'w-6 h-6']">
                                    <svg :class="isViewMode ? 'w-3 h-3' : 'w-3.5 h-3.5'" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="cat.icon" />
                                    </svg>
                                </div>
                                <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ cat.label }}</h3>
                                
                                <!-- Badge de Percentual (Apenas para Entrada) -->
                                <div v-if="cat.key === 'entrada' && selectedProduct" class="flex items-center gap-1.5 ml-2">
                                    <div :class="[
                                        'px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-tighter transition-all',
                                        entryPercentage < (selectedProduct.min_down_payment_percentage || 0) ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 
                                        (entryPercentage > (selectedProduct.min_down_payment_percentage || 0) + 0.1 ? 'bg-red-500/10 text-red-500 border border-red-500/20 shadow-[0_0_100px_rgba(239,68,68,0.1)]' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20')
                                    ]">
                                        {{ entryPercentage.toFixed(1) }}%
                                    </div>
                                    <span v-if="entryPercentage < (selectedProduct.min_down_payment_percentage || 0)" class="text-[7px] text-amber-500/60 font-bold uppercase tracking-widest">Abaixo do Mín.</span>
                                    <span v-else-if="entryPercentage > (selectedProduct.min_down_payment_percentage || 0) + 0.1" class="text-[7px] text-red-500/60 font-bold uppercase animate-pulse tracking-widest">Capped em {{ selectedProduct.min_down_payment_percentage }}%</span>
                                </div>
                            </div>
                            <button 
                                v-if="!isViewMode"
                                @click="addPaymentLine(cat.key)"
                                type="button"
                                class="text-[9px] font-black text-emerald-400 hover:text-emerald-300 uppercase tracking-widest flex items-center gap-1 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Adicionar
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div 
                                v-for="(payment, idx) in form.payments.map((p, i) => ({...p, originalIndex: i})).filter(p => p.category === cat.key)" 
                                :key="payment.originalIndex"
                                class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4 items-end bg-white/60 dark:bg-white/[0.02] backdrop-blur-sm rounded-2xl border border-slate-200/50 dark:border-white/5 relative group transition-all hover:border-slate-300 dark:hover:border-white/10"
                                :class="isViewMode ? 'p-3 px-4' : 'p-4 shadow-sm'"
                            >
                                <!-- Forma de Pagamento -->
                                <div class="md:col-span-3 space-y-1">
                                    <label class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Forma</label>
                                    <select v-model="form.payments[payment.originalIndex].payment_method" :disabled="isViewMode" class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200/60 dark:border-white/10 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold text-[11px] outline-none disabled:opacity-50 transition-all focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-emerald-500/20">
                                        <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                </div>

                                <div class="md:col-span-1 space-y-1">
                                    <label class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Parc.</label>
                                    <input 
                                        v-model="form.payments[payment.originalIndex].installments" 
                                        @input="onInstallmentsInput(payment.originalIndex)"
                                        type="number" 
                                        min="1"
                                        :disabled="isViewMode"
                                        class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200/60 dark:border-white/10 rounded-xl px-2 py-2 text-slate-900 dark:text-white font-black text-[11px] outline-none disabled:opacity-50 text-center transition-all focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-emerald-500/20"
                                    >
                                </div>

                                <div class="md:col-span-3 space-y-1">
                                    <label class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Vlr. Parcela</label>
                                    <input 
                                        :value="maskCurrency(form.payments[payment.originalIndex].installment_value)"
                                        @input="onLinePriceInput($event, payment.originalIndex)"
                                        type="text"
                                        :disabled="isViewMode"
                                        class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200/60 dark:border-white/10 rounded-xl px-3 py-2 text-indigo-600 dark:text-emerald-400 font-black text-[11px] outline-none disabled:opacity-50 transition-all focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-emerald-500/20"
                                    >
                                </div>

                                <div class="md:col-span-3 space-y-1">
                                    <label class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Data Inicial</label>
                                    <input 
                                        v-model="form.payments[payment.originalIndex].start_date" 
                                        type="date"
                                        :disabled="isViewMode"
                                        class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200/60 dark:border-white/10 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold text-[11px] outline-none disabled:opacity-50 transition-all focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-emerald-500/20"
                                    >
                                </div>

                                <div class="md:col-span-2 space-y-1">
                                    <label class="text-[8px] font-black text-emerald-500/50 uppercase tracking-widest">Total</label>
                                    <div class="w-full bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-2 py-2 text-emerald-600 dark:text-emerald-400 font-black text-[11px] text-center shadow-inner">
                                        {{ maskCurrency(form.payments[payment.originalIndex].total_value) }}
                                    </div>
                                </div>

                                <button 
                                    v-if="!isViewMode"
                                    @click="removePaymentLine(payment.originalIndex)"
                                    type="button"
                                    class="absolute -right-2 -top-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:scale-110"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            
                            <div v-if="form.payments.filter(p => p.category === cat.key).length === 0" class="py-2 text-center border-2 border-dashed border-white/[0.02] rounded-xl">
                                <p class="text-[8px] text-gray-600 uppercase font-bold tracking-widest italic">Sem lançamentos</p>
                            </div>
                            
                            <!-- Total da Categoria -->
                            <div v-if="form.payments.filter(p => p.category === cat.key).length > 0" class="flex justify-end pt-0.5">
                                <div class="bg-white/60 dark:bg-white/[0.02] backdrop-blur-sm border border-slate-200/50 dark:border-white/5 rounded-xl px-4 py-2 flex items-center gap-3 shadow-sm">
                                    <span class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Subtotal ({{ cat.label }})</span>
                                    <span class="text-[12px] font-black" :class="[
                                        cat.key === 'entrada' ? 'text-emerald-500' : 
                                        (cat.key === 'saldo' ? 'text-blue-500' : 
                                        (cat.key === 'taxa_contrato' ? 'text-amber-500' : 'text-purple-500'))
                                    ]">
                                        {{ maskCurrency(getCategoryTotal(cat.key)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div class="space-y-1.5 mt-4">
                    <label class="text-[10px] font-black text-slate-500 dark:text-gray-500 uppercase tracking-widest px-1">Observações / Detalhes Adicionais</label>
                    <textarea 
                        v-model="form.observations"
                        :rows="isViewMode ? 2 : 3"
                        :disabled="isViewMode"
                        placeholder="Insira detalhes complementares sobre a negociação..."
                        class="w-full bg-white/80 dark:bg-[#0f1219]/50 border border-slate-200/60 dark:border-white/10 rounded-2xl px-5 py-4 text-slate-900 dark:text-white font-medium text-xs focus:border-indigo-500/40 dark:focus:border-emerald-500/40 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-emerald-500/10 transition-all outline-none resize-none disabled:opacity-60 shadow-inner"
                    ></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-white/40 dark:border-white/10 bg-white/40 dark:bg-black/40 backdrop-blur-xl flex flex-col md:flex-row items-center gap-6 transition-all duration-300 relative z-20"
                 :class="{ 'py-4': isViewMode }">
                <div class="flex-1 flex gap-8 items-center">
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-widest">Total</span>
                        <span class="font-black text-slate-900 dark:text-white leading-none" :class="isViewMode ? 'text-lg' : 'text-xl'">{{ maskCurrency(form.total_value) }}</span>
                    </div>
                    <div class="w-px h-8 bg-slate-200 dark:bg-white/5 hidden md:block"></div>
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black uppercase tracking-widest" :class="isBalanceMatch ? 'text-emerald-500' : 'text-amber-500'">Saldo</span>
                        <span class="font-black leading-none" :class="[isBalanceMatch ? 'text-emerald-500/50' : 'text-amber-500', isViewMode ? 'text-lg' : 'text-xl']">
                            {{ Math.abs(remainingBalance) < 0.01 ? 'QUITADO' : maskCurrency(remainingBalance) }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 w-full md:w-auto">
                    <button 
                        @click="close"
                        class="px-8 py-3 bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-400 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white rounded-xl font-bold uppercase text-[10px] tracking-widest transition-all shadow-lg active:scale-95"
                    >
                        {{ isViewMode ? 'Fechar' : 'Cancelar' }}
                    </button>
                    
                    <!-- Botão de Aprovar (Apenas em ViewMode para propostas Pendentes) -->
                    <button 
                        v-if="isViewMode && service?.proposal?.status !== 'approved'"
                        @click="approveProposal"
                        :disabled="form.processing"
                        class="px-12 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl shadow-emerald-500/20 active:scale-95 transition-all disabled:opacity-20 flex items-center gap-3 border border-emerald-400/30"
                    >
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ form.processing ? 'Processando...' : 'APROVAR PROPOSTA' }}</span>
                    </button>

                    <!-- Selo de Aprovado -->
                    <div v-if="isViewMode && service?.proposal?.status === 'approved'" class="px-8 py-3 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-blue-400 font-black uppercase text-[10px] tracking-widest">PROPOSTA APROVADA</span>
                    </div>

                    <button 
                        v-if="!isViewMode"
                        @click="submit"
                        :disabled="form.processing || !isDownPaymentValid || !isBalanceMatch || !hasCpf"
                        class="px-12 py-3 text-white rounded-xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl active:scale-95 transition-all disabled:opacity-20 flex items-center gap-3"
                        :class="isEditMode ? 'bg-indigo-600 dark:bg-cyan-600 hover:bg-indigo-500 dark:hover:bg-cyan-500 shadow-indigo-500/20 dark:shadow-cyan-500/20' : 'bg-indigo-600 dark:bg-emerald-600 hover:bg-indigo-500 dark:hover:bg-emerald-500 shadow-indigo-500/20 dark:shadow-emerald-500/20'"
                    >
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? '...' : (isEditMode ? 'SALVAR ALTERAÇÕES' : 'SALVAR PROPOSTA') }}</span>
                    </button>
                </div>
            </div>

            <!-- Modal de Confirmação de Aprovação (Overlay Premium) -->
            <div v-if="isConfirmingApproval" class="absolute inset-0 z-[100] flex items-center justify-center bg-slate-900/60 dark:bg-[#0d1117]/90 backdrop-blur-sm transition-all duration-500">
                <div class="max-w-md w-full mx-4 bg-white dark:bg-[#161b22] border border-emerald-500/30 rounded-3xl p-8 shadow-2xl dark:shadow-[0_0_100px_rgba(16,185,129,0.1)] text-center transform scale-100 animate-in zoom-in-95 duration-300">
                    
                    <!-- Estado de Processamento (Animação) -->
                    <div v-if="isArtificialProcessing" class="space-y-6 py-4 animate-in fade-in duration-500">
                        <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
                            <!-- Anéis Giratórios -->
                            <div class="absolute inset-0 border-4 border-emerald-500/10 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-t-emerald-500 rounded-full animate-spin"></div>
                            <div class="absolute inset-3 border-4 border-b-cyan-500 rounded-full animate-spin-slow"></div>
                            
                            <!-- Ícone Central -->
                            <svg class="w-8 h-8 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-white uppercase tracking-tighter mb-1">Gerando Parcelas...</h3>
                            <p class="text-[10px] text-emerald-400/60 font-black uppercase tracking-[0.2em] animate-pulse">Configurando Contas a Receber</p>
                        </div>
                    </div>

                    <!-- Estado Inicial de Confirmação -->
                    <template v-else>
                        <div class="w-20 h-20 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg rotate-3 group transition-transform hover:rotate-0">
                            <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-2">Consolidar Venda?</h3>
                        <p class="text-slate-500 dark:text-gray-400 text-xs font-medium leading-relaxed mb-8 px-4">
                            Ao aprovar, o sistema irá gerar automaticamente todos os **lançamentos financeiros** e o status do atendimento será alterado para **"VENDA"**. Esta ação é definitiva.
                        </p>

                        <div class="flex flex-col gap-3">
                            <button 
                                @click="confirmApprove"
                                class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.3em] shadow-xl shadow-emerald-500/20 transition-all active:scale-95 flex items-center justify-center gap-3 border border-emerald-400/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>SIM, APROVAR AGORA</span>
                            </button>
                            
                            <button 
                                @click="isConfirmingApproval = false"
                                class="w-full py-4 bg-slate-50 dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 dark:text-gray-500 hover:text-slate-900 dark:hover:text-white rounded-2xl font-bold uppercase text-[10px] tracking-widest transition-all"
                            >
                                REVISAR DADOS
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Overlay de Alerta de Limite Premium -->
            <div v-if="isShowingLimitWarning" class="absolute inset-0 z-[150] flex items-center justify-center bg-slate-900/60 dark:bg-[#0d1117]/95 backdrop-blur-md transition-all duration-500">
                <div class="max-w-md w-full mx-4 bg-white dark:bg-[#161b22] border border-amber-500/30 rounded-3xl p-8 shadow-2xl dark:shadow-[0_0_100px_rgba(245,158,11,0.1)] text-center transform scale-100 animate-in zoom-in-95 duration-300">
                    <div class="w-20 h-20 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg rotate-3">
                        <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter mb-2">Aviso de Limite</h3>
                    <p class="text-slate-500 dark:text-gray-400 text-xs font-medium leading-relaxed mb-8 px-4">
                        {{ limitWarningMessage }}
                    </p>

                    <button 
                        @click="isShowingLimitWarning = false"
                        class="w-full py-4 bg-amber-600 hover:bg-amber-500 text-white rounded-2xl font-black uppercase text-[11px] tracking-[0.3em] shadow-xl shadow-amber-500/20 transition-all active:scale-95 flex items-center justify-center gap-3 border border-amber-400/20 uppercase"
                    >
                        <span>ENTENDI, CONTINUAR</span>
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(16, 185, 129, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(16, 185, 129, 0.4);
}

@keyframes toast-progress {
    from { width: 100%; }
    to { width: 0%; }
}
.animate-toast-progress {
    animation: toast-progress 4s linear forwards;
}
</style>
