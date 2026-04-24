export type SelectOption = {
    id: number;
    name: string;
};

export type UnitOption = SelectOption & {
    code: string;
};

export type UnitRecord = {
    id: number;
    name: string;
    code: string;
    decimal_places: number;
    is_active: boolean;
    can_delete?: boolean;
    in_use?: boolean;
};

export type SupplierRecord = {
    id: number;
    name: string;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
    can_delete?: boolean;
    in_use?: boolean;
};

export type RawMaterialRecord = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    presentations_count?: number;
    base_unit?: UnitOption;
    supplier_links?: Array<{
        supplier_id: number;
        supplier: SelectOption;
    }>;
    can_delete?: boolean;
    in_use?: boolean;
};

export type ProductRecord = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    supply_source: string;
    product_type: string;
    sale_price: string;
    presentations_count?: number;
    recipes_count?: number;
    base_unit?: UnitOption;
    supplier_links?: Array<{
        supplier_id: number;
        supplier: SelectOption;
    }>;
    can_delete?: boolean;
    in_use?: boolean;
};

export type CustomerRecord = {
    id: number;
    name: string;
    customer_type: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
    can_delete?: boolean;
    in_use?: boolean;
};

export type PresentationRecord = {
    id: number;
    owner_type: 'raw_material' | 'product';
    owner_type_label: string;
    owner_id: number;
    owner_name: string;
    name: string;
    quantity: string;
    barcode: string | null;
    is_active: boolean;
    unit: UnitOption;
    can_delete?: boolean;
    in_use?: boolean;
};

export type RecipeItemRecord = {
    id?: number;
    item_type: 'raw_material' | 'product';
    item_id: number;
    item_name: string;
    quantity: string;
    unit: UnitOption;
    sort_order: number;
};

export type RecipeRecord = {
    id: number;
    name: string;
    version: number;
    yield_quantity: string;
    is_active: boolean;
    product: SelectOption;
    yield_unit: UnitOption;
    items_count?: number;
    items?: RecipeItemRecord[];
    can_delete?: boolean;
    in_use?: boolean;
};

export type ProductionOrderConsumptionRecord = {
    id?: number;
    item_type: 'raw_material' | 'product';
    item_id: number;
    item_name: string;
    planned_quantity: string;
    consumed_quantity: string;
    unit: UnitOption;
};

export type ProductionRecipeOption = {
    id: number;
    name: string;
    version: number;
    product: SelectOption;
    yield_quantity: string;
    yield_unit: UnitOption;
    items: Array<{
        item_type: 'raw_material' | 'product';
        item_id: number;
        item_name: string;
        quantity: string;
        unit: UnitOption;
        sort_order: number;
    }>;
};

export type ProductionOrderRecord = {
    id: number;
    folio: string;
    planned_quantity: string;
    produced_quantity: string;
    status: string;
    scheduled_for: string | null;
    started_at: string | null;
    finished_at: string | null;
    notes: string | null;
    product: SelectOption;
    recipe: {
        id: number;
        name: string;
        version: number;
    };
    unit: UnitOption;
    consumptions?: ProductionOrderConsumptionRecord[];
    can_delete?: boolean;
};

export type PurchasePresentationOption = {
    id: number;
    name: string;
    quantity: string;
    unit: UnitOption;
};

export type PurchaseCatalogOption = SelectOption & {
    presentations: PurchasePresentationOption[];
};

export type SaleCatalogOption = SelectOption & {
    sale_price: string;
    presentations: PurchasePresentationOption[];
};

export type PurchaseItemRecord = {
    id?: number;
    item_type: 'raw_material' | 'product';
    item_id: number;
    item_name: string;
    presentation_type: 'raw_material_presentation' | 'product_presentation' | null;
    presentation_id: number | null;
    presentation_name: string | null;
    quantity: string;
    unit_cost: string;
    total: string;
};

export type PurchaseRecord = {
    id: number;
    folio: string;
    status: string;
    purchased_at: string | null;
    notes: string | null;
    supplier: SelectOption;
    items?: PurchaseItemRecord[];
    can_delete?: boolean;
};

export type SaleItemRecord = {
    id?: number;
    product_id: number;
    product_name: string;
    presentation_id: number | null;
    presentation_name: string | null;
    quantity: string;
    sold_quantity: string;
    returned_quantity: string;
    catalog_price: string;
    unit_price: string;
    discount_total: string;
    line_total: string;
    discount_note: string | null;
};

export type SaleRecord = {
    id: number;
    folio: string;
    sale_type: string;
    status: string;
    sale_date: string | null;
    delivery_date: string | null;
    completed_at: string | null;
    subtotal: string;
    discount_total: string;
    total: string;
    notes: string | null;
    customer: SelectOption | null;
    delivery_user: SelectOption | null;
    items?: SaleItemRecord[];
    can_delete?: boolean;
};

export type InventoryMovementRecord = {
    id: number;
    item_type: 'raw_material' | 'product';
    item_name: string;
    movement_type: string;
    direction: 'in' | 'out';
    quantity: string;
    unit_cost: string | null;
    moved_at: string;
    notes: string | null;
    warehouse: SelectOption & {
        code: string;
        type: string;
    };
    reference_label: string | null;
};

export type InventoryStockRecord = {
    item_type: 'raw_material' | 'product';
    item_name: string;
    warehouse: SelectOption & {
        code: string;
        type: string;
    };
    balance: string;
};

export type InventoryAdjustmentRecord = {
    id: number;
    warehouse_id?: number;
    item_type: 'raw_material' | 'product';
    item_id?: number;
    item_name: string;
    movement_type: string;
    direction: 'in' | 'out';
    quantity: string;
    unit_cost: string | null;
    moved_at: string;
    notes: string | null;
    warehouse: SelectOption & {
        code?: string;
    };
    can_delete?: boolean;
};

export type InventoryTransferRecord = {
    id: number;
    item_type: 'raw_material' | 'product';
    item_id?: number;
    item_name: string;
    quantity: string;
    unit_cost: string | null;
    transferred_at: string;
    notes: string | null;
    source_warehouse: SelectOption & {
        code?: string;
    };
    destination_warehouse: SelectOption & {
        code?: string;
    };
    can_delete?: boolean;
};

export type FinanceTransactionRecord = {
    id: number;
    folio: string;
    transaction_type: string;
    direction: 'in' | 'out';
    source: string;
    concept: string;
    detail: string | null;
    amount: string;
    status: string;
    is_manual: boolean;
    affects_balance: boolean;
    occurred_at: string;
    notes: string | null;
    creator: SelectOption | null;
    can_edit: boolean;
    can_delete: boolean;
};

export type EmployeeRecord = {
    id: number;
    name: string;
    username: string;
    email: string;
    attendance_starts_at: string | null;
    today_status: string;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number;
    tardies: number;
    absences: number;
    absence_equivalents: number;
    devices_count: number;
};

export type EmployeeDeviceRecord = {
    id: number;
    device_name: string;
    browser_name: string | null;
    platform_name: string | null;
    last_ip: string | null;
    last_seen_at: string | null;
};

export type AttendanceRecordView = {
    id: number;
    attendance_date: string;
    expected_check_in_at: string;
    expected_check_out_at: string;
    absence_after_at: string;
    tolerance_minutes: number;
    check_in_at: string | null;
    check_out_at: string | null;
    check_in_status: string;
    check_out_status: string;
    late_minutes: number;
    early_leave_minutes: number;
    entry_code: string | null;
    exit_code: string | null;
    check_in_device: EmployeeDeviceRecord | null;
    check_out_device: EmployeeDeviceRecord | null;
    live_status: string;
};
