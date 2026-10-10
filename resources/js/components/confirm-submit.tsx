import { useState } from 'react';
import { Form } from '@inertiajs/react';
import type { RouteFormDefinition } from '@/wayfinder';

type ConfirmSubmitProps = {
    form: RouteFormDefinition<'post'>;
    label: string;
    confirmLabel?: string;
    cancelLabel?: string;
    context?: string;
    className?: string;
};

/**
 * Inline destructive confirmation: first click reveals confirm/cancel
 * controls instead of submitting immediately. Meets CLAUDE.md §8
 * destructive-confirmation requirement without a modal.
 */
export function ConfirmSubmit({
    form,
    label,
    confirmLabel = 'Hapus',
    cancelLabel = 'Batal',
    context,
    className,
}: ConfirmSubmitProps) {
    const [confirming, setConfirming] = useState(false);

    if (!confirming) {
        return (
            <button
                type="button"
                onClick={() => setConfirming(true)}
                className={`text-destructive hover:text-destructive/80 focus-visible:ring-ring rounded-md text-xs font-medium underline outline-none focus-visible:ring-2 ${className ?? ''}`}
            >
                {label}
            </button>
        );
    }

    return (
        <span
            role="group"
            aria-label={
                context ? `Konfirmasi hapus ${context}` : 'Konfirmasi hapus'
            }
            className={`inline-flex items-center gap-2 ${className ?? ''}`}
        >
            <Form {...form}>
                {({ processing }) => (
                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-destructive focus-visible:ring-ring rounded-md px-2 py-1 text-xs font-medium text-white outline-none focus-visible:ring-2 disabled:opacity-60"
                    >
                        {processing ? 'Menghapus…' : confirmLabel}
                    </button>
                )}
            </Form>
            <button
                type="button"
                onClick={() => setConfirming(false)}
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-md text-xs font-medium underline outline-none focus-visible:ring-2"
            >
                {cancelLabel}
            </button>
        </span>
    );
}
