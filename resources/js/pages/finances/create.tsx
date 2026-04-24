import FinanceTransactionForm from '@/pages/finances/partials/finance-transaction-form';
import { create, index, store } from '@/routes/finances';

export default function FinanceCreate({
    typeOptions,
    statusOptions,
}: {
    typeOptions: string[];
    statusOptions: string[];
}) {
    return (
        <div className="min-h-full rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <FinanceTransactionForm
                title="Nuevo movimiento financiero"
                description="Registra ingresos, egresos, deudas, cobros, pagos, perdidas o reembolsos manuales."
                submitLabel="Guardar movimiento"
                action={store.url()}
                method="post"
                typeOptions={typeOptions}
                statusOptions={statusOptions}
            />
        </div>
    );
}

FinanceCreate.layout = {
    breadcrumbs: [
        { title: 'Finanzas', href: index() },
        { title: 'Nuevo movimiento', href: create() },
    ],
};
