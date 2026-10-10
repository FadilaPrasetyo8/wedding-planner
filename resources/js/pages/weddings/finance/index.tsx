import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CheckCircle2,
    ChevronRight,
    PiggyBank,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { SectionTabs } from '@/components/section-tabs';
import { formatDate, formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';

type BudgetSummary = {
    estimatedTotal: number;
    committedTotal: number;
    paidTotal: number;
    outstanding: number;
    draftCount: number;
    overpaid: boolean;
};
type SavingsSummary = {
    targetTotal: number;
    collectedTotal: number;
    gap: number;
    percentage: number;
};
type UpcomingPayment = {
    uuid: string;
    name: string;
    budgetItem: string;
    dueDate: string | null;
    plannedAmount: number;
    remainingAmount: number;
};

export default function FinanceOverview({
    budget,
    savings,
    categoryBreakdown,
    eventBreakdown,
    upcomingPayments,
    canEdit,
}: {
    budget: BudgetSummary;
    savings: SavingsSummary;
    categoryBreakdown: Record<string, number>;
    eventBreakdown: Record<string, number>;
    upcomingPayments: UpcomingPayment[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const financeOverviewUrl = financeOverview(activeWedding.uuid);

    return (
        <>
            <Head title="Keuangan" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Keuangan
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Ringkasan anggaran, pembayaran, dan tabungan
                                ruang pernikahan ini.
                            </p>
                        </div>
                        <nav
                            aria-label="Mode akses"
                            className="flex flex-wrap gap-2"
                        >
                            {!canEdit && (
                                <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                    Mode hanya lihat
                                </span>
                            )}
                        </nav>
                    </header>

                    <div className="mt-4">
                        <SectionTabs
                            ariaLabel="Bagian keuangan"
                            items={[
                                {
                                    title: 'Ringkasan',
                                    href: financeOverviewUrl,
                                },
                                {
                                    title: 'Anggaran',
                                    href: budgetIndex(activeWedding.uuid),
                                    matchStartsWith: true,
                                },
                                {
                                    title: 'Skenario',
                                    href: scenariosIndex(activeWedding.uuid),
                                    matchStartsWith: true,
                                },
                                {
                                    title: 'Tabungan',
                                    href: savingsIndex(activeWedding.uuid),
                                    matchStartsWith: true,
                                },
                            ]}
                        />
                    </div>

                    {budget.overpaid && (
                        <div
                            role="alert"
                            className="mt-5 flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
                        >
                            <AlertTriangle
                                className="mt-0.5 size-5 shrink-0"
                                aria-hidden="true"
                            />
                            <p>
                                Total pembayaran melebihi jumlah komit (
                                {formatRupiah(Math.abs(budget.outstanding))}{' '}
                                lebih). Jumlah komit tidak diubah; periksa
                                pembayaran yang salah catat.
                            </p>
                        </div>
                    )}

                    <section
                        aria-label="Ringkasan anggaran"
                        className="bg-card mt-5 grid overflow-hidden rounded-2xl border shadow-sm sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <StatCard
                            label="Estimasi total"
                            value={formatRupiah(budget.estimatedTotal)}
                            detail={`${budget.draftCount} item masih draf`}
                        />
                        <StatCard
                            label="Dikomit"
                            value={formatRupiah(budget.committedTotal)}
                            detail="Jumlah final item dikomit"
                        />
                        <StatCard
                            label="Sudah dibayar"
                            value={formatRupiah(budget.paidTotal)}
                            detail="Dari termin pembayaran valid"
                        />
                        <StatCard
                            label="Outstanding"
                            value={formatRupiah(budget.outstanding)}
                            detail="Komit dikurangi dibayar"
                            serious={budget.outstanding < 0}
                        />
                    </section>

                    <div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.8fr)]">
                        <section
                            aria-label="Pembayaran mendatang"
                            className="bg-card overflow-hidden rounded-2xl border shadow-sm"
                        >
                            <header className="flex items-start gap-3 border-b p-5">
                                <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                                    <CalendarClock aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 className="font-semibold">
                                        Pembayaran mendatang
                                    </h2>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Termin belum lunas pada item dikomit.
                                    </p>
                                </div>
                            </header>
                            <div className="divide-y">
                                {upcomingPayments.map((term) => (
                                    <div
                                        key={term.uuid}
                                        className="grid min-h-14 gap-1 px-5 py-3 sm:grid-cols-[1fr_auto] sm:items-center"
                                    >
                                        <span>
                                            <span className="font-medium">
                                                {term.name}
                                            </span>
                                            <span className="text-muted-foreground mt-1 block text-xs">
                                                {term.budgetItem}
                                            </span>
                                        </span>
                                        <span className="text-sm sm:text-right">
                                            <span className="tabular-nums">
                                                {formatRupiah(
                                                    term.remainingAmount,
                                                )}
                                            </span>
                                            <span className="text-muted-foreground mt-1 block text-xs">
                                                jatuh tempo{' '}
                                                {formatDate(term.dueDate)}
                                            </span>
                                        </span>
                                    </div>
                                ))}
                            </div>
                            {upcomingPayments.length === 0 && (
                                <p className="text-muted-foreground px-5 py-8 text-center text-sm">
                                    Belum ada pembayaran mendatang.
                                </p>
                            )}
                            <div className="border-t p-3">
                                <Button
                                    asChild
                                    variant="ghost"
                                    className="min-h-11 w-full justify-between"
                                >
                                    <Link
                                        href={budgetIndex(activeWedding.uuid)}
                                    >
                                        Kelola anggaran dan termin
                                        <ChevronRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                        </section>

                        <div className="grid content-start gap-6">
                            <section
                                aria-label="Ringkasan tabungan"
                                className="bg-card rounded-2xl border p-5 shadow-sm"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                                        <PiggyBank aria-hidden="true" />
                                    </span>
                                    <h2 className="font-semibold">Tabungan</h2>
                                </div>
                                <dl className="mt-4 grid gap-3 text-sm">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Target aktif
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(savings.targetTotal)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Terkumpul
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(
                                                savings.collectedTotal,
                                            )}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Kekurangan
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(savings.gap)}
                                        </dd>
                                    </div>
                                </dl>
                                <div className="bg-muted mt-4 h-2 overflow-hidden rounded-full">
                                    <span
                                        className="bg-primary block h-full rounded-full"
                                        style={{
                                            width: `${Math.min(100, Math.max(0, savings.percentage))}%`,
                                        }}
                                    />
                                </div>
                                <p className="text-muted-foreground mt-2 flex items-center gap-1 text-xs">
                                    <CheckCircle2
                                        className="size-3"
                                        aria-hidden="true"
                                    />
                                    {savings.percentage}% dari target tercapai
                                </p>
                                <Button
                                    asChild
                                    variant="ghost"
                                    className="mt-3 min-h-11 w-full justify-between"
                                >
                                    <Link
                                        href={savingsIndex(activeWedding.uuid)}
                                    >
                                        Kelola tabungan
                                        <ChevronRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </section>

                            <BreakdownPanel
                                title="Sebaran kategori"
                                breakdown={categoryBreakdown}
                            />
                            <BreakdownPanel
                                title="Sebaran acara"
                                breakdown={eventBreakdown}
                            />
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}

