import { Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { login } from '@/routes';

export default function Register() {
    return (
        <>
            <Head title="Register" />
            <div className="space-y-6 text-center">
                <p className="text-sm text-muted-foreground">
                    El registro publico esta deshabilitado. Los accesos se crean
                    desde administracion.
                </p>

                <TextLink href={login()}>Volver al login</TextLink>
            </div>
        </>
    );
}

Register.layout = {
    title: 'Registro deshabilitado',
    description: 'Solicita a administracion la creacion de tu usuario',
};
