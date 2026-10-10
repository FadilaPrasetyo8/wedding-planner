import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Archive, Copy, Layers, Plus } from 'lucide-react';
import BudgetScenarioController from '@/actions/App/Http/Controllers/Finance/BudgetScenarioController';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { show as scenarioShow } from '@/routes/weddings/finance/scenarios';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';

type ScenarioRow = {
    uuid: string;
    name: string;
    description: string | null;
    isActive: boolean;
    totalAmount: number;
    itemCount: number;
};

export default function ScenariosIndex({
    scenarios,
    canEdit,
}: {
    scenarios: ScenarioRow[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    return (
        <>
            <Head title="Skenario anggaran" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Skenario anggaran
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Bandingkan paket alokasi lalu terapkan sebagai
                                item anggaran draf.
                            </p>
                        </div>
                        {canEdit ? (
                            <span className="text-muted-foreground text-sm">
                                Gunakan tombol salin pada skenario untuk membuat
                                varian baru.
                            </span>
                        ) : (
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

                    {canEdit && (
                        <section
                            aria-label="Buat skenario baru"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">
                                Skenario baru dari kosong
                            </h2>
                            <Form
                                {...BudgetScenarioController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid flex-1 gap-2">
                                            <Label htmlFor="name">
                                                Nama skenario
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="cth. Skenario Kustom"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <Button
                                            disabled={processing}
                                            className="min-h-11"
                                        >
                                            <Plus aria-hidden="true" />
                                            {processing
                                                ? 'Membuat…'
                                                : 'Buat skenario'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {scenarios.map((scenario) => (
                            <article
                                key={scenario.uuid}
                                className="bg-card flex flex-col rounded-2xl border p-5 shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <Link
                                        href={scenarioShow([
                                            activeWedding.uuid,
                                            scenario.uuid,
                                        ])}
                                        className="hover:text-primary text-lg font-semibold focus-visible:outline-2"
                                    >
                                        {scenario.name}
                                    </Link>
                                    {!scenario.isActive && (
                                        <span className="bg-muted text-muted-foreground inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium">
                                            <Archive
                                                className="size-3"
                                                aria-hidden="true"
                                            />
                                            Arsip
                                        </span>
                                    )}
                                </div>
                                {scenario.description && (
                                    <p className="text-muted-foreground mt-2 line-clamp-2 text-sm">
                                        {scenario.description}
                                    </p>
                                )}
                                <dl className="mt-4 grid gap-2 text-sm">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Total aktif
                                        </dt>
                                        <dd className="tabular-nums">
                                            {formatRupiah(scenario.totalAmount)}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-muted-foreground">
                                            Alokasi aktif
                                        </dt>
                                        <dd>{scenario.itemCount} item</dd>
                                    </div>
                                </dl>
                                <div className="mt-4 flex items-center gap-2 border-t pt-4">
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="sm"
                                        className="min-h-9"
                                    >
                                        <Link
                                            href={scenarioShow([
                                                activeWedding.uuid,
                                                scenario.uuid,
                                            ])}
                                        >
                                            <Layers aria-hidden="true" />
                                            Lihat detail
                                        </Link>
                                    </Button>
                                    {canEdit && (
                                        <CopyScenarioButton
                                            weddingUuid={activeWedding.uuid}
                                            scenarioUuid={scenario.uuid}
                                            sourceName={scenario.name}
                                        />
                                    )}
                                </div>
                            </article>
                        ))}
                    </section>
                    {scenarios.length === 0 && (
                        <div className="bg-card mt-5 rounded-2xl border p-10 text-center shadow-sm">
                            <h2 className="font-semibold">
                                Belum ada skenario anggaran
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Buat skenario pertama untuk mulai membandingkan
                                alokasi.
                            </p>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}

function CopyScenarioButton({
    weddingUuid,
    scenarioUuid,
    sourceName,
}: {
    weddingUuid: string;
    scenarioUuid: string;
    sourceName: string;
}) {
    return (
        <Form
            {...BudgetScenarioController.copy.form([weddingUuid, scenarioUuid])}
            className="contents"
        >
            {({ processing, errors }) => (
                <>
                    <input
                        type="hidden"
                        name="name"
                        value={`Salinan ${sourceName}`}
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-9"
                        disabled={processing}
                    >
                        <Copy aria-hidden="true" />
                        {processing ? 'Menyalin…' : 'Salin'}
                    </Button>
                    <InputError message={errors.name} />
                </>
            )}
        </Form>
    );
}
