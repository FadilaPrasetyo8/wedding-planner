import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="bg-sidebar-primary text-sidebar-primary-foreground ring-sidebar-border/70 flex aspect-square size-8 items-center justify-center rounded-[var(--radius)] ring-1">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="font-display mb-0.5 truncate leading-tight font-semibold tracking-tight">
                    {name}
                </span>
            </div>
        </>
    );
}
