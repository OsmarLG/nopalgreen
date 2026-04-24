import type { FinanceTransactionRecord } from '@/types';

export const formatFinanceType = (type: string): string => {
    const labels: Record<string, string> = {
        income: 'Ingreso',
        expense: 'Egreso',
        debt: 'Deuda',
        collection: 'Cobro',
        payment: 'Pago',
        loss: 'Perdida',
        refund: 'Reembolso',
    };

    return labels[type] ?? type;
};

export const formatFinanceStatus = (status: string): string => {
    const labels: Record<string, string> = {
        pending: 'Pendiente',
        posted: 'Aplicado',
        cancelled: 'Cancelado',
    };

    return labels[status] ?? status;
};

export const formatFinanceSource = (source: string): string => {
    const labels: Record<string, string> = {
        manual: 'Manual',
        purchase: 'Compra',
        sale: 'Venta',
        production: 'Produccion',
        waste: 'Merma',
    };

    return labels[source] ?? source;
};

export const financeDirectionBadgeClass = (direction: FinanceTransactionRecord['direction']): string => {
    return direction === 'in'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-amber-200 bg-amber-50 text-amber-700';
};
