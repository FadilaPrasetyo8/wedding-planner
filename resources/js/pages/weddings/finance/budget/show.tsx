import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowDown,
    ArrowUp,
    Ban,
    Check,
    Plus,
    Trash2,
} from 'lucide-react';
import BudgetItemController from '@/actions/App/Http/Controllers/Finance/BudgetItemController';
import PaymentTermController from '@/actions/App/Http/Controllers/Finance/PaymentTermController';
import { AttachmentsPanel } from '@/components/attachments-panel';
import { CustomFieldsPanel } from '@/components/custom-fields-panel';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    budgetStatusNames,
    formatDate,
    formatRupiah,
    paymentStatusNames,
    paymentStatusStyles,
} from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';
import { parseCommitAmount, swapTermUuids } from './payment-term-actions';

type BudgetItem = {
    uuid: string;
    name: string;
    status: 'draft' | 'committed' | 'cancelled';
    estimatedAmount: number;
    finalAmount: number | null;
    notes: string | null;
    category: string | null;
    event: string | null;
    categoryUuid: string | null;
    eventUuid: string | null;
    vendor: string | null;
    vendorUuid: string | null;
    sourceScenarioName: string | null;
};
type PaymentTermRow = {
    uuid: string;
    name: string;
    sortOrder: number;
    plannedAmount: number;
    dueDate: string | null;
    actualPaidAmount: number;
    paidAt: string | null;
    status: 'planned' | 'partially_paid' | 'paid' | 'cancelled';
    notes: string | null;
};
type Option = { uuid: string; name: string };

const statusStyles: Record<BudgetItem['status'], string> = {
    draft: 'bg-muted text-muted-foreground',
    committed:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    cancelled: 'bg-muted text-muted-foreground line-through',
};

