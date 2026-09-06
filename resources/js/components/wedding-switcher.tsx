import { Link, usePage } from '@inertiajs/react';
import { Check, ChevronDown, Plus } from 'lucide-react';
import { create, show } from '@/routes/weddings';
import type { ActiveWedding, WeddingSummary } from '@/types';

export function WeddingSwitcher() {
    const { weddings, activeWedding } = usePage<{
        weddings: WeddingSummary[];
        activeWedding: ActiveWedding | null;
    }>().props;

    return (
        <details className="group relative">
            <summary className="hover:bg-sidebar-accent focus-visible:ring-sidebar-ring flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-xl px-2.5 py-2 outline-none focus-visible:ring-2">
                <span className="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-xl font-serif text-lg">
                    W
                </span>
                <span className="min-w-0 flex-1 text-left">
                    <span className="block truncate text-sm font-semibold">
                        {activeWedding?.name ?? 'Wedding Planner'}
                    </span>
                    <span className="text-muted-foreground block truncate text-xs">
                        {activeWedding?.partnerNames ?? 'Ruang pernikahan Anda'}
                    </span>
                </span>
                <ChevronDown
                    className="size-4 transition-transform group-open:rotate-180"
                    aria-hidden="true"
                />
            </summary>
            <div className="bg-popover text-popover-foreground absolute top-full right-0 left-0 z-50 mt-2 rounded-2xl border p-2 shadow-lg">
                {weddings.map((wedding) => (
                    <Link
                        key={wedding.uuid}
                        href={show(wedding.uuid)}
                        className="hover:bg-accent focus-visible:bg-accent flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 outline-none"
                    >
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium">
                                {wedding.name}
                            </span>
                            <span className="text-muted-foreground block truncate text-xs">
                                {wedding.partnerNames}
                            </span>
                        </span>
                        {wedding.uuid === activeWedding?.uuid && (
                            <Check
                                className="text-primary size-4"
                                aria-label="Ruang aktif"
                            />
                        )}
                    </Link>
                ))}
                <Link
                    href={create()}
                    className="hover:bg-accent focus-visible:bg-accent mt-1 flex min-h-11 items-center gap-3 rounded-xl border-t px-3 py-2 text-sm font-medium outline-none"
                >
                    <Plus className="size-4" aria-hidden="true" />
                    Buat ruang baru
                </Link>
            </div>
        </details>
    );
}
