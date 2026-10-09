import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Download, Plus, Search, Wallet } from 'lucide-react';
import BudgetItemController from '@/actions/App/Http/Controllers/Finance/BudgetItemController';
import InputError from '@/components/input-error';
import { SectionTabs } from '@/components/section-tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { exportUrl } from '@/lib/export-url';
import { budgetStatusNames, formatRupiah } from '@/lib/format';
import { index as budgetIndex } from '@/routes/weddings/finance/budget';
import { index as financeOverview } from '@/routes/weddings/finance';
import { index as savingsIndex } from '@/routes/weddings/finance/savings';
import { index as scenariosIndex } from '@/routes/weddings/finance/scenarios';
import type { ActiveWedding } from '@/types';

type BudgetRow = {
    uuid: string;
    name: string;
    status: 'draft' | 'committed' | 'cancelled';
    estimatedAmount: number;
    finalAmount: number | null;
    category: string | null;
    event: string | null;
    vendor: string | null;
    paymentTermsCount: number;
};
type PageData = {
    data: BudgetRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type Summary = {
    estimatedTotal: number;
    committedTotal: number;
    paidTotal: number;
    outstanding: number;
    draftCount: number;
    overpaid: boolean;
};
type Option = { uuid: string; name: string };

const statusStyles: Record<BudgetRow['status'], string> = {
    draft: 'bg-muted text-muted-foreground',
    committed:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    cancelled: 'bg-muted text-muted-foreground line-through',
};

export default function BudgetIndex({
    items,
    filters,
    categories,
    events,
    vendors,
    summary,
    canEdit,
    canExport,
}: {
    items: PageData;
    filters: { search: string; status: string; category: string };
    categories: Option[];
    events: Option[];
    vendors: Option[];
    summary: Summary;
    canEdit: boolean;
    canExport: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const base = budgetIndex(activeWedding.uuid).url;

    return (
        <>
            <Head title="Anggaran" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Anggaran
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {items.total} item · estimasi{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.estimatedTotal)}
                                </span>{' '}
                                · dikomit{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.committedTotal)}
                                </span>{' '}
                                · dibayar{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.paidTotal)}
                                </span>
                            </p>
                        </div>
                        <nav
                            aria-label="Mode akses"
                            className="flex flex-wrap gap-2"
                        >
                            {canExport && (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <a
                                        href={exportUrl(
                                            `/weddings/${activeWedding.uuid}/exports/budget`,
                                            filters,
                                        )}
                                    >
                                        <Download aria-hidden="true" />
                                        Ekspor CSV (Excel)
                                    </a>
                                </Button>
                            )}
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
                                    href: base,
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
                            aria-label="Tambah item anggaran"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">
                                Item anggaran baru
                            </h2>
                            <Form
                                {...BudgetItemController.store.form(
                                    activeWedding.uuid,
                                )}
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
                                                placeholder="cth. Katering"
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
                                                defaultValue=""
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
                                                defaultValue=""
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
                                                defaultValue=""
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
                                            <Input id="notes" name="notes" />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>
                                        <div className="flex items-end sm:col-span-2 sm:justify-self-end">
                                            <Button
                                                disabled={processing}
                                                className="min-h-11 sm:w-auto"
                                            >
                                                <Plus aria-hidden="true" />
                                                {processing
                                                    ? 'Menambah…'
                                                    : 'Tambah item'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    {summary.overpaid && (
                        <p
                            role="alert"
                            className="mt-5 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
                        >
                            Pembayaran melebihi jumlah komit. Jumlah komit tidak
                            diubah — periksa termin yang salah catat.
                        </p>
                    )}

                    <form
                        action={base}
                        method="get"
                        className="bg-card mt-5 grid gap-4 rounded-2xl border p-5 shadow-sm sm:grid-cols-[1fr_auto_auto_auto]"
                        aria-label="Filter anggaran"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="search">Cari nama item</Label>
                            <div className="relative">
                                <Search
                                    className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="search"
                                    name="search"
                                    defaultValue={filters.search}
                                    placeholder="cth. Gedung"
                                    className="pl-9"
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                defaultValue={filters.status || ''}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="">Semua status</option>
                                <option value="draft">Draf</option>
                                <option value="committed">Dikomit</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="category">Kategori</Label>
                            <select
                                id="category"
                                name="category"
                                defaultValue={filters.category || 'all'}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="all">Semua kategori</option>
                                {categories.map((category) => (
                                    <option
                                        key={category.uuid}
                                        value={category.uuid}
                                    >
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" className="min-h-11 w-full">
                                Terapkan filter
                            </Button>
                        </div>
                    </form>

                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[900px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3">Vendor</th>
                                        <th className="px-4 py-3 text-right">
                                            Estimasi
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Final
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Termin
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {items.data.map((item) => (
                                        <tr key={item.uuid}>
                                            <td className="px-4 py-3 font-medium">
                                                <Link
                                                    className="hover:text-primary focus-visible:outline-2"
                                                    href={`${base}/${item.uuid}`}
                                                >
                                                    {item.name}
                                                </Link>
                                                {item.event && (
                                                    <span className="text-muted-foreground mt-1 block text-xs">
                                                        {item.event}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusStyles[item.status]}`}
                                                >
                                                    {
                                                        budgetStatusNames[
                                                            item.status
                                                        ]
                                                    }
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.category ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.vendor ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {formatRupiah(
                                                    item.estimatedAmount,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {item.finalAmount === null
                                                    ? '—'
                                                    : formatRupiah(
                                                          item.finalAmount,
                                                      )}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3 text-right tabular-nums">
                                                {item.paymentTermsCount}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {items.data.map((item) => (
                                <article key={item.uuid} className="p-4">
                                    <Link
                                        className="font-semibold"
                                        href={`${base}/${item.uuid}`}
                                    >
                                        {item.name}
                                    </Link>
                                    <p className="mt-1 text-sm">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusStyles[item.status]}`}
                                        >
                                            {budgetStatusNames[item.status]}
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {item.category ?? 'Tanpa kategori'}
                                        {item.event ? ` · ${item.event}` : ''}
                                        {item.vendor ? ` · ${item.vendor}` : ''}
                                    </p>
                                    <p className="mt-1 text-sm tabular-nums">
                                        Estimasi{' '}
                                        {formatRupiah(item.estimatedAmount)}
                                        {item.finalAmount !== null &&
                                            ` · Final ${formatRupiah(item.finalAmount)}`}
                                    </p>
                                </article>
                            ))}
                        </div>
                        {items.data.length === 0 && (
                            <div className="p-10 text-center">
                                <Wallet
                                    className="text-muted-foreground mx-auto size-6"
                                    aria-hidden="true"
                                />
                                <h2 className="mt-3 font-semibold">
                                    Belum ada item anggaran yang cocok
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Ubah filter atau tambahkan item pertama
                                    lewat formulir di atas, atau terapkan
                                    skenario anggaran.
                                </p>
                            </div>
                        )}
                    </section>

                    <nav
                        aria-label="Paginasi anggaran"
                        className="mt-5 flex flex-wrap gap-2"
                    >
                        {items.links.map((link, index) =>
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
