export const SERVICE_STATUS = {
    MESA: {
        value: 'table',
        label: 'MESA',
        color: 'bg-cyan-500',
        textColor: 'text-cyan-400',
        borderColor: 'border-cyan-500/30',
        icon: 'table'
    },
    REPROVADO: {
        value: 'rejected',
        label: 'REPROVADO',
        color: 'bg-red-500',
        textColor: 'text-red-400',
        borderColor: 'border-red-500/30',
        icon: 'x-circle'
    },
    CANCELADO: {
        value: 'cancelled',
        label: 'CANCELADO',
        color: 'bg-gray-500',
        textColor: 'text-gray-400',
        borderColor: 'border-white/10',
        icon: 'door'
    },
    PENDENTE: {
        value: 'pending',
        label: 'PENDENTE',
        color: 'bg-amber-500',
        textColor: 'text-amber-400',
        borderColor: 'border-amber-500/30',
        icon: 'clock'
    },
    PROPOSTA: {
        value: 'proposal',
        label: 'PROPOSTA',
        color: 'bg-purple-500',
        textColor: 'text-purple-400',
        borderColor: 'border-purple-500/30',
        icon: 'document'
    },
    APROVADO: {
        value: 'approved',
        label: 'APROVADO',
        color: 'bg-emerald-500',
        textColor: 'text-emerald-400',
        borderColor: 'border-emerald-500/30',
        icon: 'money'
    },
    FINALIZADO: {
        value: 'completed',
        label: 'FINALIZADO',
        color: 'bg-blue-600',
        textColor: 'text-blue-400',
        borderColor: 'border-blue-600/30',
        icon: 'check-double'
    }
};

export const getStatusMetadata = (statusValue) => {
    if (!statusValue) return SERVICE_STATUS.PENDENTE;

    const val = statusValue.toLowerCase();

    // Mapping English values to their descriptors
    if (val === 'table' || val === 'mesa') return SERVICE_STATUS.MESA;
    if (val === 'rejected' || val === 'reprovado') return SERVICE_STATUS.REPROVADO;
    if (val === 'cancelled' || val === 'cancelado' || val === 'door') return SERVICE_STATUS.CANCELADO;
    if (val === 'pending' || val === 'pendente') return SERVICE_STATUS.PENDENTE;
    if (val === 'proposal' || val === 'proposta') return SERVICE_STATUS.PROPOSTA;
    if (val === 'approved' || val === 'aprovado' || val === 'money' || val === 'venda') return SERVICE_STATUS.APROVADO;
    if (val === 'completed' || val === 'finalizado') return SERVICE_STATUS.FINALIZADO;
    
    return SERVICE_STATUS.PENDENTE;
};