function StatCard({
    label,
    value,
    detail,
    serious = false,
}: {
    label: string;
    value: string;
    detail: string;
    serious?: boolean;
}) {
    return (
        <article className="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0">
            <p className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                {label}
            </p>
            <p
                className={`mt-1 text-2xl font-semibold tabular-nums ${serious ? 'text-amber-600 dark:text-amber-400' : ''}`}
            >
                {value}
            </p>
            <p className="text-muted-foreground mt-1 text-sm">{detail}</p>
        </article>
    );
}

function BreakdownPanel({
    title,
    breakdown,
}: {
    title: string;
    breakdown: Record<string, number>;
}) {
    const entries = Object.entries(breakdown);
    const total = entries.reduce((sum, [, amount]) => sum + amount, 0);

    return (
        <section className="bg-card rounded-2xl border p-5 shadow-sm">
            <h2 className="font-semibold">{title}</h2>
            <div className="mt-4 grid gap-3">
                {entries.map(([name, amount]) => (
                    <div key={name}>
                        <div className="flex items-center justify-between gap-4 text-sm">
                            <span>{name}</span>
                            <span className="text-muted-foreground tabular-nums">
                                {formatRupiah(amount)}
                            </span>
                        </div>
                        <div className="bg-muted mt-1.5 h-1.5 overflow-hidden rounded-full">
                            <span
                                className="bg-primary block h-full rounded-full"
                                style={{
                                    width: `${total ? Math.round((amount / total) * 100) : 0}%`,
                                }}
                            />
                        </div>
                    </div>
                ))}
                {entries.length === 0 && (
                    <p className="text-muted-foreground text-sm">
                        Belum ada item dikomit.
                    </p>
                )}
            </div>
        </section>
    );
}
