export const formatMoney = (value: number | string | null | undefined): string => {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
};