export default function BudgetItemShow({
    item,
    paymentTerms,
    paidTotal,
    categories,
    events,
    vendors,
    customFields,
    attachments,
    termAttachments,
    canEdit,
}: {
    item: BudgetItem;
    paymentTerms: PaymentTermRow[];
    paidTotal: number;
    categories: Option[];
    events: Option[];
    vendors: Option[];
    customFields: {
        definitions: Parameters<typeof CustomFieldsPanel>[0]['definitions'];
        values: Record<string, unknown>;
    };
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
    termAttachments: Record<
        string,
        Parameters<typeof AttachmentsPanel>[0]['attachments']
    >;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const itemArgs = [activeWedding.uuid, item.uuid] as const;

    const commitItem = () => {
        const amount = window.prompt(
            `Masukkan jumlah final komit untuk "${item.name}" (rupiah):`,
            String(item.finalAmount ?? item.estimatedAmount),
        );
        if (amount === null) {
            return;
        }
        const parsed = parseCommitAmount(amount);
        if (parsed === null) {
            window.alert('Jumlah final harus berupa angka rupiah yang valid.');
            return;
        }
        if (
            window.confirm(
                `Komit "${item.name}" sebesar ${formatRupiah(parsed)}? Status item akan dikunci sebagai dikomit.`,
            )
        ) {
            router.post(BudgetItemController.commit.url([...itemArgs]), {
                final_amount: parsed,
            });
        }
    };

    const cancelItem = () => {
        if (
            window.confirm(
                `Batalkan "${item.name}"? Item tetap tersimpan sebagai riwayat dan dikeluarkan dari total aktif.`,
            )
        ) {
            router.post(BudgetItemController.cancel.url([...itemArgs]));
        }
    };

    const removeItem = () => {
        if (
            window.confirm(
                `Hapus item draf "${item.name}"? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(BudgetItemController.destroy.url([...itemArgs]));
        }
    };

    const moveTerm = (index: number, direction: -1 | 1) => {
        const uuids = swapTermUuids(
            paymentTerms.map((term) => term.uuid),
            index,
            direction,
        );
        if (uuids === null) {
            return;
        }
        router.post(PaymentTermController.reorder.url([...itemArgs]), {
            term_uuids: uuids,
        });
    };

    const removeTerm = (term: PaymentTermRow) => {
        const message =
            term.actualPaidAmount > 0
                ? `Termin "${term.name}" memiliki pembayaran tercatat. Nolkan pembayarannya terlebih dahulu sebelum menghapus.`
                : `Hapus termin "${term.name}"?`;
        if (term.actualPaidAmount > 0) {
            window.alert(message);
            return;
        }
        if (window.confirm(message)) {
            router.delete(
                PaymentTermController.destroy.url([...itemArgs, term.uuid]),
            );
        }
    };

    return (
        <>
            <Head title={item.name} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <Link
                        href={budgetIndex(activeWedding.uuid)}
                        className="text-muted-foreground hover:text-primary inline-flex items-center gap-1 text-sm"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Semua item anggaran
                    </Link>
                    <header className="mt-3 flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                Item anggaran
                            </p>
                            <h1 className="font-display mt-2 flex flex-wrap items-center gap-3 text-3xl font-semibold tracking-tight">
                                {item.name}
                                <span
                                    className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${statusStyles[item.status]}`}
                                >
                                    {budgetStatusNames[item.status]}
                                </span>
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {item.category ?? 'Tanpa kategori'}
                                {item.event ? ` · ${item.event}` : ''}
                                {item.vendor ? ` · ${item.vendor}` : ''}
                                {item.sourceScenarioName
                                    ? ` · dari skenario ${item.sourceScenarioName}`
                                    : ''}
                            </p>
                        </div>
                        {canEdit && (
                            <div className="flex flex-wrap gap-2">
                                {item.status === 'draft' && (
                                    <>
                                        <Button
                                            variant="outline"
                                            className="min-h-11"
                                            onClick={commitItem}
                                        >
                                            <Check aria-hidden="true" />
                                            Komit item
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-destructive min-h-11"
                                            onClick={removeItem}
                                        >
                                            <Trash2 aria-hidden="true" />
                                            Hapus draf
                                        </Button>
                                    </>
                                )}
                                {item.status !== 'cancelled' && (
                                    <Button
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={cancelItem}
                                    >
                                        <Ban aria-hidden="true" />
                                        Batalkan item
                                    </Button>
                                )}
                            </div>
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

                    <section
                        aria-label="Ringkasan item"
                        className="bg-card mt-5 grid overflow-hidden rounded-2xl border shadow-sm sm:grid-cols-3"
                    >
                        <article className="border-b p-5 sm:border-r sm:border-b-0">
                            <p className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                Estimasi
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">
                                {formatRupiah(item.estimatedAmount)}
                            </p>
                        </article>
                        <article className="border-b p-5 sm:border-r sm:border-b-0">
                            <p className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                Final komit
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">
                                {item.finalAmount === null
                                    ? 'Belum dikomit'
                                    : formatRupiah(item.finalAmount)}
                            </p>
                        </article>
                        <article className="p-5">
                            <p className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                Total dibayar
                            </p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">
                                {formatRupiah(paidTotal)}
                            </p>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {paymentTerms.length} termin pembayaran
                            </p>
                        </article>
                    </section>

                    {canEdit && (
                        <section
                            aria-label="Perbarui item anggaran"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Perbarui item</h2>
                            <Form
                                {...BudgetItemController.update.form([
                                    ...itemArgs,
                                ])}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Nama item
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={item.name}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="estimated_amount">
                                                Estimasi (rupiah)
                                            </Label>
                                            <Input
                                                id="estimated_amount"
                                                name="estimated_amount"
                                                type="number"
                                                min={0}
                                                defaultValue={
                                                    item.estimatedAmount
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.estimated_amount
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="budget_category_uuid">
                                                Kategori
                                            </Label>
                                            <select
                                                id="budget_category_uuid"
                                                name="budget_category_uuid"
                                                defaultValue={
                                                    item.categoryUuid ?? ''
                                                }
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                <option value="">
                                                    Tanpa kategori
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
                                                Acara
                                            </Label>
                                            <select
                                                id="wedding_event_uuid"
                                                name="wedding_event_uuid"
                                                defaultValue={
                                                    item.eventUuid ?? ''
                                                }
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                <option value="">
                                                    Tanpa acara
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
                                        <div className="grid gap-2">
                                            <Label htmlFor="vendor_uuid">
                                                Vendor
                                            </Label>
                                            <select
                                                id="vendor_uuid"
                                                name="vendor_uuid"
                                                defaultValue={
                                                    item.vendorUuid ?? ''
                                                }
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                <option value="">
                                                    Tanpa vendor
                                                </option>
                                                {vendors.map((vendor) => (
                                                    <option
                                                        key={vendor.uuid}
                                                        value={vendor.uuid}
                                                    >
                                                        {vendor.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.vendor_uuid}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="notes">
                                                Catatan
                                            </Label>
                                            <Input
                                                id="notes"
                                                name="notes"
                                                defaultValue={item.notes ?? ''}
                                            />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>
                                        <div className="flex items-end sm:col-span-2 sm:justify-self-end">
                                            <Button
                                                variant="outline"
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
                            aria-label="Tambah termin pembayaran"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">
                                Tambah termin pembayaran
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Jumlah termin tidak dibatasi — tambah, susun,
                                dan hapus sesuai kesepakatan dengan vendor.
                            </p>
                            <Form
                                {...PaymentTermController.store.form([
                                    ...itemArgs,
                                ])}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="term-name">
                                                Nama termin
                                            </Label>
                                            <Input
                                                id="term-name"
                                                name="name"
                                                placeholder="cth. DP 50%"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="planned_amount">
                                                Jumlah rencana (rupiah)
                                            </Label>
                                            <Input
                                                id="planned_amount"
                                                name="planned_amount"
                                                type="number"
                                                min={0}
                                                required
                                            />
                                            <InputError
                                                message={errors.planned_amount}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="due_date">
                                                Jatuh tempo
                                            </Label>
                                            <Input
                                                id="due_date"
                                                name="due_date"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.due_date}
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11 w-full"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Menambah…'
                                                    : 'Tambah termin'}
                                            </Button>
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2 lg:col-span-4">
                                            <Label htmlFor="term-notes">
                                                Catatan
                                            </Label>
                                            <Input
                                                id="term-notes"
                                                name="notes"
                                            />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section
                        aria-label="Daftar termin pembayaran"
                        className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm"
                    >
                        <div className="divide-y">
                            {paymentTerms.map((term, index) => (
                                <article key={term.uuid} className="p-5">
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <p className="flex flex-wrap items-center gap-2 font-semibold">
                                                {term.name}
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${paymentStatusStyles[term.status]}`}
                                                >
                                                    {
                                                        paymentStatusNames[
                                                            term.status
                                                        ]
                                                    }
                                                </span>
                                            </p>
                                            <dl className="text-muted-foreground mt-2 grid gap-1 text-sm sm:grid-cols-2">
                                                <div className="flex justify-between gap-4 sm:justify-start sm:gap-2">
                                                    <dt>Rencana</dt>
                                                    <dd className="tabular-nums">
                                                        {formatRupiah(
                                                            term.plannedAmount,
                                                        )}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between gap-4 sm:justify-start sm:gap-2">
                                                    <dt>Dibayar</dt>
                                                    <dd className="tabular-nums">
                                                        {formatRupiah(
                                                            term.actualPaidAmount,
                                                        )}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between gap-4 sm:justify-start sm:gap-2">
                                                    <dt>Jatuh tempo</dt>
                                                    <dd>
                                                        {formatDate(
                                                            term.dueDate,
                                                        )}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between gap-4 sm:justify-start sm:gap-2">
                                                    <dt>Tanggal bayar</dt>
                                                    <dd>
                                                        {formatDate(
                                                            term.paidAt,
                                                        )}
                                                    </dd>
                                                </div>
                                            </dl>
                                            {term.notes && (
                                                <p className="text-muted-foreground mt-2 text-sm">
                                                    {term.notes}
                                                </p>
                                            )}
                                        </div>
                                        {canEdit && (
                                            <div className="flex flex-wrap gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="min-h-9"
                                                    disabled={index === 0}
                                                    aria-label={`Naikkan ${term.name}`}
                                                    onClick={() =>
                                                        moveTerm(index, -1)
                                                    }
                                                >
                                                    <ArrowUp aria-hidden="true" />
                                                    Naik
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="min-h-9"
                                                    disabled={
                                                        index ===
                                                        paymentTerms.length - 1
                                                    }
                                                    aria-label={`Turunkan ${term.name}`}
                                                    onClick={() =>
                                                        moveTerm(index, 1)
                                                    }
                                                >
                                                    <ArrowDown aria-hidden="true" />
                                                    Turun
                                                </Button>
                                                <RemoveTermButton
                                                    itemUuid={item.uuid}
                                                    term={term}
                                                    onBlocked={removeTerm}
                                                />
                                            </div>
                                        )}
                                    </div>
                                    {canEdit && (
                                        <div className="mt-4 grid gap-4 border-t pt-4 lg:grid-cols-2">
                                            <EditTermForm
                                                weddingUuid={activeWedding.uuid}
                                                itemUuid={item.uuid}
                                                term={term}
                                            />
                                            <RecordPaymentForm
                                                weddingUuid={activeWedding.uuid}
                                                itemUuid={item.uuid}
                                                term={term}
                                            />
                                        </div>
                                    )}
                                    <AttachmentsPanel
                                        weddingUuid={activeWedding.uuid}
                                        module="payment_term"
                                        subjectUuid={term.uuid}
                                        attachments={
                                            termAttachments[term.uuid] ?? []
                                        }
                                        canEdit={canEdit}
                                        context={`${item.name} — ${term.name}`}
                                    />
                                </article>
                            ))}
                        </div>
                        {paymentTerms.length === 0 && (
                            <div className="p-10 text-center">
                                <h2 className="font-semibold">
                                    Belum ada termin pembayaran
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Tambahkan termin pertama untuk item ini,
                                    misalnya DP atau pelunasan.
                                </p>
                            </div>
                        )}
                    </section>

                    <CustomFieldsPanel
                        weddingUuid={activeWedding.uuid}
                        module="budget_item"
                        subjectUuid={item.uuid}
                        definitions={customFields.definitions}
                        values={customFields.values}
                        canEdit={canEdit}
                    />
                    <AttachmentsPanel
                        weddingUuid={activeWedding.uuid}
                        module="budget_item"
                        subjectUuid={item.uuid}
                        attachments={attachments}
                        canEdit={canEdit}
                        context={item.name}
                    />

                    <p className="text-muted-foreground mt-4 flex items-start gap-2 text-xs">
                        <AlertTriangle
                            className="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Pembayaran yang melebihi jumlah rencana tetap dicatat
                        dan memunculkan peringatan; jumlah komit tidak pernah
                        diubah otomatis.
                    </p>
                </div>
            </main>
        </>
    );
}

function EditTermForm({
    weddingUuid,
    itemUuid,
    term,
}: {
    weddingUuid: string;
    itemUuid: string;
    term: PaymentTermRow;
}) {
    return (
        <Form
            {...PaymentTermController.update.form([
                weddingUuid,
                itemUuid,
                term.uuid,
            ])}
            className="grid gap-3 sm:grid-cols-2"
        >
            {({ processing, errors }) => (
                <>
                    <legend className="sr-only">
                        Perbarui termin {term.name}
                    </legend>
                    <div className="grid gap-2">
                        <Label htmlFor={`name-${term.uuid}`}>Nama</Label>
                        <Input
                            id={`name-${term.uuid}`}
                            name="name"
                            defaultValue={term.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`planned-${term.uuid}`}>
                            Jumlah rencana
                        </Label>
                        <Input
                            id={`planned-${term.uuid}`}
                            name="planned_amount"
                            type="number"
                            min={0}
                            defaultValue={term.plannedAmount}
                            required
                        />
                        <InputError message={errors.planned_amount} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`due-${term.uuid}`}>Jatuh tempo</Label>
                        <Input
                            id={`due-${term.uuid}`}
                            name="due_date"
                            type="date"
                            defaultValue={term.dueDate ?? ''}
                        />
                        <InputError message={errors.due_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`notes-${term.uuid}`}>Catatan</Label>
                        <Input
                            id={`notes-${term.uuid}`}
                            name="notes"
                            defaultValue={term.notes ?? ''}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="sm:col-span-2 sm:justify-self-end">
                        <Button
                            variant="outline"
                            size="sm"
                            className="min-h-9"
                            disabled={processing}
                        >
                            {processing ? 'Menyimpan…' : 'Simpan termin'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function RecordPaymentForm({
    weddingUuid,
    itemUuid,
    term,
}: {
    weddingUuid: string;
    itemUuid: string;
    term: PaymentTermRow;
}) {
    return (
        <Form
            {...PaymentTermController.recordPayment.form([
                weddingUuid,
                itemUuid,
                term.uuid,
            ])}
            className="grid gap-3 sm:grid-cols-2"
        >
            {({ processing, errors }) => (
                <>
                    <legend className="sr-only">
                        Catat pembayaran {term.name}
                    </legend>
                    <div className="grid gap-2">
                        <Label htmlFor={`paid-${term.uuid}`}>
                            Bayar aktual (rupiah)
                        </Label>
                        <Input
                            id={`paid-${term.uuid}`}
                            name="actual_paid_amount"
                            type="number"
                            min={0}
                            defaultValue={term.actualPaidAmount}
                            required
                        />
                        <InputError message={errors.actual_paid_amount} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`paid-at-${term.uuid}`}>
                            Tanggal bayar
                        </Label>
                        <Input
                            id={`paid-at-${term.uuid}`}
                            name="paid_at"
                            type="date"
                            defaultValue={term.paidAt ?? ''}
                        />
                        <InputError message={errors.paid_at} />
                    </div>
                    <div className="sm:col-span-2 sm:justify-self-end">
                        <Button
                            variant="outline"
                            size="sm"
                            className="min-h-9"
                            disabled={processing}
                        >
                            {processing ? 'Mencatat…' : 'Catat pembayaran'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function RemoveTermButton({
    itemUuid,
    term,
    onBlocked,
}: {
    itemUuid: string;
    term: PaymentTermRow;
    onBlocked: (term: PaymentTermRow) => void;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="text-destructive min-h-9"
            onClick={() => onBlocked(term)}
            data-item-uuid={itemUuid}
        >
            <Trash2 aria-hidden="true" />
            Hapus
        </Button>
    );
}
