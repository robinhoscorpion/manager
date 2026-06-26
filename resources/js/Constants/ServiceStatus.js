export const SERVICE_STATUS = {
    FILA: {
        value: 'queue',
        label: 'FILA',
        color: 'bg-orange-500',
        textColor: 'text-orange-500 dark:text-orange-400',
        bgColor: 'bg-orange-50 dark:bg-orange-500/10',
        borderColor: 'border-orange-500/30',
        icon: 'users'
    },
    MESA: {
        value: 'table',
        label: 'MESA',
        color: 'bg-cyan-500',
        textColor: 'text-cyan-600 dark:text-cyan-400',
        bgColor: 'bg-cyan-50 dark:bg-cyan-500/10',
        borderColor: 'border-cyan-500/30',
        icon: 'table'
    },
    REPROVADO: {
        value: 'rejected',
        label: 'REPROVADO',
        color: 'bg-red-500',
        textColor: 'text-red-600 dark:text-red-400',
        bgColor: 'bg-red-50 dark:bg-red-500/10',
        borderColor: 'border-red-500/30',
        icon: 'x-circle'
    },
    CANCELADO: {
        value: 'cancelled',
        label: 'CANCELADO',
        color: 'bg-gray-500',
        textColor: 'text-gray-600 dark:text-gray-400',
        bgColor: 'bg-gray-50 dark:bg-gray-500/10',
        borderColor: 'border-white/10',
        icon: 'door'
    },
    PENDENTE: {
        value: 'pending',
        label: 'PENDENTE',
        color: 'bg-amber-500',
        textColor: 'text-amber-600 dark:text-amber-400',
        bgColor: 'bg-amber-50 dark:bg-amber-500/10',
        borderColor: 'border-amber-500/30',
        icon: 'clock'
    },
    PROPOSTA: {
        value: 'proposal',
        label: 'PROPOSTA',
        color: 'bg-purple-500',
        textColor: 'text-purple-600 dark:text-purple-400',
        bgColor: 'bg-purple-50 dark:bg-purple-500/10',
        borderColor: 'border-purple-500/30',
        icon: 'document'
    },
    APROVADO: {
        value: 'approved',
        label: 'APROVADO',
        color: 'bg-emerald-500',
        textColor: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-emerald-50 dark:bg-emerald-500/10',
        borderColor: 'border-emerald-500/30',
        icon: 'money'
    },
    FINALIZADO: {
        value: 'completed',
        label: 'FINALIZADO',
        color: 'bg-blue-600',
        textColor: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-600/10',
        borderColor: 'border-blue-600/30',
        icon: 'check-double'
    }
};

export const getStatusMetadata = (statusValue) => {
    if (!statusValue) return SERVICE_STATUS.PENDENTE;

    const val = statusValue.toLowerCase();

    // Mapping English values to their descriptors
    if (val === 'queue' || val === 'fila') return SERVICE_STATUS.FILA;
    if (val === 'table' || val === 'mesa') return SERVICE_STATUS.MESA;
    if (val === 'rejected' || val === 'reprovado') return SERVICE_STATUS.REPROVADO;
    if (val === 'cancelled' || val === 'cancelado' || val === 'door') return SERVICE_STATUS.CANCELADO;
    if (val === 'pending' || val === 'pendente') return SERVICE_STATUS.PENDENTE;
    if (val === 'proposal' || val === 'proposta') return SERVICE_STATUS.PROPOSTA;
    if (val === 'approved' || val === 'aprovado' || val === 'money' || val === 'venda') return SERVICE_STATUS.APROVADO;
    if (val === 'completed' || val === 'finalizado') return SERVICE_STATUS.FINALIZADO;
    
    return SERVICE_STATUS.PENDENTE;
};
