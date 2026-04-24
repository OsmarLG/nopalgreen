import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    describePermission,
    formatRoleName,
    groupItemsByPermission,
} from '@/lib/access-display';

type UserFormData = {
    name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    attendance_starts_at: string;
    roles: string[];
    permissions: string[];
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    roles: string[];
    permissions: string[];
    initialValues?: Partial<UserFormData>;
};

export default function UserForm({
    title,
    description,
    submitLabel,
    action,
    method,
    roles,
    permissions,
    initialValues,
}: Props) {
    const form = useForm<UserFormData>({
        name: initialValues?.name ?? '',
        username: initialValues?.username ?? '',
        email: initialValues?.email ?? '',
        password: '',
        password_confirmation: '',
        attendance_starts_at: initialValues?.attendance_starts_at ?? '',
        roles: initialValues?.roles ?? [],
        permissions: initialValues?.permissions ?? [],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
        };

        if (method === 'patch') {
            form.patch(action, options);

            return;
        }

        form.post(action, options);
    };

    const toggleRole = (role: string, checked: boolean) => {
        form.setData(
            'roles',
            checked
                ? [...form.data.roles, role]
                : form.data.roles.filter((value) => value !== role),
        );
    };

    const togglePermission = (permission: string, checked: boolean) => {
        form.setData(
            'permissions',
            checked
                ? [...form.data.permissions, permission]
                : form.data.permissions.filter((value) => value !== permission),
        );
    };

    const employeeRoleSelected = form.data.roles.includes('empleado');

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name" className="font-semibold text-nopal-700">Nombre</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        placeholder="Nombre completo"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400"
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="username" className="font-semibold text-nopal-700">Username</Label>
                    <Input
                        id="username"
                        value={form.data.username}
                        onChange={(event) => form.setData('username', event.target.value)}
                        placeholder="osmarlg"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400"
                    />
                    <InputError message={form.errors.username} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email" className="font-semibold text-nopal-700">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.target.value)}
                        placeholder="usuario@nopalgreen.local"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400"
                    />
                    <InputError message={form.errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="attendance_starts_at" className="font-semibold text-nopal-700">
                        Inicio de asistencia
                    </Label>
                    <DateTimePicker
                        id="attendance_starts_at"
                        value={form.data.attendance_starts_at}
                        onChange={(value) => form.setData('attendance_starts_at', value)}
                        includeTime={false}
                    />
                    <p className="text-xs text-stone-500">
                        {employeeRoleSelected
                            ? 'Si se deja vacio, se toma la fecha de registro del usuario.'
                            : 'Solo aplica si el usuario tiene el rol empleado.'}
                    </p>
                    <InputError message={form.errors.attendance_starts_at} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password" className="font-semibold text-nopal-700">
                        {method === 'patch' ? 'Nueva contrasena' : 'Contrasena'}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        value={form.data.password}
                        onChange={(event) => form.setData('password', event.target.value)}
                        placeholder={method === 'patch' ? 'Deja en blanco para conservarla' : 'Contrasena'}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400"
                    />
                    <InputError message={form.errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation" className="font-semibold text-nopal-700">
                        Confirmar contrasena
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={form.data.password_confirmation}
                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                        placeholder="Confirma la contrasena"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400"
                    />
                </div>
            </div>

            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Roles"
                    description="Un usuario puede tener varios roles. Si incluye empleado, entra al control de asistencia."
                />

                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {roles.map((role) => (
                        <label
                            key={role}
                            className="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700"
                        >
                            <input
                                type="checkbox"
                                className="mt-1 h-4 w-4 accent-[var(--color-nopal-500)]"
                                checked={form.data.roles.includes(role)}
                                onChange={(event) => toggleRole(role, event.target.checked)}
                            />
                            <span className="min-w-0">
                                <span className="block font-semibold text-nopal-700">
                                    {formatRoleName(role)}
                                </span>
                                <span className="mt-1 block text-xs text-stone-500">{role}</span>
                            </span>
                        </label>
                    ))}
                </div>
                <InputError message={form.errors.roles} />
            </div>

            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Permisos directos"
                    description="Asigna permisos adicionales aparte de los roles."
                />

                <div className="space-y-5">
                    {groupItemsByPermission(permissions, (permission) => permission).map((group) => (
                        <div key={group.key} className="space-y-3">
                            <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">
                                {group.label}
                            </p>
                            <div className="grid gap-3 md:grid-cols-2">
                                {group.items.map((permission) => {
                                    const permissionDisplay = describePermission(permission);

                                    return (
                                        <label
                                            key={permission}
                                            className="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700"
                                        >
                                            <input
                                                type="checkbox"
                                                className="mt-1 h-4 w-4 accent-[var(--color-nopal-500)]"
                                                checked={form.data.permissions.includes(permission)}
                                                onChange={(event) => togglePermission(permission, event.target.checked)}
                                            />
                                            <span className="min-w-0">
                                                <span className="block font-semibold text-nopal-700">
                                                    {permissionDisplay.title}
                                                </span>
                                                <span className="mt-1 block text-xs text-stone-500">
                                                    {permissionDisplay.raw}
                                                </span>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
                <InputError message={form.errors.permissions} />
            </div>

            <div className="flex items-center gap-3">
                <Button className="rounded-xl" disabled={form.processing}>{submitLabel}</Button>
                {form.recentlySuccessful && <p className="text-sm text-stone-500">Guardado.</p>}
            </div>
        </form>
    );
}
