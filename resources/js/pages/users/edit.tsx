import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import UserForm from '@/pages/users/partials/user-form';
import { index, update } from '@/routes/users';
import type { User } from '@/types';

export default function EditUser({
    roles,
    permissions,
    userRecord,
    selectedRoles,
    selectedPermissions,
}: {
    roles: string[];
    permissions: string[];
    userRecord: User;
    selectedRoles: string[];
    selectedPermissions: string[];
}) {
    const { status } = usePage().props;

    return (
        <>
            <Head title={`Editar ${userRecord.name}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <Heading
                                title={userRecord.name}
                                description={`@${userRecord.username}`}
                            />
                            <div className="mt-3 flex flex-wrap gap-2">
                                {userRecord.roles?.map((role) => (
                                    <Badge key={role.id}>{role.name}</Badge>
                                ))}
                            </div>
                            <div className="mt-3 text-sm text-stone-500">
                                Inicio de asistencia: {userRecord.attendance_starts_at ?? 'Se toma la fecha de registro'}
                            </div>
                        </div>

                        {typeof status === 'string' && status !== '' && (
                            <div className="rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                                {status}
                            </div>
                        )}
                    </div>

                    <UserForm
                        title="Editar usuario"
                        description="Actualiza datos, rol principal y permisos directos."
                        submitLabel="Actualizar usuario"
                        action={update.url(userRecord)}
                        method="patch"
                        roles={roles}
                        permissions={permissions}
                        initialValues={{
                            name: userRecord.name,
                            username: userRecord.username,
                            email: userRecord.email,
                            attendance_starts_at: userRecord.attendance_starts_at ?? '',
                            roles: selectedRoles,
                            permissions: selectedPermissions,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditUser.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: index(),
        },
        {
            title: 'Editar usuario',
            href: index(),
        },
    ],
};
