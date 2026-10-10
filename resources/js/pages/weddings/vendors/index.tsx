import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, Plus, Search, Store } from 'lucide-react';
import { useState } from 'react';
import VendorCategoryController from '@/actions/App/Http/Controllers/Logistics/VendorCategoryController';
import VendorContactController from '@/actions/App/Http/Controllers/Logistics/VendorContactController';
import VendorController from '@/actions/App/Http/Controllers/Logistics/VendorController';
import { AttachmentsPanel } from '@/components/attachments-panel';
import { ConfirmSubmit } from '@/components/confirm-submit';
import FilterDisclosure from '@/components/filter-disclosure';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as vendorsIndex } from '@/routes/weddings/vendors';
import type { ActiveWedding } from '@/types';

type VendorContactRow = {
    uuid: string;
    name: string;
    phone: string | null;
    email: string | null;
    role: string | null;
    isPrimary: boolean;
};
type VendorRow = {
    uuid: string;
    name: string;
    category: string | null;
    categoryUuid: string | null;
    status: string;
    internalRating: number | null;
    address: string | null;
    website: string | null;
    socialMedia: string | null;
    notes: string | null;
    budgetItemsCount: number;
    contacts: VendorContactRow[];
};
type PageData = {
    data: VendorRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type StatusOption = { value: string; label: string };
type CategoryOption = { uuid: string; name: string };

const statusStyles: Record<string, string> = {
    candidate: 'bg-muted text-muted-foreground',
    contacted: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
    negotiating:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    selected:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    rejected: 'bg-muted text-muted-foreground line-through',
    done: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
};

const statusNames: Record<string, string> = {
    candidate: 'Kandidat',
    contacted: 'Dihubungi',
    negotiating: 'Negosiasi',
    selected: 'Terpilih',
    rejected: 'Ditolak',
    done: 'Selesai',
};

const ratingLabel = (rating: number | null): string =>
    rating === null ? '—' : `${rating}/5`;

export default function VendorIndex({
    vendors,
    filters,
    statuses,
    categories,
    vendorAttachments,
    canEdit,
}: {
    vendors: PageData;
    filters: { search: string; status: string; category: string };
    statuses: StatusOption[];
    categories: CategoryOption[];
    vendorAttachments: Record<
        string,
        Parameters<typeof AttachmentsPanel>[0]['attachments']
    >;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const base = vendorsIndex(activeWedding.uuid).url;

    return (
        <>
            <Head title="Vendor" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Vendor
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {vendors.total} vendor · kontak pribadi wedding
                                ini
                            </p>
                        </div>
                        {!canEdit && (
                            <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                Mode hanya lihat
                            </span>
                        )}
                    </header>

                    {canEdit && (
                        <section
                            aria-label="Tambah vendor"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Vendor baru</h2>
                            <Form
                                {...VendorController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="name">
                                                Nama vendor
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="cth. Dekorasi Melati"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="vendor_category_uuid">
                                                Kategori
                                            </Label>
                                            <select
                                                id="vendor_category_uuid"
                                                name="vendor_category_uuid"
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
                                                    errors.vendor_category_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Status
                                            </Label>
                                            <select
                                                id="status"
                                                name="status"
                                                defaultValue="candidate"
                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                            >
                                                {statuses.map((status) => (
                                                    <option
                                                        key={status.value}
                                                        value={status.value}
                                                    >
                                                        {status.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.status}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="internal_rating">
                                                Rating internal (0–5)
                                            </Label>
                                            <Input
                                                id="internal_rating"
                                                name="internal_rating"
                                                type="number"
                                                min={0}
                                                max={5}
                                            />
                                            <InputError
                                                message={errors.internal_rating}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="website">
                                                Situs web
                                            </Label>
                                            <Input
                                                id="website"
                                                name="website"
                                                type="url"
                                                placeholder="https://"
                                            />
                                            <InputError
                                                message={errors.website}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="social_media">
                                                Media sosial
                                            </Label>
                                            <Input
                                                id="social_media"
                                                name="social_media"
                                                type="url"
                                                placeholder="https://instagram.com/"
                                            />
                                            <InputError
                                                message={errors.social_media}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="address">
                                                Alamat
                                            </Label>
                                            <Input
                                                id="address"
                                                name="address"
                                            />
                                            <InputError
                                                message={errors.address}
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
                                                    : 'Tambah vendor'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    {canEdit && (
                        <section
                            aria-label="Kelola kategori vendor"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Kategori vendor</h2>
                            <ul className="mt-3 flex flex-wrap gap-2">
                                {categories.map((category) => (
                                    <li
                                        key={category.uuid}
                                        className="flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                                    >
                                        {category.name}
                                        <ConfirmSubmit
                                            form={VendorCategoryController.destroy.form(
                                                [
                                                    activeWedding.uuid,
                                                    category.uuid,
                                                ],
                                            )}
                                            label="hapus"
                                            context={`kategori ${category.name}`}
                                        />
                                    </li>
                                ))}
                            </ul>
                            <Form
                                {...VendorCategoryController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 flex flex-wrap items-end gap-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-1">
                                            <Label htmlFor="category-name">
                                                Nama kategori baru
                                            </Label>
                                            <Input
                                                id="category-name"
                                                name="name"
                                                required
                                                className="min-h-11"
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <Button
                                            variant="outline"
                                            disabled={processing}
                                            className="min-h-11"
                                        >
                                            Tambah kategori
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <FilterDisclosure
                        activeCount={
                            [
                                filters.search,
                                filters.status !== 'all' && filters.status,
                                filters.category !== 'all' && filters.category,
                            ].filter(Boolean).length
                        }
                    >
                        <form
                            action={base}
                            method="get"
                            className="bg-card grid gap-4 rounded-2xl border p-5 shadow-sm sm:grid-cols-[1fr_auto_auto_auto]"
                            aria-label="Filter vendor"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="search">Cari nama vendor</Label>
                                <div className="relative">
                                    <Search
                                        className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                        aria-hidden="true"
                                    />
                                    <Input
                                        id="search"
                                        name="search"
                                        defaultValue={filters.search}
                                        placeholder="cth. Melati"
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={filters.status || 'all'}
                                    className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                >
                                    <option value="all">Semua status</option>
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
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
                                <Button
                                    type="submit"
                                    className="min-h-11 w-full"
                                >
                                    Terapkan filter
                                </Button>
                            </div>
                        </form>
                    </FilterDisclosure>

                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[960px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Vendor</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Rating</th>
                                        <th className="px-4 py-3">Kontak</th>
                                        <th className="px-4 py-3 text-right">
                                            Item anggaran
                                        </th>
                                        {canEdit && (
                                            <th className="px-4 py-3 text-right">
                                                Aksi
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {vendors.data.map((vendor) => (
                                        <VendorTableRows
                                            key={vendor.uuid}
                                            vendor={vendor}
                                            weddingUuid={activeWedding.uuid}
                                            attachments={
                                                vendorAttachments[
                                                    vendor.uuid
                                                ] ?? []
                                            }
                                            canEdit={canEdit}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {vendors.data.map((vendor) => (
                                <article key={vendor.uuid} className="p-4">
                                    <p className="font-semibold">
                                        {vendor.name}
                                    </p>
                                    <p className="mt-1 text-sm">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                statusStyles[vendor.status] ??
                                                ''
                                            }`}
                                        >
                                            {statusNames[vendor.status] ??
                                                vendor.status}
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {vendor.category ?? 'Tanpa kategori'} ·
                                        rating{' '}
                                        {ratingLabel(vendor.internalRating)}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {vendor.contacts
                                            .map(
                                                (contact) =>
                                                    contact.name +
                                                    (contact.phone
                                                        ? ` (${contact.phone})`
                                                        : ''),
                                            )
                                            .join(', ') || 'Belum ada kontak'}
                                    </p>
                                    {canEdit && (
                                        <details className="mt-3">
                                            <summary className="text-muted-foreground cursor-pointer text-xs underline">
                                                kelola kontak
                                            </summary>
                                            <ul className="mt-2 space-y-1">
                                                {vendor.contacts.map(
                                                    (contact) => (
                                                        <li
                                                            key={contact.uuid}
                                                            className="text-muted-foreground flex items-center justify-between gap-2 text-xs"
                                                        >
                                                            <span>
                                                                {contact.name}
                                                                {contact.phone
                                                                    ? ` · ${contact.phone}`
                                                                    : ''}
                                                            </span>
                                                            <ConfirmSubmit
                                                                form={VendorContactController.destroy.form(
                                                                    [
                                                                        activeWedding.uuid,
                                                                        vendor.uuid,
                                                                        contact.uuid,
                                                                    ],
                                                                )}
                                                                label="hapus"
                                                                context={`kontak ${contact.name}`}
                                                            />
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                            <Form
                                                {...VendorContactController.store.form(
                                                    [
                                                        activeWedding.uuid,
                                                        vendor.uuid,
                                                    ],
                                                )}
                                                className="mt-3 grid gap-2"
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <Input
                                                            name="name"
                                                            placeholder="Nama kontak"
                                                            required
                                                            aria-label="Nama kontak"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.name
                                                            }
                                                        />
                                                        <Input
                                                            name="phone"
                                                            placeholder="No. telepon"
                                                            aria-label="No. telepon"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.phone
                                                            }
                                                        />
                                                        <label className="flex items-center gap-2 text-xs">
                                                            <input
                                                                type="checkbox"
                                                                name="is_primary"
                                                                value="1"
                                                            />
                                                            Kontak utama
                                                        </label>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Tambah kontak
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        </details>
                                    )}
                                    <VendorExtras
                                        weddingUuid={activeWedding.uuid}
                                        vendor={vendor}
                                        attachments={
                                            vendorAttachments[vendor.uuid] ?? []
                                        }
                                        canEdit={canEdit}
                                    />
                                    {canEdit && (
                                        <ConfirmSubmit
                                            form={VendorController.destroy.form(
                                                [
                                                    activeWedding.uuid,
                                                    vendor.uuid,
                                                ],
                                            )}
                                            label="hapus vendor"
                                            context={`vendor ${vendor.name}`}
                                        />
                                    )}
                                </article>
                            ))}
                        </div>
                        {vendors.data.length === 0 && (
                            <div className="p-10 text-center">
                                <Store
                                    className="text-muted-foreground mx-auto size-6"
                                    aria-hidden="true"
                                />
                                <h2 className="mt-3 font-semibold">
                                    Belum ada vendor yang cocok
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Ubah filter atau tambahkan vendor pertama
                                    lewat formulir di atas.
                                </p>
                            </div>
                        )}
                    </section>

                    <nav
                        aria-label="Paginasi vendor"
                        className="mt-5 flex flex-wrap gap-2"
                    >
                        {vendors.links.map((link, index) =>
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

function VendorTableRows({
    vendor,
    weddingUuid,
    attachments,
    canEdit,
}: {
    vendor: VendorRow;
    weddingUuid: string;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
    canEdit: boolean;
}) {
    const [open, setOpen] = useState(false);
    const expandable = attachments.length > 0 || canEdit;

    return (
        <>
            <tr>
                <td className="px-4 py-3 font-medium">
                    {vendor.name}
                    {vendor.website && (
                        <a
                            href={vendor.website}
                            target="_blank"
                            rel="noreferrer noopener"
                            className="text-primary mt-1 block text-xs hover:underline"
                        >
                            {vendor.website}
                        </a>
                    )}
                </td>
                <td className="px-4 py-3">{vendor.category ?? '—'}</td>
                <td className="px-4 py-3">
                    <span
                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                            statusStyles[vendor.status] ?? ''
                        }`}
                    >
                        {statusNames[vendor.status] ?? vendor.status}
                    </span>
                </td>
                <td className="px-4 py-3 tabular-nums">
                    {ratingLabel(vendor.internalRating)}
                </td>
                <td className="px-4 py-3">
                    {vendor.contacts.length === 0
                        ? '—'
                        : vendor.contacts
                              .map(
                                  (contact) =>
                                      contact.name +
                                      (contact.isPrimary ? ' (utama)' : ''),
                              )
                              .join(', ')}
                    {canEdit && (
                        <details className="mt-2">
                            <summary className="text-muted-foreground cursor-pointer text-xs underline">
                                kelola kontak
                            </summary>
                            <ul className="mt-2 space-y-1">
                                {vendor.contacts.map((contact) => (
                                    <li
                                        key={contact.uuid}
                                        className="text-muted-foreground flex items-center justify-between gap-2 text-xs"
                                    >
                                        <span>
                                            {contact.name}
                                            {contact.phone
                                                ? ` · ${contact.phone}`
                                                : ''}
                                        </span>
                                        <ConfirmSubmit
                                            form={VendorContactController.destroy.form(
                                                [
                                                    weddingUuid,
                                                    vendor.uuid,
                                                    contact.uuid,
                                                ],
                                            )}
                                            label="hapus"
                                            context={`kontak ${contact.name}`}
                                        />
                                    </li>
                                ))}
                            </ul>
                            <Form
                                {...VendorContactController.store.form([
                                    weddingUuid,
                                    vendor.uuid,
                                ])}
                                className="mt-3 grid gap-2"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <Input
                                            name="name"
                                            placeholder="Nama kontak"
                                            required
                                            aria-label="Nama kontak"
                                        />
                                        <InputError message={errors.name} />
                                        <Input
                                            name="phone"
                                            placeholder="No. telepon"
                                            aria-label="No. telepon"
                                        />
                                        <InputError message={errors.phone} />
                                        <label className="flex items-center gap-2 text-xs">
                                            <input
                                                type="checkbox"
                                                name="is_primary"
                                                value="1"
                                            />
                                            Kontak utama
                                        </label>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            Tambah kontak
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </details>
                    )}
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {vendor.budgetItemsCount}
                </td>
                <td className="px-4 py-3 text-right">
                    <div className="inline-flex items-center justify-end gap-2">
                        {expandable && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="min-h-9"
                                aria-expanded={open}
                                aria-label={
                                    open
                                        ? `Tutup detail ${vendor.name}`
                                        : `Buka detail ${vendor.name}`
                                }
                                onClick={() => setOpen((was) => !was)}
                            >
                                <ChevronDown
                                    className={`size-4 transition-transform ${
                                        open ? 'rotate-180' : ''
                                    }`}
                                    aria-hidden="true"
                                />
                            </Button>
                        )}
                        {canEdit && (
                            <ConfirmSubmit
                                form={VendorController.destroy.form([
                                    weddingUuid,
                                    vendor.uuid,
                                ])}
                                label="hapus"
                                context={`vendor ${vendor.name}`}
                            />
                        )}
                    </div>
                </td>
            </tr>
            {open && (
                <tr>
                    <td
                        colSpan={canEdit ? 7 : 6}
                        className="bg-muted/30 px-4 py-4"
                    >
                        <VendorPanels
                            weddingUuid={weddingUuid}
                            vendor={vendor}
                            attachments={attachments}
                            canEdit={canEdit}
                        />
                    </td>
                </tr>
            )}
        </>
    );
}

function VendorExtras({
    weddingUuid,
    vendor,
    attachments,
    canEdit,
}: {
    weddingUuid: string;
    vendor: VendorRow;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
    canEdit: boolean;
}) {
    const [open, setOpen] = useState(false);
    if (attachments.length === 0 && !canEdit) {
        return null;
    }

    return (
        <div className="mt-3">
            <button
                type="button"
                className="text-muted-foreground flex items-center gap-1 text-xs underline"
                aria-expanded={open}
                onClick={() => setOpen((was) => !was)}
            >
                <ChevronDown
                    className={`size-3.5 transition-transform ${
                        open ? 'rotate-180' : ''
                    }`}
                    aria-hidden="true"
                />
                Detail &amp; lampiran
            </button>
            {open && (
                <div className="mt-2">
                    <VendorPanels
                        weddingUuid={weddingUuid}
                        vendor={vendor}
                        attachments={attachments}
                        canEdit={canEdit}
                    />
                </div>
            )}
        </div>
    );
}

function VendorPanels({
    weddingUuid,
    vendor,
    attachments,
    canEdit,
}: {
    weddingUuid: string;
    vendor: VendorRow;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
    canEdit: boolean;
}) {
    if (attachments.length === 0 && !canEdit) {
        return null;
    }

    return (
        <AttachmentsPanel
            weddingUuid={weddingUuid}
            module="vendor"
            subjectUuid={vendor.uuid}
            attachments={attachments}
            canEdit={canEdit}
            context={vendor.name}
        />
    );
}
