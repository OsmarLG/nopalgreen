import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: edit(),
            icon: null,
        },
        {
            title: 'Security',
            href: editSecurity(),
            icon: null,
        },
        // {
        //     title: 'Appearance',
        //     href: editAppearance(),
        //     icon: null,
        // },
    ];

    return (
        <div className="space-y-6 px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                <aside className="w-full max-w-xl rounded-[2rem] border border-stone-200 bg-white p-4 shadow-sm lg:w-64">
                    <nav
                        className="flex flex-col space-y-2 space-x-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => {
                            const isActive = isCurrentOrParentUrl(item.href);

                            return (
                                <Button
                                    key={`${toUrl(item.href)}-${index}`}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn(
                                        'h-11 w-full justify-start rounded-xl px-4 font-semibold text-stone-500 hover:bg-stone-100 hover:text-stone-900',
                                        {
                                            'border border-nopal-700 bg-nopal-50 text-nopal-700 shadow-sm hover:border-nopal-700 hover:bg-nopal-100 hover:text-nopal-700':
                                                isActive,
                                        },
                                    )}
                                >
                                    <Link
                                        href={item.href}
                                        aria-current={isActive ? 'page' : undefined}
                                    >
                                        {item.icon && (
                                            <item.icon className="h-4 w-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-2 lg:hidden" />

                <div className="flex-1">
                    <section className="space-y-6">
                        <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            {children}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    );
}
