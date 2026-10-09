/**
 * Presentation-boundary formatting for finance pages. Money is stored and
 * transported as whole-rupiah integers; it is only formatted here (CLAUDE.md 5.5).
 */

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const date = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export const formatRupiah = (amount: number): string => rupiah.format(amount);

export const formatDate = (value: string | null): string =>
    value ? date.format(new Date(`${value}T00:00:00`)) : '—';

export const formatPercentage = (value: number): string => `${value}%`;

export const budgetStatusNames = {
    draft: 'Draf',
    committed: 'Dikomit',
    cancelled: 'Dibatalkan',
} as const;

export const paymentStatusNames = {
    planned: 'Terencana',
    partially_paid: 'Sebagian dibayar',
    paid: 'Lunas',
    cancelled: 'Dibatalkan',
} as const;

export const paymentStatusStyles: Record<string, string> = {
    planned: 'bg-muted text-muted-foreground',
    partially_paid:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    paid: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    cancelled: 'bg-muted text-muted-foreground line-through',
};
