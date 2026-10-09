import { Form, Head, router, usePage } from '@inertiajs/react';
import { Archive, Plus, Trash2, Users } from 'lucide-react';
import SavingContributorController from '@/actions/App/Http/Controllers/Finance/SavingContributorController';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { contributors as contributorsPage } from '@/routes/weddings/finance/savings';
import { contributions as contributionsPage } from '@/routes/weddings/finance/savings';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';

type ContributorRow = {
    uuid: string;
    label: string;
    isActive: boolean;
    linkedMemberName: string | null;
    contributedAmount: number;
};
type MembershipOption = { id: number; name: string };

export default function SavingContributors({
    contributors,
    memberships,
    canEdit,
}: {
    contributors: ContributorRow[];
    memberships: MembershipOption[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    const removeContributor = (contributor: ContributorRow) => {
        if (contributor.contributedAmount > 0) {
            window.alert(
                `Penabung "${contributor.label}" memiliki riwayat setoran dan tidak dapat dihapus. Nonaktifkan sebagai gantinya agar riwayat tetap tersimpan.`,
            );
            return;
        }
        if (
            window.confirm(
                `Hapus penabung "${contributor.label}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(
                SavingContributorController.destroy.url([
                    activeWedding.uuid,
                    contributor.uuid,
                ]),
            );
        }
    };

    return (
        <>
            <Head title="Penabung" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-5xl">
                    <header className="mt-3 flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Penabung
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Tautkan anggota ruang atau catat penabung
                                eksternal dengan label khusus.
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
                            aria-label="Tambah penabung"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Penabung baru</h2>
                            <Form
                                {...SavingContributorController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 grid gap-5 sm:grid-cols-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="label">
                                                Label tampilan
                                            </Label>
                                            <Input
                                                id="label"
                                                name="label"
                                                placeholder="cth. Keluarga Besar"
                                                required
                                            />
                                            <InputError
                                                message={errors.label}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="membership_id">
                                                Tautkan anggota (opsional)
                                            </Label>
                                            <select
                                                id="membership_id"
                                                name="membership_id"
                                                defaultValue=""
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                <option value="">
                                                    Tanpa tautan anggota
                                                </option>
                                                {memberships.map(
                                                    (membership) => (
                                                        <option
                                                            key={membership.id}
                                                            value={
                                                                membership.id
                                                            }
                                                        >
                                                            {membership.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={errors.membership_id}
                                            />
                                        </div>
                                        <div className="sm:col-span-2 sm:justify-self-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Menambah…'
                                                    : 'Tambah penabung'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="divide-y">
                            {contributors.map((contributor) => (
                                <article
                                    key={contributor.uuid}
                                    className="grid gap-3 p-5 sm:grid-cols-[1fr_auto] sm:items-center"
                                >
                                    <div>
                                        <p className="flex flex-wrap items-center gap-2 font-semibold">
                                            <Users
                                                className="text-primary size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                            {contributor.label}
                                            {!contributor.isActive && (
                                                <span className="bg-muted text-muted-foreground rounded-full px-2.5 py-1 text-xs font-medium">
                                                    Nonaktif
                                                </span>
                                            )}
                                        </p>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {contributor.linkedMemberName
                                                ? `Anggota: ${contributor.linkedMemberName}`
                                                : 'Penabung eksternal'}
                                            {' · '}
                                            <span className="tabular-nums">
                                                {formatRupiah(
                                                    contributor.contributedAmount,
                                                )}
                                            </span>{' '}
                                            disetor
                                        </p>
                                    </div>
                                    {canEdit && (
                                        <div className="flex flex-wrap gap-2">
                                            <ToggleContributorForm
                                                weddingUuid={activeWedding.uuid}
                                                contributor={contributor}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive min-h-9"
                                                onClick={() =>
                                                    removeContributor(
                                                        contributor,
                                                    )
                                                }
                                            >
                                                <Trash2 aria-hidden="true" />
                                                Hapus
                                            </Button>
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                        {contributors.length === 0 && (
                            <div className="p-10 text-center">
                                <h2 className="font-semibold">
                                    Belum ada penabung
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Tambahkan penabung pertama untuk mulai
                                    mencatat setoran.
                                </p>
                            </div>
                        )}
                    </section>

                    <p className="text-muted-foreground mt-4 text-xs">
                        Menghapus keanggotaan pada ruang pernikahan tidak
                        menghapus riwayat setoran penabung — tautannya dilepas
                        dan label tetap tersimpan.
                    </p>
                </div>
            </main>
        </>
    );
}

function ToggleContributorForm({
    weddingUuid,
    contributor,
}: {
    weddingUuid: string;
    contributor: ContributorRow;
}) {
    return (
        <Form
            {...SavingContributorController.update.form([
                weddingUuid,
                contributor.uuid,
            ])}
            className="contents"
        >
            {({ processing }) => (
                <>
                    <input
                        type="hidden"
                        name="label"
                        value={contributor.label}
                    />
                    <input
                        type="hidden"
                        name="is_active"
                        value={contributor.isActive ? 'false' : 'true'}
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-9"
                        disabled={processing}
                    >
                        <Archive aria-hidden="true" />
                        {contributor.isActive ? 'Nonaktifkan' : 'Aktifkan'}
                    </Button>
                </>
            )}
        </Form>
    );
}
