import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    describePermission,
    groupItemsByPermission,
} from '@/lib/access-display';

type RoleFormData = {
    name: string;
    permissions: string[];
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    permissions: string[];
    initialValues?: Partial<RoleFormData>;
    lockName?: boolean;
};

export default function RoleForm({
    title,
    description,
    submitLabel,
    action,
    method,
    permissions,
    initialValues,
    lockName = false,
}: Props) {
    const form = useForm<RoleFormData>({
        name: initialValues?.name ?? '',
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

    const togglePermission = (permission: string, checked: boolean) => {
        form.setData(
            'permissions',
            checked
                ? [...form.data.permissions, permission]
                : form.data.permissions.filter((value) => value !== permission),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="name" className="font-semibold text-nopal-700">
                        Nombre del rol
                    </Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder="supervisor"
                        disabled={lockName}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900 placeholder:text-stone-400 disabled:bg-stone-100"
                    />
                    <InputError message={form.errors.name} />
                </div>
            </div>

            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Permisos del rol"
                    description="Selecciona los permisos que pertenecen a este rol."
                />

                <div className="space-y-5">
                    {groupItemsByPermission(
                        permissions,
                        (permission) => permission,
                    ).map((group) => (
                        <div key={group.key} className="space-y-3">
                            <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">
                                {group.label}
                            </p>
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {group.items.map((permission) => {
                                    const permissionDisplay =
                                        describePermission(permission);

                                    return (
                                        <label
                                            key={permission}
                                            className="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700"
                                        >
                                            <input
                                                type="checkbox"
                                                className="mt-1 h-4 w-4 accent-[var(--color-nopal-500)]"
                                                checked={form.data.permissions.includes(
                                                    permission,
                                                )}
                                                onChange={(event) =>
                                                    togglePermission(
                                                        permission,
                                                        event.target.checked,
                                                    )
                                                }
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
                <Button className="rounded-xl" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {form.recentlySuccessful && (
                    <p className="text-sm text-stone-500">Guardado.</p>
                )}
            </div>
        </form>
    );
}
