import { Head } from '@inertiajs/react';
import RoleForm from '@/pages/roles/partials/role-form';
import { create, index, store } from '@/routes/roles';

export default function CreateRole({
    permissions,
}: {
    permissions: string[];
}) {
    return (
        <>
            <Head title="Nuevo rol" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <RoleForm
                        title="Crear rol"
                        description="Agrega un nuevo rol y define sus permisos iniciales."
                        submitLabel="Guardar rol"
                        action={store.url()}
                        method="post"
                        permissions={permissions}
                    />
                </div>
            </div>
        </>
    );
}

CreateRole.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: 'Nuevo rol',
            href: create(),
        },
    ],
};
