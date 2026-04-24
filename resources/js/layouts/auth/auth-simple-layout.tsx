import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-[radial-gradient(circle_at_top,_#fff5d6,_#ffffff_55%)] p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8 rounded-[2rem] border border-nopal-200 bg-white p-8 shadow-2xl shadow-maiz-100/50">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-14 w-14 items-center justify-center rounded-2xl border border-nopal-100 bg-white shadow-sm">
                                <AppLogoIcon className="size-10" />
                            </div>
                            <div className="text-center">
                                <span className="block text-lg font-semibold text-nopal-700">
                                    NopalGreen
                                </span>
                                <span className="block text-xs tracking-[0.22em] text-maiz-700 uppercase">
                                    Tortilleria
                                </span>
                            </div>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl font-semibold text-nopal-700">{title}</h1>
                            <p className="text-center text-sm leading-6 text-stone-600">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
