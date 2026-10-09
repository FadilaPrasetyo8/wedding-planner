import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';

type TabItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    /** Mark active when the current URL starts with this tab's path. */
    matchStartsWith?: boolean;
};

/**
 * Underline tab navigation used across finance pages. Replaces the mix of
 * outline buttons each finance page previously used for section switching.
 */
export function SectionTabs({
    items,
    ariaLabel,
}: {
    items: TabItem[];
    ariaLabel: string;
}) {
    const { currentUrl } = useCurrentUrl();

    return (
        <nav
            aria-label={ariaLabel}
            className="border-border -mt-2 flex flex-wrap gap-x-1 gap-y-2 border-b"
        >
            {items.map((item) => {
                const href = toUrl(item.href);
                const isActive = item.matchStartsWith
                    ? currentUrl.startsWith(href)
                    : currentUrl === href;
                return (
                    <Link
                        key={item.title}
                        href={item.href}
                        prefetch
                        aria-current={isActive ? 'page' : undefined}
                        className={`focus-visible:ring-ring -mb-px inline-flex min-h-11 items-center gap-2 rounded-t-lg border-b-2 px-3 py-2 text-sm font-medium transition-colors outline-none focus-visible:ring-2 ${
                            isActive
                                ? 'border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground border-transparent'
                        }`}
                    >
                        {item.title}
                    </Link>
                );
            })}
        </nav>
    );
}
