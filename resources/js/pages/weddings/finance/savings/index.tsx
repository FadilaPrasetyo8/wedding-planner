import { Form, Head, router, usePage } from '@inertiajs/react';
import { Archive, PiggyBank, Plus, Trash2 } from 'lucide-react';
import SavingGoalController from '@/actions/App/Http/Controllers/Finance/SavingGoalController';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { contributors as contributorsPage } from '@/routes/weddings/finance/savings';
import { contributions as contributionsPage } from '@/routes/weddings/finance/savings';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';

type GoalRow = {
    uuid: string;
    name: string;
    targetAmount: number;
    targetDate: string | null;
    isActive: boolean;
    notes: string | null;
    collectedAmount: number;
    progressPercentage: number;
};
type Summary = {
    targetTotal: number;
    collectedTotal: number;
    gap: number;
    percentage: number;
};

export default function SavingsIndex({
    goals,
    summary,
    canEdit,
}: {
    goals: GoalRow[];
    summary: Summary;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    const removeGoal = (goal: GoalRow) => {
        if (goal.collectedAmount > 0) {
            window.alert(
                `Target "${goal.name}" memiliki riwayat setoran dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.`,
            );
            return;
        }
        if (
            window.confirm(
                `Hapus target "${goal.name}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(
                SavingGoalController.destroy.url([
                    activeWedding.uuid,
                    goal.uuid,
                ]),
            );
        }
    };

    return (
        <>
            <Head title="Tabungan" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Tabungan
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Terkumpul{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.collectedTotal)}
                                </span>{' '}
                                dari target{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.targetTotal)}
                                </span>{' '}
                                ({summary.percentage}%).
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
                                    href: financeOverview(activeWedding.uuid),
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

                    <div className="mt-4">
                        <SectionTabs
                            ariaLabel="Bagian tabungan"
                            items={[
                                {
                                    title: 'Target',
                                    href: savingsIndex(activeWedding.uuid),
                                },
                                {
                                    title: 'Penabung',
                                    href: contributorsPage(activeWedding.uuid),
                                    matchStartsWith: true,
                                },
                                {
                                    title: 'Riwayat setoran',
                                    href: contributionsPage(activeWedding.uuid),
                                    matchStartsWith: true,
                                },
                            ]}
                        />
                    </div>

                    {canEdit && (
                        <section
                            aria-label="Buat target tabungan"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">
                                Target tabungan baru
                            </h2>
                            <Form
                                {...SavingGoalController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Nama target
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="cth. Dana Resepsi"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="target_amount">
                                                Target (rupiah)
                                            </Label>
                                            <Input
                                                id="target_amount"
                                                name="target_amount"
                                                type="number"
                                                min={0}
                                                required
                                            />
                                            <InputError
                                                message={errors.target_amount}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="target_date">
                                                Tanggal target
                                            </Label>
                                            <Input
                                                id="target_date"
                                                name="target_date"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.target_date}
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11 w-full"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Membuat…'
                                                    : 'Buat target'}
                                            </Button>
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2 lg:col-span-4">
                                            <Label htmlFor="notes">
                                                Catatan
                                            </Label>
                                            <Input id="notes" name="notes" />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {goals.map((goal) => (
                            <article
                                key={goal.uuid}
                                className="bg-card flex flex-col rounded-2xl border p-5 shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                                        <PiggyBank
                                            className="text-primary size-5 shrink-0"
                                            aria-hidden="true"
                                        />
                                        {goal.name}
                                    </h2>
                                    {!goal.isActive && (
                                        <span className="bg-muted text-muted-foreground inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium">
                                            <Archive
                                                className="size-3"
                                                aria-hidden="true"
                                            />
                                            Nonaktif
                                        </span>
                                    )}
                                </div>
                                <dl className="mt-4 grid gap-2 text-sm">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Target
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(goal.targetAmount)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Terkumpul
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(goal.collectedAmount)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Tanggal target
                                        </dt>
                                        <dd>{formatDate(goal.targetDate)}</dd>
                                    </div>
                                </dl>
                                <div className="bg-muted mt-4 h-2 overflow-hidden rounded-full">
                                    <span
                                        className="bg-primary block h-full rounded-full"
                                        style={{
                                            width: `${Math.min(100, Math.max(0, goal.progressPercentage))}%`,
                                        }}
                                    />
                                </div>
                                <p className="text-muted-foreground mt-2 text-xs">
                                    {goal.progressPercentage}% tercapai
                                </p>
                                {goal.notes && (
                                    <p className="text-muted-foreground mt-3 text-sm">
                                        {goal.notes}
                                    </p>
                                )}
                                {canEdit && (
                                    <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                                        <ToggleGoalForm
                                            weddingUuid={activeWedding.uuid}
                                            goal={goal}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive min-h-9"
                                            onClick={() => removeGoal(goal)}
                                        >
                                            <Trash2 aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    </div>
                                )}
                            </article>
                        ))}
                    </section>
                    {goals.length === 0 && (
                        <div className="bg-card mt-5 rounded-2xl border p-10 text-center shadow-sm">
                            <h2 className="font-semibold">
                                Belum ada target tabungan
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Buat target pertama untuk mulai melacak tabungan
                                pernikahan.
                            </p>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}

function ToggleGoalForm({
    weddingUuid,
    goal,
}: {
    weddingUuid: string;
    goal: GoalRow;
}) {
    return (
        <Form
            {...SavingGoalController.update.form([weddingUuid, goal.uuid])}
            className="contents"
        >
            {({ processing }) => (
                <>
                    <input type="hidden" name="name" value={goal.name} />
                    <input
                        type="hidden"
                        name="target_amount"
                        value={goal.targetAmount}
                    />
                    {goal.targetDate && (
                        <input
                            type="hidden"
                            name="target_date"
                            value={goal.targetDate}
                        />
                    )}
                    <input
                        type="hidden"
                        name="is_active"
                        value={goal.isActive ? 'false' : 'true'}
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-9"
                        disabled={processing}
                    >
                        <Archive aria-hidden="true" />
                        {goal.isActive ? 'Nonaktifkan' : 'Aktifkan'}
                    </Button>
                </>
            )}
        </Form>
    );
}
