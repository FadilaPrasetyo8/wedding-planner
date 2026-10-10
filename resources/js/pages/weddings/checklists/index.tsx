import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronDown,
    Clock,
    Download,
    Pencil,
    Plus,
    Search,
} from 'lucide-react';
import { useState } from 'react';
import ChecklistController from '@/actions/App/Http/Controllers/Logistics/ChecklistController';
import { AttachmentsPanel } from '@/components/attachments-panel';
import { ConfirmSubmit } from '@/components/confirm-submit';
import FilterDisclosure from '@/components/filter-disclosure';
import { CustomFieldsPanel } from '@/components/custom-fields-panel';
import FormDialog from '@/components/form-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionTabs } from '@/components/section-tabs';
import { exportUrl } from '@/lib/export-url';
import { formatDate, formatRupiah } from '@/lib/format';
import { index as checklistsIndex } from '@/routes/weddings/checklists';
import type { ActiveWedding } from '@/types';
import ChecklistItemForm, { type ChecklistItemValue } from './item-form';
import {
    checklistProgressLabel,
    checklistRowTone,
    type ChecklistSummary,
} from './checklist-display';

type ChecklistItemRow = {
    uuid: string;
    name: string;
    quantity: number;
    estimatedCost: number | null;
    actualCost: number | null;
    estimatedTotal: number;
    actualTotal: number;
    deadline: string | null;
    isOverdue: boolean;
    purchaseUrl: string | null;
    notes: string | null;
    group: string | null;
    groupUuid: string | null;
    category: string | null;
    categoryUuid: string | null;
    status: string | null;
    statusUuid: string | null;
    statusColor: string | null;
    isCompleted: boolean;
    assigneeName: string | null;
    assigneeMemberId: number | null;
};
type PageData = {
    data: ChecklistItemRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type StatusOption = {
    uuid: string;
    name: string;
    color: string;
    isCompleted: boolean;
};
type CategoryOption = { uuid: string; name: string };
type MemberOption = { id: number; name: string };

const toneStyles: Record<ReturnType<typeof checklistRowTone>, string> = {
    completed:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    overdue: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
    open: 'bg-muted text-muted-foreground',
};

export default function ChecklistIndex({
    type,
    typeLabel,
    items,
    statuses,
    categories,
    members,
    summary,
    filters,
    customFieldDefinitions,
    customValues,
    itemAttachments,
    canEdit,
    canExport,
}: {
    type: string;
    typeLabel: string;
    items: PageData;
    statuses: StatusOption[];
    categories: CategoryOption[];
    members: MemberOption[];
    summary: ChecklistSummary;
    filters: { search: string; status: string; category: string };
    customFieldDefinitions: Parameters<
        typeof CustomFieldsPanel
    >[0]['definitions'];
    customValues: Record<string, Record<string, unknown>>;
    itemAttachments: Record<
        string,
        Parameters<typeof AttachmentsPanel>[0]['attachments']
    >;
    canEdit: boolean;
    canExport: boolean;
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const base = checklistsIndex([activeWedding.uuid, type]).url;
    const isSeserahan = type === 'seserahan';
    const hasCustomFields = customFieldDefinitions.length > 0;
    const [editing, setEditing] = useState<ChecklistItemValue | null>(null);
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title={typeLabel} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                {typeLabel}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {items.total} item ·{' '}
                                <span className="tabular-nums">
                                    {checklistProgressLabel(summary)}
                                </span>{' '}
                                · estimasi{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.estimatedTotal)}
                                </span>{' '}
                                · aktual{' '}
                                <span className="tabular-nums">
                                    {formatRupiah(summary.actualTotal)}
                                </span>
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {canExport && (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="min-h-11"
                                >
                                    <a
                                        href={exportUrl(
                                            `/weddings/${activeWedding.uuid}/exports/checklist/${type}`,
                                            { ...filters, type },
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
                        </div>
                    </header>

                    <SectionTabs
                        ariaLabel="Jenis daftar persiapan"
                        items={[
                            {
                                title: 'Seserahan',
                                href: checklistsIndex([
                                    activeWedding.uuid,
                                    'seserahan',
                                ]),
                            },
                            {
                                title: 'Administrasi',
                                href: checklistsIndex([
                                    activeWedding.uuid,
                                    'administrasi',
                                ]),
                            },
                        ]}
                    />

                    {summary.overdueCount > 0 && (
                        <p
                            role="alert"
                            className="mt-5 flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200"
                        >
                            <Clock
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            {summary.overdueCount} item telah melewati tenggat
                            dan belum selesai.
                        </p>
                    )}

                    {canEdit && (
                        <div className="mt-5 flex justify-end">
                            <Button
                                className="min-h-11"
                                onClick={() => setCreating(true)}
                            >
                                <Plus aria-hidden="true" />
                                Tambah item {typeLabel.toLowerCase()}
                            </Button>
                        </div>
                    )}

                    {canEdit && (
                        <section
                            aria-label="Kelola referensi"
                            className="bg-card mt-5 grid gap-6 rounded-2xl border p-5 shadow-sm md:grid-cols-2"
                        >
                            <div>
                                <h2 className="font-semibold">
                                    Status {typeLabel.toLowerCase()}
                                </h2>
                                <ul className="mt-3 flex flex-wrap gap-2">
                                    {statuses.map((status) => (
                                        <li
                                            key={status.uuid}
                                            className="flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                                        >
                                            {status.name}
                                            {status.isCompleted && (
                                                <CheckCircle2
                                                    className="size-3.5"
                                                    aria-label="status selesai"
                                                />
                                            )}
                                            {canEdit && (
                                                <ConfirmSubmit
                                                    form={ChecklistController.destroyReference.form(
                                                        [
                                                            activeWedding.uuid,
                                                            type,
                                                            'statuses',
                                                            status.uuid,
                                                        ],
                                                    )}
                                                    label="hapus"
                                                    context={`status ${status.name}`}
                                                />
                                            )}
                                        </li>
                                    ))}
                                </ul>
                                <Form
                                    {...ChecklistController.storeReference.form(
                                        [activeWedding.uuid, type, 'statuses'],
                                    )}
                                    className="mt-4 flex flex-wrap items-end gap-3"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="type"
                                                value={type}
                                            />
                                            <div className="grid gap-1">
                                                <Label htmlFor="status-name">
                                                    Nama status baru
                                                </Label>
                                                <Input
                                                    id="status-name"
                                                    name="name"
                                                    required
                                                    className="min-h-11"
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>
                                            <div className="grid gap-1">
                                                <Label htmlFor="status-completed">
                                                    Menandai selesai
                                                </Label>
                                                <select
                                                    id="status-completed"
                                                    name="is_completed"
                                                    defaultValue="0"
                                                    className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                >
                                                    <option value="0">
                                                        Tidak
                                                    </option>
                                                    <option value="1">
                                                        Ya
                                                    </option>
                                                </select>
                                            </div>
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                className="min-h-11"
                                            >
                                                Tambah status
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </div>
                            <div>
                                <h2 className="font-semibold">
                                    Kategori {typeLabel.toLowerCase()}
                                </h2>
                                <ul className="mt-3 flex flex-wrap gap-2">
                                    {categories.map((category) => (
                                        <li
                                            key={category.uuid}
                                            className="flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                                        >
                                            {category.name}
                                            {canEdit && (
                                                <ConfirmSubmit
                                                    form={ChecklistController.destroyReference.form(
                                                        [
                                                            activeWedding.uuid,
                                                            type,
                                                            'categories',
                                                            category.uuid,
                                                        ],
                                                    )}
                                                    label="hapus"
                                                    context={`kategori ${category.name}`}
                                                />
                                            )}
                                        </li>
                                    ))}
                                </ul>
                                <Form
                                    {...ChecklistController.storeReference.form(
                                        [
                                            activeWedding.uuid,
                                            type,
                                            'categories',
                                        ],
                                    )}
                                    className="mt-4 flex flex-wrap items-end gap-3"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="type"
                                                value={type}
                                            />
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
                                                <InputError
                                                    message={errors.name}
                                                />
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
                            </div>
                        </section>
                    )}

                    <FilterDisclosure
                        activeCount={
                            [
                                filters.search,
                                filters.status,
                                filters.category,
                            ].filter((value) => value && value !== 'all').length
                        }
                    >
                        <form
                            action={base}
                            method="get"
                            className="bg-card grid gap-4 rounded-2xl border p-5 shadow-sm sm:grid-cols-[1fr_auto_auto_auto]"
                            aria-label={`Filter ${typeLabel}`}
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
                                        placeholder="cth. Kalung"
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
                                    {statuses.map((status) => (
                                        <option
                                            key={status.uuid}
                                            value={status.uuid}
                                        >
                                            {status.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category">Kategori</Label>
                                <select
                                    id="category"
                                    name="category"
                                    defaultValue={filters.category || ''}
                                    className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                >
                                    <option value="">Semua kategori</option>
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
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3">
                                            Penanggung jawab
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Jumlah
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Estimasi total
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Aktual total
                                        </th>
                                        <th className="px-4 py-3">Tenggat</th>
                                        {canEdit && (
                                            <th className="px-4 py-3 text-right">
                                                Aksi
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {items.data.map((item) => (
                                        <ItemTableRows
                                            key={item.uuid}
                                            item={item}
                                            type={type}
                                            weddingUuid={activeWedding.uuid}
                                            statuses={statuses}
                                            canEdit={canEdit}
                                            onEdit={() =>
                                                setEditing(toItemValue(item))
                                            }
                                            hasCustomFields={hasCustomFields}
                                            definitions={customFieldDefinitions}
                                            values={
                                                customValues[item.uuid] ?? {}
                                            }
                                            attachments={
                                                itemAttachments[item.uuid] ?? []
                                            }
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {items.data.map((item) => (
                                <article key={item.uuid} className="p-4">
                                    <p className="font-semibold">{item.name}</p>
                                    {canEdit ? (
                                        <div className="mt-2">
                                            <ItemStatusForm
                                                weddingUuid={activeWedding.uuid}
                                                type={type}
                                                item={item}
                                                statuses={statuses}
                                            />
                                        </div>
                                    ) : (
                                        <p className="mt-1 text-sm">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    toneStyles[
                                                        checklistRowTone(item)
                                                    ]
                                                }`}
                                            >
                                                {item.isOverdue
                                                    ? 'Terlambat'
                                                    : (item.status ??
                                                      'Tanpa status')}
                                            </span>
                                        </p>
                                    )}
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {item.category ?? 'Tanpa kategori'}
                                        {item.assigneeName
                                            ? ` · ${item.assigneeName}`
                                            : ''}
                                    </p>
                                    <p className="mt-1 text-sm tabular-nums">
                                        {item.quantity}× · estimasi{' '}
                                        {formatRupiah(item.estimatedTotal)}
                                        {item.actualCost !== null &&
                                            ` · aktual ${formatRupiah(
                                                item.actualTotal,
                                            )}`}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Tenggat {formatDate(item.deadline)}
                                    </p>
                                    <ItemExtras
                                        item={item}
                                        weddingUuid={activeWedding.uuid}
                                        canEdit={canEdit}
                                        hasCustomFields={hasCustomFields}
                                        definitions={customFieldDefinitions}
                                        values={customValues[item.uuid] ?? {}}
                                        attachments={
                                            itemAttachments[item.uuid] ?? []
                                        }
                                    />
                                    {canEdit && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="min-h-11"
                                                onClick={() =>
                                                    setEditing(
                                                        toItemValue(item),
                                                    )
                                                }
                                            >
                                                <Pencil aria-hidden="true" />
                                                Edit
                                            </Button>
                                            <ConfirmSubmit
                                                form={ChecklistController.destroy.form(
                                                    [
                                                        activeWedding.uuid,
                                                        type,
                                                        item.uuid,
                                                    ],
                                                )}
                                                label="hapus item"
                                                context={`item ${item.name}`}
                                            />
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                        {items.data.length === 0 && (
                            <div className="p-10 text-center">
                                <CheckCircle2
                                    className="text-muted-foreground mx-auto size-6"
                                    aria-hidden="true"
                                />
                                <h2 className="mt-3 font-semibold">
                                    Belum ada item {typeLabel.toLowerCase()}{' '}
                                    yang cocok
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Ubah filter atau tambahkan item pertama
                                    lewat formulir di atas.
                                </p>
                            </div>
                        )}
                    </section>

                    <nav
                        aria-label={`Paginasi ${typeLabel}`}
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

            <FormDialog
                open={creating}
                onOpenChange={setCreating}
                title={`Tambah item ${typeLabel.toLowerCase()}`}
                description="Catat item baru beserta tenggat dan penanggung jawabnya."
            >
                <ChecklistItemForm
                    type={type}
                    isSeserahan={isSeserahan}
                    categories={categories}
                    statuses={statuses}
                    members={members}
                    onCancel={() => setCreating(false)}
                    onSuccess={() => setCreating(false)}
                />
            </FormDialog>

            <FormDialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditing(null);
                    }
                }}
                title="Edit item"
                description={`Perbarui detail item ${typeLabel.toLowerCase()}.`}
            >
                {editing && (
                    <ChecklistItemForm
                        key={editing.uuid}
                        item={editing}
                        type={type}
                        isSeserahan={isSeserahan}
                        categories={categories}
                        statuses={statuses}
                        members={members}
                        onCancel={() => setEditing(null)}
                        onSuccess={() => setEditing(null)}
                    />
                )}
            </FormDialog>
        </>
    );
}

function toItemValue(item: ChecklistItemRow): ChecklistItemValue {
    return {
        uuid: item.uuid,
        name: item.name,
        quantity: item.quantity,
        estimatedCost: item.estimatedCost,
        actualCost: item.actualCost,
        deadline: item.deadline,
        purchaseUrl: item.purchaseUrl,
        notes: item.notes,
        groupUuid: item.groupUuid,
        categoryUuid: item.categoryUuid,
        statusUuid: item.statusUuid,
        assigneeMemberId: item.assigneeMemberId,
    };
}

function ItemStatusForm({
    weddingUuid,
    type,
    item,
    statuses,
}: {
    weddingUuid: string;
    type: string;
    item: ChecklistItemRow;
    statuses: StatusOption[];
}) {
    return (
        <Form
            {...ChecklistController.update.form([weddingUuid, type, item.uuid])}
        >
            {({ processing }) => (
                <>
                    <label className="sr-only" htmlFor={`status-${item.uuid}`}>
                        Status {item.name}
                    </label>
                    <select
                        id={`status-${item.uuid}`}
                        name="checklist_status_uuid"
                        defaultValue={item.statusUuid ?? ''}
                        disabled={processing}
                        onChange={(event) =>
                            event.currentTarget.form?.requestSubmit()
                        }
                        className="border-input bg-background min-h-9 rounded-md border px-2 py-1 text-xs"
                    >
                        <option value="">Tanpa status</option>
                        {statuses.map((status) => (
                            <option key={status.uuid} value={status.uuid}>
                                {status.name}
                            </option>
                        ))}
                    </select>
                    <input type="hidden" name="name" value={item.name} />
                    <input
                        type="hidden"
                        name="quantity"
                        value={item.quantity}
                    />
                    {item.estimatedCost !== null && (
                        <input
                            type="hidden"
                            name="estimated_cost"
                            value={item.estimatedCost}
                        />
                    )}
                    {item.actualCost !== null && (
                        <input
                            type="hidden"
                            name="actual_cost"
                            value={item.actualCost}
                        />
                    )}
                    {item.deadline !== null && (
                        <input
                            type="hidden"
                            name="deadline"
                            value={item.deadline}
                        />
                    )}
                    <input
                        type="hidden"
                        name="checklist_category_uuid"
                        value={item.categoryUuid ?? ''}
                    />
                    <input
                        type="hidden"
                        name="checklist_group_uuid"
                        value={item.groupUuid ?? ''}
                    />
                    {type === 'seserahan' && (
                        <input
                            type="hidden"
                            name="purchase_url"
                            value={item.purchaseUrl ?? ''}
                        />
                    )}
                    <input
                        type="hidden"
                        name="assignee_member_id"
                        value={item.assigneeMemberId ?? ''}
                    />
                    {item.notes !== null && (
                        <input type="hidden" name="notes" value={item.notes} />
                    )}
                    <span
                        className={`ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                            toneStyles[checklistRowTone(item)]
                        }`}
                    >
                        {item.isOverdue
                            ? 'Terlambat'
                            : (item.status ?? 'Tanpa status')}
                    </span>
                    {processing && (
                        <span className="text-muted-foreground ml-2 text-xs">
                            Menyimpan…
                        </span>
                    )}
                </>
            )}
        </Form>
    );
}

/**
 * Desktop table row pair: the item row plus an optional expanded details
 * row rendering the custom-fields and attachments panels (D-027/D-028).
 */
function ItemTableRows({
    item,
    type,
    weddingUuid,
    statuses,
    canEdit,
    onEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    item: ChecklistItemRow;
    type: string;
    weddingUuid: string;
    statuses: StatusOption[];
    canEdit: boolean;
    onEdit: () => void;
    hasCustomFields: boolean;
    definitions: Parameters<typeof CustomFieldsPanel>[0]['definitions'];
    values: Record<string, unknown>;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
}) {
    const [open, setOpen] = useState(false);
    const expandable = hasCustomFields || attachments.length > 0 || canEdit;

    return (
        <>
            <tr>
                <td className="px-4 py-3 font-medium">
                    {expandable && (
                        <button
                            type="button"
                            onClick={() => setOpen((v) => !v)}
                            aria-expanded={open}
                            className="hover:bg-muted mr-2 inline-flex size-6 items-center justify-center rounded-full text-xs transition-transform focus-visible:outline-2 focus-visible:outline-offset-2"
                            aria-label={`${open ? 'Tutup' : 'Buka'} detail ${item.name}`}
                        >
                            <ChevronDown
                                className={`size-4 transition-transform ${
                                    open ? 'rotate-180' : ''
                                }`}
                                aria-hidden="true"
                            />
                        </button>
                    )}
                    {item.name}
                    {item.notes && (
                        <span className="text-muted-foreground mt-1 block text-xs">
                            {item.notes}
                        </span>
                    )}
                </td>
                <td className="px-4 py-3">
                    {canEdit ? (
                        <ItemStatusForm
                            weddingUuid={weddingUuid}
                            type={type}
                            item={item}
                            statuses={statuses}
                        />
                    ) : (
                        <span
                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                toneStyles[checklistRowTone(item)]
                            }`}
                        >
                            {item.isOverdue
                                ? 'Terlambat'
                                : (item.status ?? 'Tanpa status')}
                        </span>
                    )}
                </td>
                <td className="px-4 py-3">{item.category ?? '—'}</td>
                <td className="px-4 py-3">{item.assigneeName ?? '—'}</td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {item.quantity}
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {formatRupiah(item.estimatedTotal)}
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {item.actualCost === null
                        ? '—'
                        : formatRupiah(item.actualTotal)}
                </td>
                <td className="px-4 py-3">{formatDate(item.deadline)}</td>
                {canEdit && (
                    <td className="px-4 py-3 text-right">
                        <div className="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                className="min-h-11"
                                onClick={onEdit}
                            >
                                <Pencil aria-hidden="true" />
                                Edit
                            </Button>
                            <ConfirmSubmit
                                form={ChecklistController.destroy.form([
                                    weddingUuid,
                                    type,
                                    item.uuid,
                                ])}
                                label="hapus"
                                context={`item ${item.name}`}
                            />
                        </div>
                    </td>
                )}
            </tr>
            {open && expandable && (
                <tr>
                    <td
                        colSpan={canEdit ? 9 : 8}
                        className="bg-muted/30 px-4 py-4"
                    >
                        <ItemPanels
                            item={item}
                            weddingUuid={weddingUuid}
                            canEdit={canEdit}
                            hasCustomFields={hasCustomFields}
                            definitions={definitions}
                            values={values}
                            attachments={attachments}
                        />
                    </td>
                </tr>
            )}
        </>
    );
}

/**
 * Mobile inline expander plus the shared panel pair.
 */
function ItemExtras({
    item,
    weddingUuid,
    canEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    item: ChecklistItemRow;
    weddingUuid: string;
    canEdit: boolean;
    hasCustomFields: boolean;
    definitions: Parameters<typeof CustomFieldsPanel>[0]['definitions'];
    values: Record<string, unknown>;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
}) {
    const [open, setOpen] = useState(false);
    const expandable = hasCustomFields || attachments.length > 0 || canEdit;

    if (!expandable) return null;

    return (
        <div className="mt-3">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                className="text-muted-foreground inline-flex items-center gap-1 text-xs font-medium hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
            >
                <ChevronDown
                    className={`size-4 transition-transform ${
                        open ? 'rotate-180' : ''
                    }`}
                    aria-hidden="true"
                />
                {open ? 'Tutup detail' : 'Detail & lampiran'}
            </button>
            {open && (
                <div className="mt-3">
                    <ItemPanels
                        item={item}
                        weddingUuid={weddingUuid}
                        canEdit={canEdit}
                        hasCustomFields={hasCustomFields}
                        definitions={definitions}
                        values={values}
                        attachments={attachments}
                    />
                </div>
            )}
        </div>
    );
}

function ItemPanels({
    item,
    weddingUuid,
    canEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    item: ChecklistItemRow;
    weddingUuid: string;
    canEdit: boolean;
    hasCustomFields: boolean;
    definitions: Parameters<typeof CustomFieldsPanel>[0]['definitions'];
    values: Record<string, unknown>;
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
}) {
    return (
        <div className="grid gap-4">
            {hasCustomFields && (
                <CustomFieldsPanel
                    weddingUuid={weddingUuid}
                    module="checklist_item"
                    subjectUuid={item.uuid}
                    definitions={definitions}
                    values={values}
                    canEdit={canEdit}
                />
            )}
            <AttachmentsPanel
                weddingUuid={weddingUuid}
                module="checklist_item"
                subjectUuid={item.uuid}
                attachments={attachments}
                canEdit={canEdit}
                context={item.name}
            />
        </div>
    );
}
