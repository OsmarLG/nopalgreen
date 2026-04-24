import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import type { Branding } from '@/types';

export default function AppLogo({
    variant = 'default',
}: {
    variant?: 'default' | 'sidebar';
}) {
    const isSidebar = variant === 'sidebar';
    const { branding } = usePage<{ branding: Branding }>().props;

    return (
        <div className="flex w-full min-w-0 items-center group-data-[collapsible=icon]:justify-center">
            <div className="flex aspect-square size-9 items-center justify-center rounded-2xl border border-nopal-100 bg-white shadow-sm">
                <AppLogoIcon className="size-6" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm transition-[margin,opacity,width] duration-200 group-data-[collapsible=icon]:ml-0 group-data-[collapsible=icon]:w-0 group-data-[collapsible=icon]:opacity-0">
                <span
                    className={cn(
                        'mb-0.5 truncate leading-tight font-semibold',
                        isSidebar ? 'text-nopal-700' : 'text-nopal-700',
                    )}
                >
                    {branding.app_name}
                </span>
                <span
                    className={cn(
                        'truncate text-xs',
                        isSidebar ? 'text-stone-600' : 'text-muted-foreground',
                    )}
                >
                    {branding.app_tagline}
                </span>
            </div>
        </div>
    );
}
