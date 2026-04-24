import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatSaleStatus, formatSaleType } from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { index as posIndex, store as posStore } from '@/routes/pos';
import { index as salesIndex } from '@/routes/sales';
import type { SaleCatalogOption, SelectOption } from '@/types';

const STORAGE_KEY = 'nopalgreen:pos-tabs:v1';
const NO_CUSTOMER_VALUE = '__none__';
const NO_DELIVERY_USER_VALUE = '__none__';

type PosItem = {
    product_id: string;
    presentation_id: string;
    quantity: string;
    sold_quantity: string;
    returned_quantity: string;
    catalog_price: string;
    unit_price: string;
    discount_note: string;
};

type PosTab = {
    id: string;
    label: string;
    customer_id: string;
    delivery_user_id: string;
    sale_type: string;
    status: string;
    sale_date: string;
    delivery_date: string;
    completed_at: string;
    notes: string;
    items: PosItem[];
};

type SalePayload = {
    customer_id: number | null;
    delivery_user_id: number | null;
    sale_type: string;
    status: string;
    sale_date: string | null;
    delivery_date: string | null;
    completed_at: string | null;
    notes: string;
    items: Array<{
        product_id: number;
        presentation_id: number;
        quantity: number;
        sold_quantity: number;
        returned_quantity: number;
        catalog_price: number;
        unit_price: number;
        discount_note: string | null;
    }>;
};

const nowDateTime = (): string => new Date().toISOString().slice(0, 16);

const toNumber = (value: string): number => Number(value || '0');

const findProduct = (products: SaleCatalogOption[], productId: string): SaleCatalogOption | undefined => {
    return products.find((product) => String(product.id) === productId);
};

const defaultItem = (products: SaleCatalogOption[]): PosItem => {
    const product = products[0];
    const presentation = product?.presentations[0];

    return {
        product_id: String(product?.id ?? ''),
        presentation_id: String(presentation?.id ?? ''),
        quantity: '',
        sold_quantity: '',
        returned_quantity: '',
        catalog_price: product?.sale_price ?? '0',
        unit_price: product?.sale_price ?? '0',
        discount_note: '',
    };
};

const createBlankTab = (products: SaleCatalogOption[], label: string): PosTab => ({
    id: crypto.randomUUID(),
    label,
    customer_id: '',
    delivery_user_id: '',
    sale_type: 'direct',
    status: 'completed',
    sale_date: nowDateTime(),
    delivery_date: '',
    completed_at: nowDateTime(),
    notes: '',
    items: [defaultItem(products)],
});

const allowedStatusesForSaleType = (saleType: string, statuses: string[]): string[] => {
    if (saleType === 'direct') {
        return statuses.filter((status) => status === 'completed');
    }

    return statuses.filter((status) => status === 'assigned' || status === 'completed');
};

const loadInitialTabs = (products: SaleCatalogOption[]): PosTab[] => {
    if (typeof window === 'undefined') {
        return [createBlankTab(products, 'Venta 1')];
    }

    const storedTabs = window.localStorage.getItem(STORAGE_KEY);

    if (storedTabs === null) {
        return [createBlankTab(products, 'Venta 1')];
    }

    try {
        const parsedTabs = JSON.parse(storedTabs) as PosTab[];

        if (Array.isArray(parsedTabs) && parsedTabs.length > 0) {
            return parsedTabs;
        }
    } catch {
        window.localStorage.removeItem(STORAGE_KEY);
    }

    return [createBlankTab(products, 'Venta 1')];
};

