import { Link, usePage } from '@inertiajs/react';
import { ArrowRightLeft, BarChart3, BookOpenText, Boxes, ClipboardList, Factory, Fingerprint, HandCoins, KeyRound, LayoutGrid, Package, PackageMinus, ReceiptText, Rows3, Ruler, ShieldCheck, ShoppingCart, TimerReset, Truck, UserRound, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as editAttendanceMark } from '@/routes/attendance-mark';
import { edit as editAttendanceSettings } from '@/routes/attendance-settings';
import { edit as editBranding } from '@/routes/branding';
import { index as customersIndex } from '@/routes/customers';
import { index as documentationIndex } from '@/routes/documentation';
import { index as employeesIndex } from '@/routes/employees';
import { index as financesIndex } from '@/routes/finances';
import { index as inventoryAdjustmentsIndex } from '@/routes/inventory-adjustments';
import { index as inventoryMovementsIndex } from '@/routes/inventory-movements';
import { index as inventoryStocksIndex } from '@/routes/inventory-stocks';
import { index as inventoryTransfersIndex } from '@/routes/inventory-transfers';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as posIndex } from '@/routes/pos';
import { index as presentationsIndex } from '@/routes/presentations';
import { index as productionOrdersIndex } from '@/routes/production-orders';
import { index as productsIndex } from '@/routes/products';
import { index as purchasesIndex } from '@/routes/purchases';
import { index as rawMaterialsIndex } from '@/routes/raw-materials';
import { index as recipesIndex } from '@/routes/recipes';
import { index as reportsIndex } from '@/routes/reports';
import { index as rolesIndex } from '@/routes/roles';
import { index as salesIndex } from '@/routes/sales';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as unitsIndex } from '@/routes/units';
import { index as usersIndex } from '@/routes/users';
import type { Auth, NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;

    const platformNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Documentacion',
            href: documentationIndex(),
            icon: BookOpenText,
        },
        ...(auth.can.viewReports
            ? [
                  {
                      title: 'Reportes',
                      href: reportsIndex(),
                      icon: BarChart3,
                  },
              ]
            : []),
    ];

    const configurationNavItems: NavItem[] = [
        ...(auth.can.viewUsers
            ? [
                  {
                      title: 'Usuarios',
                      href: usersIndex(),
                      icon: Users,
                  },
              ]
            : []),
        ...(auth.can.viewRoles
            ? [
                  {
                      title: 'Roles',
                      href: rolesIndex(),
                      icon: ShieldCheck,
                  },
              ]
            : []),
        ...(auth.can.viewPermissions
            ? [
                  {
                      title: 'Permisos',
                      href: permissionsIndex(),
                      icon: KeyRound,
                  },
              ]
            : []),
        ...(auth.can.viewEmployees
            ? [
                  {
                      title: 'Empleados',
                      href: employeesIndex(),
                      icon: Fingerprint,
                  },
              ]
            : []),
        ...(auth.can.viewUnits
            ? [
                  {
                      title: 'Unidades',
                      href: unitsIndex(),
                      icon: Ruler,
                  },
              ]
            : []),
        ...(auth.can.manageAttendance
            ? [
                  {
                      title: 'Asistencia',
                      href: editAttendanceSettings(),
                      icon: TimerReset,
                  },
              ]
            : []),
        ...(auth.can.updateBranding
            ? [
                  {
                      title: 'Marca',
                      href: editBranding(),
                      icon: Package,
                  },
              ]
            : []),
    ];

    const inventoryNavItems: NavItem[] = [
        ...(auth.can.viewSuppliers
            ? [
                  {
                      title: 'Proveedores',
                      href: suppliersIndex(),
                      icon: Truck,
                  },
              ]
            : []),
        ...(auth.can.viewRawMaterials
            ? [
                  {
                      title: 'Materias primas',
                      href: rawMaterialsIndex(),
                      icon: Boxes,
                  },
              ]
            : []),
        ...(auth.can.viewProducts
            ? [
                  {
                      title: 'Productos',
                      href: productsIndex(),
                      icon: Boxes,
                  },
              ]
            : []),
        ...(auth.can.viewPresentations
            ? [
                  {
                      title: 'Presentaciones',
                      href: presentationsIndex(),
                      icon: Package,
                  },
              ]
            : []),
        ...(auth.can.viewInventoryMovements
            ? [
                  {
                      title: 'Existencias',
                      href: inventoryStocksIndex(),
                      icon: Package,
                  },
              ]
            : []),
        ...(auth.can.viewInventoryAdjustments
            ? [
                  {
                      title: 'Ajustes y mermas',
                      href: inventoryAdjustmentsIndex(),
                      icon: PackageMinus,
                  },
              ]
            : []),
        ...(auth.can.viewInventoryTransfers
            ? [
                  {
                      title: 'Transferencias',
                      href: inventoryTransfersIndex(),
                      icon: ArrowRightLeft,
                  },
              ]
            : []),
        ...(auth.can.viewInventoryMovements
            ? [
                  {
                      title: 'Movimientos',
                      href: inventoryMovementsIndex(),
                      icon: Rows3,
                  },
              ]
            : []),
    ];

    const productionNavItems: NavItem[] = [
        ...(auth.can.viewRecipes
            ? [
                  {
                      title: 'Recetas',
                      href: recipesIndex(),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(auth.can.viewProductionOrders
            ? [
                  {
                      title: 'Ordenes de produccion',
                      href: productionOrdersIndex(),
                      icon: Factory,
                  },
              ]
            : []),
    ];

    const purchasesNavItems: NavItem[] = [
        ...(auth.can.viewPurchases
            ? [
                  {
                      title: 'Compras',
                      href: purchasesIndex(),
                      icon: ReceiptText,
                  },
              ]
            : []),
    ];

    const salesNavItems: NavItem[] = [
        ...(auth.can.createSales
            ? [
                  {
                      title: 'POS',
                      href: posIndex(),
                      icon: ShoppingCart,
                  },
              ]
            : []),
        ...(auth.can.viewCustomers
            ? [
                  {
                      title: 'Clientes',
                      href: customersIndex(),
                      icon: UserRound,
                  },
              ]
            : []),
        ...(auth.can.viewSales
            ? [
                  {
                      title: 'Ventas',
                      href: salesIndex(),
                      icon: ShoppingCart,
                  },
              ]
            : []),
    ];

    const financeNavItems: NavItem[] = [
        ...(auth.can.viewFinances
            ? [
                  {
                      title: 'Finanzas',
                      href: financesIndex(),
                      icon: HandCoins,
                  },
              ]
            : []),
    ];

    const personalNavItems: NavItem[] = [
        ...(auth.can.markAttendance
            ? [
                  {
                      title: 'Mi asistencia',
                      href: editAttendanceMark(),
                      icon: Fingerprint,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="[--sidebar:#fffdfa] [--sidebar-foreground:#1f2937] [--sidebar-border:#e7e5e4] [--sidebar-accent:#f5faed] [--sidebar-accent-foreground:#2c5d1f] [--sidebar-ring:#f4c430] [&_[data-sidebar=sidebar]]:bg-[#fffdfa] [&_[data-sidebar=sidebar]]:text-stone-800 [&_[data-sidebar=sidebar]]:border-stone-200"
        >
            <SidebarHeader className="bg-[#fffdfa]">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={{
                                children: 'NopalGreen',
                                className:
                                    'border border-stone-200 bg-[#fffdfa] text-stone-800 shadow-sm',
                            }}
                            asChild
                        >
                            <Link href={dashboard()}>
                                <AppLogo variant="sidebar" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="bg-[#fffdfa]">
                <NavMain items={platformNavItems} title="Platform" />
                {personalNavItems.length > 0 && (
                    <NavMain items={personalNavItems} title="Personal" />
                )}
                {salesNavItems.length > 0 && (
                    <NavMain items={salesNavItems} title="Ventas" />
                )}
                {financeNavItems.length > 0 && (
                    <NavMain items={financeNavItems} title="Finanzas" />
                )}
                {purchasesNavItems.length > 0 && (
                    <NavMain items={purchasesNavItems} title="Compras" />
                )}
                {inventoryNavItems.length > 0 && (
                    <NavMain items={inventoryNavItems} title="Inventario" />
                )}
                {productionNavItems.length > 0 && (
                    <NavMain items={productionNavItems} title="Produccion" />
                )}
                {configurationNavItems.length > 0 && (
                    <NavMain
                        items={configurationNavItems}
                        title="Configuracion"
                    />
                )}
            </SidebarContent>

            <SidebarFooter className="border-t border-stone-200 bg-[#fffdfa]">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
