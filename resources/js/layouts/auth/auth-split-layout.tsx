import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col overflow-hidden bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-[linear-gradient(160deg,#2c5d1f_0%,#4f8a2d_52%,#f4c430_100%)]" />
                <div className="absolute -top-16 -right-10 h-64 w-64 rounded-full bg-white/12 blur-3xl" />
                <div className="absolute bottom-10 left-0 h-40 w-40 rounded-full bg-maiz-300/25 blur-3xl" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <AppLogoIcon className="mr-3 size-10" />
                    <div>
                        <span className="block text-xl font-semibold">
                            NopalGreen
                        </span>
                        <span className="block text-xs tracking-[0.22em] text-white/80 uppercase">
                            Tortilleria
                        </span>
                    </div>
                </Link>
                <div className="relative z-20 mt-auto max-w-sm space-y-3">
                    <p className="text-4xl leading-tight font-semibold text-white">
                        Tortillas frescas con identidad local.
                    </p>
                    <p className="text-sm text-white/80">
                        Accede al sistema de NopalGreen con una interfaz clara,
                        ligera y alineada a la marca.
                    </p>
                </div>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="h-12 sm:h-14" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
