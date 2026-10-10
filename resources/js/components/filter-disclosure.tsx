import { ChevronDown, SlidersHorizontal } from 'lucide-react';
import { useId, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export default function FilterDisclosure({
    activeCount = 0,
    defaultOpen = false,
    children,
}: {
    activeCount?: number;
    defaultOpen?: boolean;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(defaultOpen || activeCount > 0);
    const panelId = useId();

    return (
        <div className="mt-5">
            <Button
                type="button"
                variant="outline"
                className="min-h-11"
                aria-expanded={open}
                aria-controls={panelId}
                onClick={() => setOpen((was) => !was)}
            >
                <SlidersHorizontal aria-hidden="true" />
                Filter
                {activeCount > 0 && (
                    <span className="bg-primary text-primary-foreground rounded-full px-2 text-xs tabular-nums">
                        {activeCount}
                    </span>
                )}
                <ChevronDown
                    aria-hidden="true"
                    className={`transition-transform ${open ? 'rotate-180' : ''}`}
                />
            </Button>
            <div id={panelId} hidden={!open} className="mt-3">
                {children}
            </div>
        </div>
    );
}
