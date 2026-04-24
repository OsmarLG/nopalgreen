import FinanceTransactionForm from '@/pages/finances/partials/finance-transaction-form';
import { edit, index, update } from '@/routes/finances';

export default function FinanceEdit({
    transactionRecord,
    typeOptions,
    statusOptions,
}: {
    transactionRecord: {
        id: number;
        transaction_type: string;
        concept: string;
        detail: string;
        amount: string;
        status: string;
        occurred_at: string;
        notes: string;
    };
    typeOptions: string[];
    statusOptions: string[];
}) {
    return (
        <div className="min-h-full rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <FinanceTransactionForm
                title="Editar movimiento financiero"
                description="Actualiza un movimiento manual ya registrado en finanzas."
                submitLabel="Actualizar movimiento"
                action={update.url(transactionRecord.id)}
                method="patch"
                typeOptions={typeOptions}
                statusOptions={statusOptions}
                initialValues={transactionRecord}
            />
        </div>
    );
}

FinanceEdit.layout = (pageProps: { transactionRecord: { id: number } }) => ({
    breadcrumbs: [
        { title: 'Finanzas', href: index() },
        { title: 'Editar movimiento', href: edit(pageProps.transactionRecord.id) },
    ],
});
