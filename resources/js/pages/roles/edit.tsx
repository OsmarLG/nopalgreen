import { Head, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import RoleForm from '@/pages/roles/partials/role-form';
import { index, update } from '@/routes/roles';

type RoleRecord = {
    id: number;
    name: string;
};

export default function EditRole({
    roleRecord,
    permissions,
    selectedPermissions,
    isProtected,
}: {
    roleRecord: RoleRecord;
    permissions: string[];
    selectedPermissions: string[];
    isProtected: boolean;
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${roleRecord.name}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-3">
                            <h1 className="text-3xl font-semibold text-nopal-700">
                                {roleRecord.name}
                            </h1>
                            <div className="flex flex-wrap gap-2">
                                {isProtected && <Badge>protegido</Badge>}
                                <Badge
                                    variant="secondary"
                                    className="bg-stone-100 text-stone-700"
                                >
                                    Sin eliminacion
                                </Badge>
                            </div>
                        </div>

                        {typeof status === 'string' && status !== '' && (
                            <div className="rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                                {status}
                            </div>
                        )}
                    </div>

                    <RoleForm
                        title="Editar rol"
                        description="Los roles existentes no se eliminan; aqui solo ajustas permisos."
                        submitLabel="Actualizar rol"
                        action={update.url(roleRecord.id)}
                        method="patch"
                        permissions={permissions}
                        lockName={true}
                        initialValues={{
                            name: roleRecord.name,
                            permissions: selectedPermissions,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditRole.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: 'Editar rol',
            href: index(),
        },
    ],
};
