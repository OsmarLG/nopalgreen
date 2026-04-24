export type UserRole = {
    id: number;
    name: string;
};

export type UserPermission = {
    id: number;
    name: string;
};

export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    attendance_starts_at?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    roles?: UserRole[];
    permissions?: UserPermission[];
    [key: string]: unknown;
};

export type Branding = {
    app_name: string;
    app_tagline: string;
    logo_url: string | null;
    favicon_url: string;
};

export type AttendanceSettings = {
    check_in_time: string;
    check_out_time: string;
    tolerance_minutes: number;
    absence_after_time: string;
    tardies_before_absence: number;
    work_days: string[];
};

export type Auth = {
    user: User | null;
    can: {
        viewUsers: boolean;
        createUsers: boolean;
        updateUsers: boolean;
        deleteUsers: boolean;
        viewRoles: boolean;
        createRoles: boolean;
        updateRoles: boolean;
        viewPermissions: boolean;
        updateBranding: boolean;
        viewUnits: boolean;
        createUnits: boolean;
        updateUnits: boolean;
        deleteUnits: boolean;
        viewSuppliers: boolean;
        createSuppliers: boolean;
        updateSuppliers: boolean;
        deleteSuppliers: boolean;
        viewCustomers: boolean;
        createCustomers: boolean;
        updateCustomers: boolean;
        deleteCustomers: boolean;
        viewSales: boolean;
        createSales: boolean;
        updateSales: boolean;
        deleteSales: boolean;
        viewRawMaterials: boolean;
        createRawMaterials: boolean;
        updateRawMaterials: boolean;
        deleteRawMaterials: boolean;
        viewProducts: boolean;
        createProducts: boolean;
        updateProducts: boolean;
        deleteProducts: boolean;
        viewPresentations: boolean;
        createPresentations: boolean;
        updatePresentations: boolean;
        deletePresentations: boolean;
        viewRecipes: boolean;
        createRecipes: boolean;
        updateRecipes: boolean;
        deleteRecipes: boolean;
        viewProductionOrders: boolean;
        createProductionOrders: boolean;
        updateProductionOrders: boolean;
        deleteProductionOrders: boolean;
        viewPurchases: boolean;
        createPurchases: boolean;
        updatePurchases: boolean;
        deletePurchases: boolean;
        viewInventoryAdjustments: boolean;
        createInventoryAdjustments: boolean;
        updateInventoryAdjustments: boolean;
        deleteInventoryAdjustments: boolean;
        viewInventoryTransfers: boolean;
        createInventoryTransfers: boolean;
        updateInventoryTransfers: boolean;
        deleteInventoryTransfers: boolean;
        viewInventoryMovements: boolean;
        viewReports: boolean;
        viewFinances: boolean;
        createFinances: boolean;
        updateFinances: boolean;
        deleteFinances: boolean;
        viewEmployees: boolean;
        markAttendance: boolean;
        manageAttendance: boolean;
    };
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
