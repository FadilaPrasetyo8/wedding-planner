import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Archive, ArrowLeft, Check, Plus, Trash2 } from 'lucide-react';
import BudgetScenarioController from '@/actions/App/Http/Controllers/Finance/BudgetScenarioController';
import ScenarioItemController from '@/actions/App/Http/Controllers/Finance/ScenarioItemController';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';
import { applyScenarioPayload } from './apply-payload';

type Scenario = {
    uuid: string;
    name: string;
    description: string | null;
    isActive: boolean;
    totalAmount: number;
};
type ScenarioItemRow = {
    uuid: string;
    name: string;
    amount: number;
    notes: string | null;
    isActive: boolean;
    category: string | null;
    event: string | null;
};
type Option = { uuid: string; name: string };

export default function ScenarioShow({
    scenario,
    items,
    categories,
    events,
    hasBeenApplied,
    canEdit,
}: {
    scenario: Scenario;
    items: ScenarioItemRow[];
    categories: Option[];
    events: Option[];
    hasBeenApplied: boolean;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const args = [activeWedding.uuid, scenario.uuid] as const;

    const applyScenario = (confirmRepeat: boolean) => {
        const message = confirmRepeat
            ? `Terapkan ulang "${scenario.name}"? Item anggaran draf baru akan ditambahkan tanpa mengubah item yang sudah ada.`
            : `Terapkan "${scenario.name}" sebagai item anggaran draf? Item baru akan ditambahkan ke anggaran.`;
        if (window.confirm(message)) {
            router.post(
                BudgetScenarioController.apply.url([...args]),
                applyScenarioPayload(confirmRepeat),
            );
        }
    };

    const removeScenario = () => {
        if (
            window.confirm(
                `Hapus skenario "${scenario.name}" beserta seluruh alokasinya? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(BudgetScenarioController.destroy.url([...args]));
        }
    };

    return (
        <>
            <Head title={scenario.name} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <Link
                        href={scenariosIndex(activeWedding.uuid)}
                        className="text-muted-foreground hover:text-primary inline-flex items-center gap-1 text-sm"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Semua skenario
                    </Link>
                    <header className="mt-3 flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                Skenario anggaran
                            </p>
                            <h1 className="font-display mt-2 flex flex-wrap items-center gap-3 text-3xl font-semibold tracking-tight">
                                {scenario.name}
                                {!scenario.isActive && (
                                    <span className="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-medium">
                                        Diarsipkan
                                    </span>
                                )}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Total alokasi aktif{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(scenario.totalAmount)}
                                </span>{' '}
                                dari{' '}
                                {items.filter((item) => item.isActive).length}{' '}
                                item aktif.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                className="min-h-11"
                                onClick={() => applyScenario(hasBeenApplied)}
                            >
                                <Check aria-hidden="true" />
                                {hasBeenApplied
                                    ? 'Terapkan ulang'
                                    : 'Terapkan ke anggaran'}
                            </Button>
                            {canEdit && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    className="min-h-11"
                                    onClick={removeScenario}
                                >
                                    <Trash2 aria-hidden="true" />
                                    Hapus skenario
                                </Button>
                            )}
                        </div>
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

                    {hasBeenApplied && (
                        <p className="text-muted-foreground mt-4 rounded-2xl border border-dashed p-4 text-sm">
                            Skenario ini pernah diterapkan. Menerapkan ulang
                            menambahkan batch item draf baru dan memerlukan
                            konfirmasi.
                        </p>
                    )}

                    {canEdit && (
                        <section
                            aria-label="Perbarui skenario"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Perbarui skenario</h2>
                            <Form
                                {...BudgetScenarioController.update.form([
                                    ...args,
                                ])}
                                className="mt-4 grid gap-5 sm:grid-cols-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Nama</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={scenario.name}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="description">
                                                Deskripsi
                                            </Label>
                                            <Input
                                                id="description"
                                                name="description"
                                                defaultValue={
                                                    scenario.description ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.description}
                                            />
                                        </div>
                                        <label className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                name="is_active"
                                                defaultChecked={
                                                    scenario.isActive
                                                }
                                            />
                                            Skenario aktif
                                        </label>
                                        <div className="sm:col-start-2 sm:justify-self-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11"
                                            >
                                                {processing
                                                    ? 'Menyimpan…'
                                                    : 'Simpan perubahan'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    {canEdit && (
                        <section
                            aria-label="Tambah alokasi"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Tambah alokasi</h2>
                            <Form
                                {...ScenarioItemController.store.form([
                                    ...args,
                                ])}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Nama alokasi
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="amount">
                                                Jumlah (rupiah)
                                            </Label>
                                            <Input
                                                id="amount"
                                                name="amount"
                                                type="number"
                                                min={0}
                                                required
                                            />
                                            <InputError
                                                message={errors.amount}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="budget_category_uuid">
                                                Kategori
                                            </Label>
                                            <select
                                                id="budget_category_uuid"
                                                name="budget_category_uuid"
                                                defaultValue=""
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                required
                                            >
                                                <option value="">
                                                    Pilih kategori
                                                </option>
                                                {categories.map((category) => (
                                                    <option
                                                        key={category.uuid}
                                                        value={category.uuid}
                                                    >
                                                        {category.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.budget_category_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="wedding_event_uuid">
                                                Acara terkait
                                            </Label>
                                            <select
                                                id="wedding_event_uuid"
                                                name="wedding_event_uuid"
                                                defaultValue=""
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                <option value="">
                                                    Tidak terkait acara
                                                </option>
                                                {events.map((event) => (
                                                    <option
                                                        key={event.uuid}
                                                        value={event.uuid}
                                                    >
                                                        {event.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.wedding_event_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="notes">
                                                Catatan
                                            </Label>
                                            <Input id="notes" name="notes" />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>
                                        <div className="flex items-end lg:col-start-4">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11 w-full"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Menambah…'
                                                    : 'Tambah alokasi'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section
                        aria-label="Daftar alokasi"
                        className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm"
                    >
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[800px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Alokasi</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3">Acara</th>
                                        <th className="px-4 py-3 text-right">
                                            Jumlah
                                        </th>
                                        {canEdit && (
                                            <th className="px-4 py-3 text-right">
                                                Aksi
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {items.map((item) => (
                                        <tr
                                            key={item.uuid}
                                            className={
                                                item.isActive
                                                    ? undefined
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {item.name}
                                                {!item.isActive && (
                                                    <span className="ml-2 text-xs">
                                                        (nonaktif)
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.category ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.event ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {formatRupiah(item.amount)}
                                            </td>
                                            {canEdit && (
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-2">
                                                        <ToggleItemForm
                                                            weddingUuid={
                                                                activeWedding.uuid
                                                            }
                                                            scenarioUuid={
                                                                scenario.uuid
                                                            }
                                                            item={item}
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive min-h-9"
                                                            onClick={() => {
                                                                if (
                                                                    window.confirm(
                                                                        `Hapus alokasi "${item.name}"?`,
                                                                    )
                                                                ) {
                                                                    router.delete(
                                                                        ScenarioItemController.destroy.url(
                                                                            [
                                                                                activeWedding.uuid,
                                                                                scenario.uuid,
                                                                                item.uuid,
                                                                            ],
                                                                        ),
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 aria-hidden="true" />
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {items.map((item) => (
                                <article key={item.uuid} className="p-4">
                                    <p className="font-medium">
                                        {item.name}
                                        {!item.isActive && ' (nonaktif)'}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {item.category ?? 'Tanpa kategori'}
                                        {item.event ? ` · ${item.event}` : ''}
                                    </p>
                                    <p className="mt-1 text-sm tabular-nums">
                                        {formatRupiah(item.amount)}
                                    </p>
                                    {canEdit && (
                                        <div className="mt-3 flex gap-2">
                                            <ToggleItemForm
                                                weddingUuid={activeWedding.uuid}
                                                scenarioUuid={scenario.uuid}
                                                item={item}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive min-h-9"
                                                onClick={() => {
                                                    if (
                                                        window.confirm(
                                                            `Hapus alokasi "${item.name}"?`,
                                                        )
                                                    ) {
                                                        router.delete(
                                                            ScenarioItemController.destroy.url(
                                                                [
                                                                    activeWedding.uuid,
                                                                    scenario.uuid,
                                                                    item.uuid,
                                                                ],
                                                            ),
                                                        );
                                                    }
                                                }}
                                            >
                                                <Trash2 aria-hidden="true" />
                                                Hapus
                                            </Button>
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                        {items.length === 0 && (
                            <div className="p-10 text-center">
                                <h2 className="font-semibold">
                                    Belum ada alokasi
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Tambahkan alokasi pertama untuk skenario
                                    ini.
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </>
    );
}

function ToggleItemForm({
    weddingUuid,
    scenarioUuid,
    item,
}: {
    weddingUuid: string;
    scenarioUuid: string;
    item: ScenarioItemRow;
}) {
    return (
        <Form
            {...ScenarioItemController.update.form([
                weddingUuid,
                scenarioUuid,
                item.uuid,
            ])}
            className="contents"
        >
            {({ processing }) => (
                <>
                    <input type="hidden" name="name" value={item.name} />
                    <input type="hidden" name="amount" value={item.amount} />
                    <input
                        type="hidden"
                        name="is_active"
                        value={item.isActive ? 'false' : 'true'}
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-9"
                        disabled={processing}
                    >
                        <Archive aria-hidden="true" />
                        {item.isActive ? 'Nonaktifkan' : 'Aktifkan'}
                    </Button>
                </>
            )}
        </Form>
    );
}
