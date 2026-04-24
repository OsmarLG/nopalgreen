import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatSaleStatus, formatSaleType } from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import type { SaleCatalogOption, SelectOption } from '@/types';

const NO_CUSTOMER_VALUE = '__none__';
const NO_DELIVERY_USER_VALUE = '__none__';

type SaleItemFormData = {
    product_id: string;
    presentation_id: string;
    quantity: string;
    sold_quantity: string;
    returned_quantity: string;
    catalog_price: string;
    unit_price: string;
    discount_note: string;
};

type SaleFormData = {
    customer_id: string;
    delivery_user_id: string;
    sale_type: string;
    status: string;
    sale_date: string;
    delivery_date: string;
    completed_at: string;
    notes: string;
    items: SaleItemFormData[];
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    customers: SelectOption[];
    deliveryUsers: SelectOption[];
    products: SaleCatalogOption[];
    saleTypes: string[];
    statuses: string[];
    initialValues?: Partial<SaleFormData>;
};

const toDateTimeLocalValue = (value: string): string => {
    if (value === '') {
        return '';
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');

    return normalized.slice(0, 16);
};

const findProduct = (products: SaleCatalogOption[], productId: string): SaleCatalogOption | undefined => {
    return products.find((product) => String(product.id) === productId);
};

const defaultItem = (products: SaleCatalogOption[]): SaleItemFormData => {
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

const toNumber = (value: string): number => {
    return Number(value || '0');
};

export default function SaleForm({
    title,
    description,
    submitLabel,
    action,
    method,
    customers,
    deliveryUsers,
    products,
    saleTypes,
    statuses,
    initialValues,
}: Props) {
    const form = useForm<SaleFormData>({
        customer_id: initialValues?.customer_id ?? '',
        delivery_user_id: initialValues?.delivery_user_id ?? '',
        sale_type: initialValues?.sale_type ?? saleTypes[0] ?? 'direct',
        status: initialValues?.status ?? statuses[0] ?? 'draft',
        sale_date: toDateTimeLocalValue(initialValues?.sale_date ?? ''),
        delivery_date: toDateTimeLocalValue(initialValues?.delivery_date ?? ''),
        completed_at: toDateTimeLocalValue(initialValues?.completed_at ?? ''),
        notes: initialValues?.notes ?? '',
        items: initialValues?.items ?? [defaultItem(products)],
    });

    const isDirectSale = form.data.sale_type === 'direct';
    const canLiquidateDelivery = form.data.sale_type === 'delivery' && form.data.status === 'completed';

    useEffect(() => {
        if (! isDirectSale) {
            return;
        }

        if (form.data.delivery_user_id === '' && form.data.delivery_date === '') {
            return;
        }

        form.setData((currentData) => ({
            ...currentData,
            delivery_user_id: '',
            delivery_date: '',
        }));
    }, [form, isDirectSale]);

    useEffect(() => {
        if (canLiquidateDelivery) {
            return;
        }

        const nextItems = form.data.items.map((item) => ({
            ...item,
            sold_quantity: isDirectSale ? item.quantity : '',
            returned_quantity: '0',
        }));

        const hasChanges = nextItems.some((item, index) => (
            item.sold_quantity !== form.data.items[index]?.sold_quantity
            || item.returned_quantity !== form.data.items[index]?.returned_quantity
        ));

        if (! hasChanges) {
            return;
        }

        form.setData('items', nextItems);
    }, [canLiquidateDelivery, form, isDirectSale]);

    const effectiveSoldQuantity = (item: SaleItemFormData): number => {
        return isDirectSale
            ? toNumber(item.quantity)
            : toNumber(item.sold_quantity);
    };

    const lineDiscount = (item: SaleItemFormData): string => {
        const discount = Math.max((toNumber(item.catalog_price) - toNumber(item.unit_price)) * effectiveSoldQuantity(item), 0);

        return discount.toFixed(2);
    };

    const lineTotal = (item: SaleItemFormData): string => {
        const total = toNumber(item.unit_price) * effectiveSoldQuantity(item);

        return total.toFixed(2);
    };

    const totals = form.data.items.reduce(
        (accumulator, item) => {
            const subtotal = toNumber(item.catalog_price) * effectiveSoldQuantity(item);
            const total = toNumber(item.unit_price) * effectiveSoldQuantity(item);

            return {
                subtotal: accumulator.subtotal + subtotal,
                discountTotal: accumulator.discountTotal + Math.max(subtotal - total, 0),
                total: accumulator.total + total,
            };
        },
        { subtotal: 0, discountTotal: 0, total: 0 },
    );

    const syncProductSelection = (index: number, productId: string): void => {
        const product = findProduct(products, productId);
        const presentation = product?.presentations[0];

        form.setData(
            'items',
            form.data.items.map((item, itemIndex) =>
                itemIndex === index
                    ? {
                          ...item,
                          product_id: productId,
                          presentation_id: String(presentation?.id ?? ''),
                          catalog_price: product?.sale_price ?? '0',
                          unit_price: item.unit_price === '' || item.unit_price === item.catalog_price ? product?.sale_price ?? '0' : item.unit_price,
                      }
                    : item,
            ),
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            customer_id:
                form.data.customer_id === '' || form.data.customer_id === NO_CUSTOMER_VALUE
                    ? null
                    : Number(form.data.customer_id),
            delivery_user_id:
                form.data.delivery_user_id === '' || form.data.delivery_user_id === NO_DELIVERY_USER_VALUE
                    ? null
                    : Number(form.data.delivery_user_id),
            sale_date: form.data.sale_date === '' ? null : form.data.sale_date,
            delivery_date: form.data.delivery_date === '' ? null : form.data.delivery_date,
            completed_at: form.data.completed_at === '' ? null : form.data.completed_at,
            items: form.data.items.map((item) => ({
                product_id: Number(item.product_id),
                presentation_id: Number(item.presentation_id),
                quantity: Number(item.quantity),
                sold_quantity: isDirectSale ? Number(item.quantity) : Number(item.sold_quantity || 0),
                returned_quantity: isDirectSale ? 0 : Number(item.returned_quantity || 0),
                catalog_price: Number(item.catalog_price),
                unit_price: Number(item.unit_price),
                discount_note: item.discount_note === '' ? null : item.discount_note,
            })),
        };

        form.transform(() => payload);

        if (method === 'patch') {
            form.patch(action, { preserveScroll: true });

            return;
        }

        form.post(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="customer_id" className="font-semibold text-nopal-700">Cliente</Label>
                    <Select
                        value={form.data.customer_id === '' ? NO_CUSTOMER_VALUE : form.data.customer_id}
                        onValueChange={(value) => form.setData('customer_id', value === NO_CUSTOMER_VALUE ? '' : value)}
                    >
                        <SelectTrigger id="customer_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
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
                    <Label htmlFor="sale_type" className="font-semibold text-nopal-700">Tipo de venta</Label>
                    <Select value={form.data.sale_type} onValueChange={(value) => form.setData('sale_type', value)}>
                        <SelectTrigger id="sale_type" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un tipo" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {saleTypes.map((saleType) => (
                                <SelectItem key={saleType} value={saleType}>
                                    {formatSaleType(saleType)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.sale_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status" className="font-semibold text-nopal-700">Estado</Label>
                    <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                        <SelectTrigger id="status" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un estado" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {formatSaleStatus(status)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="delivery_user_id" className="font-semibold text-nopal-700">Repartidor</Label>
                    <Select
                        disabled={isDirectSale}
                        value={form.data.delivery_user_id === '' ? NO_DELIVERY_USER_VALUE : form.data.delivery_user_id}
                        onValueChange={(value) => form.setData('delivery_user_id', value === NO_DELIVERY_USER_VALUE ? '' : value)}
                    >
                        <SelectTrigger id="delivery_user_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100 disabled:text-stone-500">
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
                    {isDirectSale && (
                        <p className="text-xs text-stone-500">Disponible solo para ventas por entrega.</p>
                    )}
                    <InputError message={form.errors.delivery_user_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="sale_date" className="font-semibold text-nopal-700">Fecha de venta</Label>
                    <DateTimePicker id="sale_date" value={form.data.sale_date} onChange={(value) => form.setData('sale_date', value)} />
                    <InputError message={form.errors.sale_date} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="delivery_date" className="font-semibold text-nopal-700">Fecha de entrega</Label>
                    <DateTimePicker id="delivery_date" value={form.data.delivery_date} onChange={(value) => form.setData('delivery_date', value)} disabled={isDirectSale} />
                    {isDirectSale && (
                        <p className="text-xs text-stone-500">Disponible solo para ventas por entrega.</p>
                    )}
                    <InputError message={form.errors.delivery_date} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="completed_at" className="font-semibold text-nopal-700">Fecha de cierre</Label>
                    <DateTimePicker id="completed_at" value={form.data.completed_at} onChange={(value) => form.setData('completed_at', value)} />
                    <InputError message={form.errors.completed_at} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="notes" className="font-semibold text-nopal-700">Notas</Label>
                    <textarea
                        id="notes"
                        value={form.data.notes}
                        onChange={(event) => form.setData('notes', event.target.value)}
                        className="min-h-28 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none ring-0 placeholder:text-stone-400 focus:border-nopal-300"
                        placeholder="Observaciones de venta, reparto o liquidacion"
                    />
                    <InputError message={form.errors.notes} />
                </div>
            </div>

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading variant="small" title="Detalle" description="Agrega productos, precio final y cantidades vendidas o devueltas." />
                    <Button type="button" variant="outline" onClick={() => form.setData('items', [...form.data.items, defaultItem(products)])}>
                        Agregar item
                    </Button>
                </div>

                <div className="space-y-4">
                    {form.data.items.map((item, index) => {
                        const product = findProduct(products, item.product_id);
                        const presentations = product?.presentations ?? [];

                        return (
                            <div key={`sale-item-${index}`} className="space-y-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4">
                                <div className="grid gap-4 lg:grid-cols-[1.3fr_1.1fr_0.8fr_0.8fr_0.8fr_0.8fr_auto]">
                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Producto</Label>
                                        <Select value={item.product_id} onValueChange={(value) => syncProductSelection(index, value)}>
                                            <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
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
                                        <InputError message={form.errors[`items.${index}.product_id`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Presentacion</Label>
                                        <Select
                                            value={item.presentation_id}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, presentation_id: value } : currentItem,
                                                    ),
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
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
                                        <InputError message={form.errors[`items.${index}.presentation_id`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Enviada</Label>
                                        <Input
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            value={item.quantity}
                                            onChange={(event) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, quantity: event.target.value } : currentItem,
                                                    ),
                                                )
                                            }
                                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                        />
                                        <InputError message={form.errors[`items.${index}.quantity`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Vendida</Label>
                                        <Input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            value={isDirectSale ? item.quantity : item.sold_quantity}
                                            disabled={! canLiquidateDelivery}
                                            onChange={(event) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, sold_quantity: event.target.value } : currentItem,
                                                    ),
                                                )
                                            }
                                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100"
                                        />
                                        {! canLiquidateDelivery && (
                                            <p className="text-xs text-stone-500">
                                                {isDirectSale
                                                    ? 'En venta directa siempre coincide con la cantidad enviada.'
                                                    : 'Se captura al completar la liquidacion del reparto.'}
                                            </p>
                                        )}
                                        <InputError message={form.errors[`items.${index}.sold_quantity`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Devuelta</Label>
                                        <Input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            value={isDirectSale ? '0' : item.returned_quantity}
                                            disabled={! canLiquidateDelivery}
                                            onChange={(event) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, returned_quantity: event.target.value } : currentItem,
                                                    ),
                                                )
                                            }
                                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-100"
                                        />
                                        {! canLiquidateDelivery && (
                                            <p className="text-xs text-stone-500">
                                                {isDirectSale
                                                    ? 'No aplica en venta directa.'
                                                    : 'Se captura al completar la liquidacion del reparto.'}
                                            </p>
                                        )}
                                        <InputError message={form.errors[`items.${index}.returned_quantity`]} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Precio final</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.unit_price}
                                            onChange={(event) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, unit_price: event.target.value } : currentItem,
                                                    ),
                                                )
                                            }
                                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                        />
                                        <InputError message={form.errors[`items.${index}.unit_price`]} />
                                    </div>

                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            disabled={form.data.items.length === 1}
                                            onClick={() => form.setData('items', form.data.items.filter((_, itemIndex) => itemIndex !== index))}
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
                                            {formatMoney(lineDiscount(item))}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Total de linea</Label>
                                        <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                            {formatMoney(lineTotal(item))}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label className="font-semibold text-nopal-700">Nota de descuento</Label>
                                        <Input
                                            value={item.discount_note}
                                            onChange={(event) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((currentItem, itemIndex) =>
                                                        itemIndex === index ? { ...currentItem, discount_note: event.target.value } : currentItem,
                                                    ),
                                                )
                                            }
                                            placeholder="Motivo si se baja el precio final"
                                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                        />
                                        <InputError message={form.errors[`items.${index}.discount_note`]} />
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <InputError message={form.errors.items} />
            </div>

            <div className="grid gap-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4 md:grid-cols-3">
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
                    <p className="mt-2 text-2xl font-semibold text-nopal-700">{formatMoney(totals.total)}</p>
                </div>
            </div>

            <div className="flex items-center gap-3">
                <Button className="rounded-xl" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {form.recentlySuccessful && <p className="text-sm text-stone-500">Guardado.</p>}
            </div>
        </form>
    );
}
