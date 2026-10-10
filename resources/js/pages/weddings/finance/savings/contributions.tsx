import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import SavingContributionController from '@/actions/App/Http/Controllers/Finance/SavingContributionController';
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

type ContributionRow = {
    uuid: string;
    goal: string;
    contributor: string;
    amount: number;
    contributedOn: string | null;
    source: string | null;
    notes: string | null;
};
type PageData = {
    data: ContributionRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type GoalOption = { uuid: string; name: string };
type ContributorOption = { uuid: string; label: string };
type Summary = {
    targetTotal: number;
    collectedTotal: number;
    gap: number;
    percentage: number;
};

export default function SavingContributions({
    contributions,
    goals,
    contributors,
    summary,
    canEdit,
}: {
    contributions: PageData;
    goals: GoalOption[];
    contributors: ContributorOption[];
    summary: Summary;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    const removeContribution = (contribution: ContributionRow) => {
        if (
            window.confirm(
                `Hapus setoran ${formatRupiah(contribution.amount)} oleh ${contribution.contributor}? Riwayat lain tidak berubah.`,
            )
        ) {
            router.delete(
                SavingContributionController.destroy.url([
                    activeWedding.uuid,
                    contribution.uuid,
                ]),
            );
        }
    };

    return (
        <>
            <Head title="Riwayat setoran" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="mt-3 flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Riwayat setoran
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {contributions.total} setoran · terkumpul{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.collectedTotal)}
                                </span>{' '}
                                ({summary.percentage}% dari target).
                            </p>
                        </div>
                        {!canEdit && (
                            <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                Mode hanya lihat
                            </span>
                        )}
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
                            aria-label="Catat setoran"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Catat setoran</h2>
                            <Form
                                {...SavingContributionController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="saving_goal_uuid">
                                                Target
                                            </Label>
                                            <select
                                                id="saving_goal_uuid"
                                                name="saving_goal_uuid"
                                                defaultValue=""
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                required
                                            >
                                                <option value="">
                                                    Pilih target
                                                </option>
                                                {goals.map((goal) => (
                                                    <option
                                                        key={goal.uuid}
                                                        value={goal.uuid}
                                                    >
                                                        {goal.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.saving_goal_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="saving_contributor_uuid">
                                                Penabung
                                            </Label>
                                            <select
                                                id="saving_contributor_uuid"
                                                name="saving_contributor_uuid"
                                                defaultValue=""
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                required
                                            >
                                                <option value="">
                                                    Pilih penabung
                                                </option>
                                                {contributors.map(
                                                    (contributor) => (
                                                        <option
                                                            key={
                                                                contributor.uuid
                                                            }
                                                            value={
                                                                contributor.uuid
                                                            }
                                                        >
                                                            {contributor.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.saving_contributor_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="amount">
                                                Jumlah (rupiah)
                                            </Label>
                                            <Input
                                                id="amount"
                                                name="amount"
                                                type="number"
                                                min={1}
                                                required
                                            />
                                            <InputError
                                                message={errors.amount}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="contributed_on">
                                                Tanggal setoran
                                            </Label>
                                            <Input
                                                id="contributed_on"
                                                name="contributed_on"
                                                type="date"
                                                required
                                            />
                                            <InputError
                                                message={errors.contributed_on}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="source">
                                                Sumber
                                            </Label>
                                            <Input
                                                id="source"
                                                name="source"
                                                placeholder="cth. transfer"
                                            />
                                            <InputError
                                                message={errors.source}
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11 w-full"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Mencatat…'
                                                    : 'Catat'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[850px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Tanggal</th>
                                        <th className="px-4 py-3">Target</th>
                                        <th className="px-4 py-3">Penabung</th>
                                        <th className="px-4 py-3 text-right">
                                            Jumlah
                                        </th>
                                        <th className="px-4 py-3">Sumber</th>
                                        {canEdit && (
                                            <th className="px-4 py-3 text-right">
                                                Aksi
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {contributions.data.map((contribution) => (
                                        <tr key={contribution.uuid}>
                                            <td className="px-4 py-3">
                                                {formatDate(
                                                    contribution.contributedOn,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {contribution.goal}
                                            </td>
                                            <td className="px-4 py-3">
                                                {contribution.contributor}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {formatRupiah(
                                                    contribution.amount,
                                                )}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">
                                                {contribution.source ?? '—'}
                                            </td>
                                            {canEdit && (
                                                <td className="px-4 py-3 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive min-h-9"
                                                        onClick={() =>
                                                            removeContribution(
                                                                contribution,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 aria-hidden="true" />
                                                        Hapus
                                                    </Button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {contributions.data.map((contribution) => (
                                <article
                                    key={contribution.uuid}
                                    className="p-4"
                                >
                                    <p className="font-semibold">
                                        {contribution.goal}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {contribution.contributor} ·{' '}
                                        {formatDate(contribution.contributedOn)}
                                    </p>
                                    <p className="mt-1 text-sm tabular-nums">
                                        {formatRupiah(contribution.amount)}
                                    </p>
                                    {canEdit && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive mt-2 min-h-9"
                                            onClick={() =>
                                                removeContribution(contribution)
                                            }
                                        >
                                            <Trash2 aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    )}
                                </article>
                            ))}
                        </div>
                        {contributions.data.length === 0 && (
                            <div className="p-10 text-center">
                                <h2 className="font-semibold">
                                    Belum ada setoran
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Catat setoran pertama untuk mulai melacak
                                    kemajuan tabungan.
                                </p>
                            </div>
                        )}
                    </section>

                    <nav
                        aria-label="Paginasi setoran"
                        className="mt-5 flex flex-wrap gap-2"
                    >
                        {contributions.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={index}
                                    asChild
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                >
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : null,
                        )}
                    </nav>
                </div>
            </main>
        </>
    );
}