export default function PosIndex({
    customers,
    deliveryUsers,
    products,
    saleTypes,
    statuses,
}: {
    customers: SelectOption[];
    deliveryUsers: SelectOption[];
    products: SaleCatalogOption[];
    saleTypes: string[];
    statuses: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;
    const [tabs, setTabs] = useState<PosTab[]>(() => loadInitialTabs(products));
    const [activeTabId, setActiveTabId] = useState<string>('');
    const form = useForm<SalePayload>({
        customer_id: null,
        delivery_user_id: null,
        sale_type: 'direct',
        status: 'completed',
        sale_date: null,
        delivery_date: null,
        completed_at: null,
        notes: '',
        items: [],
    });

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(tabs));
    }, [tabs]);

    const resolvedActiveTabId = activeTabId || tabs[0]?.id || '';
    const activeTab = tabs.find((tab) => tab.id === resolvedActiveTabId) ?? tabs[0] ?? null;

    const updateActiveTab = (updater: (tab: PosTab) => PosTab): void => {
        setTabs((currentTabs) => currentTabs.map((tab) => (tab.id === resolvedActiveTabId ? updater(tab) : tab)));
    };

    const createNextTab = (): void => {
        const nextTab = createBlankTab(products, `Venta ${tabs.length + 1}`);
        setTabs((currentTabs) => [...currentTabs, nextTab]);
        setActiveTabId(nextTab.id);
        form.clearErrors();
    };

    const closeTab = (tabId: string): void => {
        if (tabs.length === 1) {
            const nextTab = createBlankTab(products, 'Venta 1');
            setTabs([nextTab]);
            setActiveTabId(nextTab.id);
            form.clearErrors();

            return;
        }

        const nextTabs = tabs.filter((tab) => tab.id !== tabId);
        setTabs(nextTabs);

        if (activeTabId === tabId) {
            setActiveTabId(nextTabs[0]?.id ?? '');
            form.clearErrors();
        }
    };

    const syncProductSelection = (itemIndex: number, productId: string): void => {
        updateActiveTab((tab) => {
            const product = findProduct(products, productId);
            const presentation = product?.presentations[0];

            return {
                ...tab,
                items: tab.items.map((item, index) => (
                    index === itemIndex
                        ? {
                              ...item,
                              product_id: productId,
                              presentation_id: String(presentation?.id ?? ''),
                              catalog_price: product?.sale_price ?? '0',
                              unit_price: item.unit_price === '' || item.unit_price === item.catalog_price ? product?.sale_price ?? '0' : item.unit_price,
                          }
                        : item
                )),
            };
        });
    };

    const submitCurrentTab = (): void => {
        if (activeTab === null) {
            return;
        }

        const isDirectSale = activeTab.sale_type === 'direct';
        const payload: SalePayload = {
            customer_id: activeTab.customer_id === '' || activeTab.customer_id === NO_CUSTOMER_VALUE ? null : Number(activeTab.customer_id),
            delivery_user_id: activeTab.delivery_user_id === '' || activeTab.delivery_user_id === NO_DELIVERY_USER_VALUE ? null : Number(activeTab.delivery_user_id),
            sale_type: activeTab.sale_type,
            status: activeTab.status,
            sale_date: activeTab.sale_date === '' ? null : activeTab.sale_date,
            delivery_date: activeTab.delivery_date === '' ? null : activeTab.delivery_date,
            completed_at: activeTab.completed_at === '' ? null : activeTab.completed_at,
            notes: activeTab.notes,
            items: activeTab.items.map((item) => ({
                product_id: Number(item.product_id),
                presentation_id: Number(item.presentation_id),
                quantity: Number(item.quantity),
                sold_quantity: isDirectSale ? Number(item.quantity || 0) : Number(item.sold_quantity || 0),
                returned_quantity: isDirectSale ? 0 : Number(item.returned_quantity || 0),
                catalog_price: Number(item.catalog_price),
                unit_price: Number(item.unit_price),
                discount_note: item.discount_note === '' ? null : item.discount_note,
            })),
        };

        form.transform(() => payload);
        form.post(posStore.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setTabs((currentTabs) => {
                    const remainingTabs = currentTabs.filter((tab) => tab.id !== activeTab.id);
                    const nextTab = createBlankTab(products, `Venta ${remainingTabs.length + 1}`);
                    setActiveTabId(nextTab.id);

                    return [...remainingTabs, nextTab];
                });
                form.clearErrors();
            },
        });
    };

    if (activeTab === null) {
        return null;
    }

    const isDirectSale = activeTab.sale_type === 'direct';
    const canLiquidateDelivery = activeTab.sale_type === 'delivery' && activeTab.status === 'completed';
    const availableStatuses = allowedStatusesForSaleType(activeTab.sale_type, statuses);
    const totals = activeTab.items.reduce(
        (accumulator, item) => {
            const soldQuantity = isDirectSale ? toNumber(item.quantity) : toNumber(item.sold_quantity);
            const subtotal = toNumber(item.catalog_price) * soldQuantity;
            const total = toNumber(item.unit_price) * soldQuantity;

            return {
                subtotal: accumulator.subtotal + subtotal,
                discountTotal: accumulator.discountTotal + Math.max(subtotal - total, 0),
                total: accumulator.total + total,
            };
        },
        { subtotal: 0, discountTotal: 0, total: 0 },
    );

    return (
        <>
            <Head title="POS" />
            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading title="POS" description="Captura ventas rapidas sin entrar al modulo completo de ventas." />
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" onClick={createNextTab}>
                                <Plus className="mr-2 size-4" />
                                Nueva cuenta
                            </Button>
                            <Button asChild variant="ghost">
                                <Link href={salesIndex()}>Ver ventas</Link>
                            </Button>
                        </div>
                    </div>

                    {typeof status === 'string' && status !== '' && (
                        <div className="mt-4 rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">{status}</div>
                    )}
                </div>

                <div className="rounded-[2rem] border border-stone-200 bg-white p-4 shadow-sm">
                    <div className="flex flex-wrap gap-2">
                        {tabs.map((tab) => (
                            <div key={tab.id} className={`flex items-center rounded-full border px-3 py-2 text-sm ${tab.id === resolvedActiveTabId ? 'border-nopal-300 bg-nopal-50 text-nopal-700' : 'border-stone-200 bg-white text-stone-600'}`}>
                                <button
                                    type="button"
                                    className="max-w-44 truncate"
                                    onClick={() => {
                                        setActiveTabId(tab.id);
                                        form.clearErrors();
                                    }}
                                >
                                    {tab.label}
                                </button>
                                <button type="button" className="ml-2 rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700" onClick={() => closeTab(tab.id)} aria-label={`Cerrar ${tab.label}`}>
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.8fr)_22rem]">
                    <div className="space-y-6 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <div className="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_minmax(0,0.8fr)]">
                            <div className="grid gap-2">
                                <Label htmlFor="tab-label" className="font-semibold text-nopal-700">Nombre de cuenta</Label>
                                <Input
                                    id="tab-label"
                                    value={activeTab.label}
                                    onChange={(event) => updateActiveTab((tab) => ({ ...tab, label: event.target.value }))}
                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sale_type" className="font-semibold text-nopal-700">Tipo</Label>
                                <Select
                                    value={activeTab.sale_type}
                                    onValueChange={(value) =>
                                        updateActiveTab((tab) => ({
                                            ...tab,
                                            sale_type: value,
                                            status: value === 'direct' ? 'completed' : 'assigned',
                                            delivery_user_id: value === 'direct' ? '' : tab.delivery_user_id,
                                            delivery_date: value === 'direct' ? '' : tab.delivery_date,
                                            completed_at: value === 'direct' ? nowDateTime() : '',
                                            items: tab.items.map((item) => ({
                                                ...item,
                                                sold_quantity: value === 'direct' ? item.quantity : '',
                                                returned_quantity: '0',
                                            })),
                                        }))
                                    }
                                >
                                    <SelectTrigger id="sale_type" className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                                        <SelectValue placeholder="Tipo de venta" />
                                    </SelectTrigger>
                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                        {saleTypes.map((saleType) => (
                                            <SelectItem key={saleType} value={saleType}>
                                                {formatSaleType(saleType)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status" className="font-semibold text-nopal-700">Estado</Label>
                                <Select
                                    value={activeTab.status}
                                    onValueChange={(value) =>
                                        updateActiveTab((tab) => ({
                                            ...tab,
                                            status: value,
                                            completed_at: value === 'completed' ? (tab.completed_at || nowDateTime()) : '',
                                            items: tab.items.map((item) => ({
                                                ...item,
                                                sold_quantity: value === 'completed' ? item.sold_quantity : '',
                                                returned_quantity: value === 'completed' ? item.returned_quantity : '0',
                                            })),
                                        }))
                                    }
                                >
                                    <SelectTrigger id="status" className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                        {availableStatuses.map((saleStatus) => (
                                            <SelectItem key={saleStatus} value={saleStatus}>
                                                {formatSaleStatus(saleStatus)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">Cliente</Label>
                                <Select
                                    value={activeTab.customer_id === '' ? NO_CUSTOMER_VALUE : activeTab.customer_id}
                                    onValueChange={(value) => updateActiveTab((tab) => ({ ...tab, customer_id: value === NO_CUSTOMER_VALUE ? '' : value }))}
                                >
                                    <SelectTrigger className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                                        <SelectValue placeholder="Selecciona un cliente" />
                                    </SelectTrigger>
                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                        <SelectItem value={NO_CUSTOMER_VALUE}>Sin cliente</SelectItem>
                                        {customers.map((customer) => (
                                            <SelectItem key={customer.id} value={String(customer.id)}>
                                                {customer.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.customer_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">Repartidor</Label>
                                <Select
                                    disabled={isDirectSale}
                                    value={activeTab.delivery_user_id === '' ? NO_DELIVERY_USER_VALUE : activeTab.delivery_user_id}
                                    onValueChange={(value) => updateActiveTab((tab) => ({ ...tab, delivery_user_id: value === NO_DELIVERY_USER_VALUE ? '' : value }))}
                                >
                                    <SelectTrigger className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100 disabled:text-stone-500">
                                        <SelectValue placeholder="Sin repartidor" />
                                    </SelectTrigger>
                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                        <SelectItem value={NO_DELIVERY_USER_VALUE}>Sin repartidor</SelectItem>
                                        {deliveryUsers.map((deliveryUser) => (
                                            <SelectItem key={deliveryUser.id} value={String(deliveryUser.id)}>
                                                {deliveryUser.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.delivery_user_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">Fecha de venta</Label>
                                <DateTimePicker value={activeTab.sale_date} onChange={(value) => updateActiveTab((tab) => ({ ...tab, sale_date: value }))} />
                                <InputError message={form.errors.sale_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">Fecha de entrega</Label>
                                <DateTimePicker disabled={isDirectSale} value={activeTab.delivery_date} onChange={(value) => updateActiveTab((tab) => ({ ...tab, delivery_date: value }))} />
                                <InputError message={form.errors.delivery_date} />
                            </div>

                            <div className="grid gap-2 lg:col-span-2">
                                <Label className="font-semibold text-nopal-700">Notas</Label>
                                <textarea
                                    value={activeTab.notes}
                                    onChange={(event) => updateActiveTab((tab) => ({ ...tab, notes: event.target.value }))}
                                    className="min-h-24 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none placeholder:text-stone-400 focus:border-nopal-300"
                                    placeholder="Observaciones rapidas de la venta"
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-4">
                                <Heading variant="small" title="Productos" description="Cada tab queda pendiente aunque abras otra cuenta." />
                                <Button type="button" variant="outline" onClick={() => updateActiveTab((tab) => ({ ...tab, items: [...tab.items, defaultItem(products)] }))}>
                                    <Plus className="mr-2 size-4" />
                                    Agregar
                                </Button>
                            </div>

                            {activeTab.items.map((item, itemIndex) => {
                                const product = findProduct(products, item.product_id);
                                const presentations = product?.presentations ?? [];
                                const soldQuantity = isDirectSale ? toNumber(item.quantity) : toNumber(item.sold_quantity);
                                const lineSubtotal = toNumber(item.catalog_price) * soldQuantity;
                                const lineTotal = toNumber(item.unit_price) * soldQuantity;
                                const lineDiscount = Math.max(lineSubtotal - lineTotal, 0);

                                return (
                                    <div key={`${activeTab.id}-item-${itemIndex}`} className="space-y-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4">
                                        <div className="grid gap-4 xl:grid-cols-[1.3fr_1fr_0.8fr_0.8fr_0.8fr_0.8fr_auto]">
                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Producto</Label>
                                                <Select value={item.product_id} onValueChange={(value) => syncProductSelection(itemIndex, value)}>
                                                    <SelectTrigger className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                                                        <SelectValue placeholder="Selecciona un producto" />
                                                    </SelectTrigger>
                                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                                        {products.map((option) => (
                                                            <SelectItem key={option.id} value={String(option.id)}>
                                                                {option.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={form.errors[`items.${itemIndex}.product_id`]} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Presentacion</Label>
                                                <Select
                                                    value={item.presentation_id}
                                                    onValueChange={(value) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex ? { ...currentItem, presentation_id: value } : currentItem
                                                            )),
                                                        }))
                                                    }
                                                >
                                                    <SelectTrigger className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                                                        <SelectValue placeholder="Selecciona una presentacion" />
                                                    </SelectTrigger>
                                                    <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                                        {presentations.map((presentation) => (
                                                            <SelectItem key={presentation.id} value={String(presentation.id)}>
                                                                {presentation.name} ({presentation.quantity} {presentation.unit.code})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={form.errors[`items.${itemIndex}.presentation_id`]} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Enviada</Label>
                                                <Input
                                                    type="number"
                                                    min="0.001"
                                                    step="0.001"
                                                    value={item.quantity}
                                                    onChange={(event) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex
                                                                    ? {
                                                                          ...currentItem,
                                                                          quantity: event.target.value,
                                                                          sold_quantity: isDirectSale ? event.target.value : currentItem.sold_quantity,
                                                                      }
                                                                    : currentItem
                                                            )),
                                                        }))
                                                    }
                                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                                />
                                                <InputError message={form.errors[`items.${itemIndex}.quantity`]} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Vendida</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.001"
                                                    disabled={! canLiquidateDelivery}
                                                    value={isDirectSale ? item.quantity : item.sold_quantity}
                                                    onChange={(event) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex ? { ...currentItem, sold_quantity: event.target.value } : currentItem
                                                            )),
                                                        }))
                                                    }
                                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100"
                                                />
                                                <InputError message={form.errors[`items.${itemIndex}.sold_quantity`]} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Devuelta</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.001"
                                                    disabled={! canLiquidateDelivery}
                                                    value={isDirectSale ? '0' : item.returned_quantity}
                                                    onChange={(event) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex ? { ...currentItem, returned_quantity: event.target.value } : currentItem
                                                            )),
                                                        }))
                                                    }
                                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100"
                                                />
                                                <InputError message={form.errors[`items.${itemIndex}.returned_quantity`]} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Precio final</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={item.unit_price}
                                                    onChange={(event) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex ? { ...currentItem, unit_price: event.target.value } : currentItem
                                                            )),
                                                        }))
                                                    }
                                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                                />
                                                <InputError message={form.errors[`items.${itemIndex}.unit_price`]} />
                                            </div>

                                            <div className="flex items-end">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    disabled={activeTab.items.length === 1}
                                                    onClick={() =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.filter((_, index) => index !== itemIndex),
                                                        }))
                                                    }
                                                >
                                                    Quitar
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="grid gap-4 lg:grid-cols-[0.8fr_0.8fr_1fr_1.2fr]">
                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Precio catalogo</Label>
                                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                                    {formatMoney(item.catalog_price)}
                                                </div>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Descuento</Label>
                                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                                    {formatMoney(lineDiscount)}
                                                </div>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Total de linea</Label>
                                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                                    {formatMoney(lineTotal)}
                                                </div>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label className="font-semibold text-nopal-700">Nota de descuento</Label>
                                                <Input
                                                    value={item.discount_note}
                                                    onChange={(event) =>
                                                        updateActiveTab((tab) => ({
                                                            ...tab,
                                                            items: tab.items.map((currentItem, index) => (
                                                                index === itemIndex ? { ...currentItem, discount_note: event.target.value } : currentItem
                                                            )),
                                                        }))
                                                    }
                                                    placeholder="Motivo si se baja el precio final"
                                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                                />
                                                <InputError message={form.errors[`items.${itemIndex}.discount_note`]} />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <aside className="space-y-6 rounded-[2rem] border border-stone-200 bg-stone-50 p-6 shadow-sm">
                        <Heading title="Resumen" description="Este tab queda pendiente aunque abras otra venta." />

                        <div className="space-y-4">
                            <div className="rounded-2xl border border-stone-200 bg-white p-4">
                                <p className="text-sm text-stone-500">Subtotal</p>
                                <p className="mt-2 text-2xl font-semibold text-stone-900">{formatMoney(totals.subtotal)}</p>
                            </div>
                            <div className="rounded-2xl border border-stone-200 bg-white p-4">
                                <p className="text-sm text-stone-500">Descuento</p>
                                <p className="mt-2 text-2xl font-semibold text-amber-700">{formatMoney(totals.discountTotal)}</p>
                            </div>
                            <div className="rounded-2xl border border-stone-200 bg-white p-4">
                                <p className="text-sm text-stone-500">Total</p>
                                <p className="mt-2 text-3xl font-semibold text-nopal-700">{formatMoney(totals.total)}</p>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <Button className="w-full rounded-xl" disabled={form.processing} onClick={submitCurrentTab}>
                                Guardar venta
                            </Button>
                            <Button type="button" variant="outline" className="w-full rounded-xl" onClick={createNextTab}>
                                Abrir otra cuenta
                            </Button>
                        </div>

                        <div className="rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-3 text-sm text-stone-600">
                            `POS` guarda tabs pendientes en este navegador. `Ventas` sigue mostrando el historial completo y la edicion formal.
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}

PosIndex.layout = {
    breadcrumbs: [{ title: 'POS', href: posIndex() }],
};
