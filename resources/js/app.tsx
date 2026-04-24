import { createInertiaApp, router } from '@inertiajs/react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { Branding } from '@/types';

const fallbackAppName = import.meta.env.VITE_APP_NAME || 'NopalGreen';

const getCurrentBrandName = (): string => {
    const encodedPage = document.getElementById('app')?.getAttribute('data-page');

    if (encodedPage) {
        try {
            const parsedPage = JSON.parse(encodedPage) as { props?: { branding?: Branding } };

            if (parsedPage.props?.branding?.app_name) {
                return parsedPage.props.branding.app_name;
            }
        } catch {
            // Ignore invalid page payload and fall back to the current title.
        }
    }

    const titleSegments = document.title.split(' - ');

    return titleSegments.at(-1) || fallbackAppName;
};

const syncBrandingMeta = (branding?: Branding): void => {
    if (!branding) {
        return;
    }

    const currentTitleSegments = document.title.split(' - ');
    const pageTitle = currentTitleSegments.length > 1 ? currentTitleSegments[0] : '';

    document.title = pageTitle ? `${pageTitle} - ${branding.app_name}` : branding.app_name;

    document
        .querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="apple-touch-icon"]')
        .forEach((element) => {
            element.href = branding.favicon_url;
        });
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${getCurrentBrandName()}` : getCurrentBrandName()),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return <TooltipProvider delayDuration={0}>{app}</TooltipProvider>;
    },
    progress: {
        color: '#4f8a2d',
    },
});

router.on('finish', (event) => {
    const { visit } = event.detail;

    if (visit.completed && visit.method !== 'get') {
        router.flushAll();
    }
});

router.on('success', (event) => {
    syncBrandingMeta(event.detail.page.props.branding as Branding | undefined);
});

// This will set light / dark mode on load...
initializeTheme();
