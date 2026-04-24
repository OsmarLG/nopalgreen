import { Head } from '@inertiajs/react';
import UserForm from '@/pages/users/partials/user-form';
import { create, index, store } from '@/routes/users';

export default function CreateUser({
    roles,
    permissions,
}: {
    roles: string[];
    permissions: string[];
}) {
    return (
        <>
            <Head title="Nuevo usuario" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <UserForm
                        title="Crear usuario"
                        description="Alta de usuario interno con varios roles, control de asistencia y permisos directos."
                        submitLabel="Guardar usuario"
                        action={store.url()}
                        method="post"
                        roles={roles}
                        permissions={permissions}
                    />
                </div>
            </div>
        </>
    );
}

CreateUser.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: index(),
        },
        {
            title: 'Nuevo usuario',
            href: create(),
        },
    ],
};
