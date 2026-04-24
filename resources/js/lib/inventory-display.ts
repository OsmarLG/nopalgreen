const supplySourceLabels: Record<string, string> = {
    production: 'Produccion',
    supplier: 'Proveedor',
    mixed: 'Mixto',
};

const productTypeLabels: Record<string, string> = {
    finished: 'Terminado',
    intermediate: 'Intermedio',
};

const presentationOwnerLabels: Record<string, string> = {
    raw_material: 'Materia Prima',
    product: 'Producto',
};

const recipeItemTypeLabels: Record<string, string> = {
    raw_material: 'Materia Prima',
    product: 'Producto',
};

const productionOrderStatusLabels: Record<string, string> = {
    draft: 'Borrador',
    planned: 'Planeada',
    in_progress: 'En Proceso',
    completed: 'Completada',
    cancelled: 'Cancelada',
};

const purchaseStatusLabels: Record<string, string> = {
    draft: 'Borrador',
    received: 'Recibida',
    cancelled: 'Cancelada',
};

const saleTypeLabels: Record<string, string> = {
    direct: 'Directa',
    delivery: 'Entrega',
};

const saleStatusLabels: Record<string, string> = {
    draft: 'Borrador',
    assigned: 'Asignada',
    completed: 'Completada',
    cancelled: 'Cancelada',
};

const inventoryMovementTypeLabels: Record<string, string> = {
    purchase: 'Compra',
    production_output: 'Salida de Produccion',
    production_consumption: 'Consumo de Produccion',
    adjustment: 'Ajuste',
    return: 'Devolucion',
    waste: 'Merma',
    transfer: 'Transferencia',
    sale: 'Venta',
    sale_dispatch: 'Salida a Reparto',
};

const warehouseTypeLabels: Record<string, string> = {
    raw_material: 'Materia Prima',
    finished_product: 'Producto Terminado',
    mixed: 'Mixto',
};

export const formatSupplySource = (value: string): string => {
    return supplySourceLabels[value] ?? value;
};

export const formatProductType = (value: string): string => {
    return productTypeLabels[value] ?? value;
};

export const formatPresentationOwnerType = (value: string): string => {
    return presentationOwnerLabels[value] ?? value;
};

export const formatRecipeItemType = (value: string): string => {
    return recipeItemTypeLabels[value] ?? value;
};

export const formatProductionOrderStatus = (value: string): string => {
    return productionOrderStatusLabels[value] ?? value;
};

export const formatPurchaseStatus = (value: string): string => {
    return purchaseStatusLabels[value] ?? value;
};

export const formatSaleType = (value: string): string => {
    return saleTypeLabels[value] ?? value;
};

export const formatSaleStatus = (value: string): string => {
    return saleStatusLabels[value] ?? value;
};

export const formatInventoryMovementType = (value: string): string => {
    return inventoryMovementTypeLabels[value] ?? value;
};

export const formatWarehouseType = (value: string): string => {
    return warehouseTypeLabels[value] ?? value;
};

export const activeLabel = (value: boolean): string => {
    return value ? 'Activo' : 'Inactivo';
};

export const activeBadgeClass = (value: boolean): string => {
    return value
        ? 'bg-nopal-50 text-nopal-700 border-nopal-200'
        : 'bg-stone-100 text-stone-600 border-stone-200';
};

export const productionOrderStatusBadgeClass = (value: string): string => {
    return (
        {
            draft: 'bg-stone-100 text-stone-700 border-stone-200',
            planned: 'bg-amber-50 text-amber-800 border-amber-200',
            in_progress: 'bg-sky-50 text-sky-800 border-sky-200',
            completed: 'bg-nopal-50 text-nopal-700 border-nopal-200',
            cancelled: 'bg-rose-50 text-rose-700 border-rose-200',
        }[value] ?? 'bg-stone-100 text-stone-700 border-stone-200'
    );
};

export const purchaseStatusBadgeClass = (value: string): string => {
    return (
        {
            draft: 'bg-stone-100 text-stone-700 border-stone-200',
            received: 'bg-nopal-50 text-nopal-700 border-nopal-200',
            cancelled: 'bg-rose-50 text-rose-700 border-rose-200',
        }[value] ?? 'bg-stone-100 text-stone-700 border-stone-200'
    );
};

export const saleStatusBadgeClass = (value: string): string => {
    return (
        {
            draft: 'bg-stone-100 text-stone-700 border-stone-200',
            assigned: 'bg-sky-50 text-sky-800 border-sky-200',
            completed: 'bg-nopal-50 text-nopal-700 border-nopal-200',
            cancelled: 'bg-rose-50 text-rose-700 border-rose-200',
        }[value] ?? 'bg-stone-100 text-stone-700 border-stone-200'
    );
};

export const inventoryDirectionBadgeClass = (value: string): string => {
    return value === 'in'
        ? 'bg-nopal-50 text-nopal-700 border-nopal-200'
        : 'bg-amber-50 text-amber-800 border-amber-200';
};

export const inventoryDirectionLabel = (value: string): string => {
    return value === 'in' ? 'Entrada' : 'Salida';
};
