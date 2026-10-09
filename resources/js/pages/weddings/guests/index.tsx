import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, Download, Plus, Search, UsersRound } from 'lucide-react';
import { useState } from 'react';
import GuestController from '@/actions/App/Http/Controllers/Logistics/GuestController';
import { AttachmentsPanel } from '@/components/attachments-panel';
import { ConfirmSubmit } from '@/components/confirm-submit';
import { CustomFieldsPanel } from '@/components/custom-fields-panel';
import InputError from '@/components/input-error';
import { exportUrl } from '@/lib/export-url';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as guestsIndex } from '@/routes/weddings/guests';
import type { ActiveWedding } from '@/types';
import { guestSummaryLabel, invitationTone, rsvpTone } from './guest-display';

type GuestRow = {
    uuid: string;
    name: string;
    phone: string | null;
    invitationCount: number;
    paxCount: number;
    invitationStatus: string;
    rsvpStatus: string | null;
    notes: string | null;
    side: string | null;
    sideUuid: string | null;
    group: string | null;
    groupUuid: string | null;
};
type PageData = {
    data: GuestRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type StatusOption = { value: string; label: string };
type ReferenceOption = { uuid: string; name: string };

const invitationStyles: Record<string, string> = {
    notSent: 'bg-muted text-muted-foreground',
    sent: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    failed: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
};

const invitationNames: Record<string, string> = {
    not_sent: 'Belum dikirim',
    sent: 'Terkirim',
    delivered: 'Diterima',
    failed: 'Gagal',
};

const rsvpStyles: Record<string, string> = {
    attending:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    declined: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
    pending:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
};

const rsvpNames: Record<string, string> = {
    pending: 'Menunggu jawaban',
    attending: 'Hadir',
    not_attending: 'Tidak hadir',
    tentative: 'Masih ragu',
};

const selectClass =
    'border-input bg-background min-h-11 rounded-md border px-3 text-sm';

export default function GuestIndex({
    guests,
    filters,
    sides,
    groups,
    invitationStatuses,
    rsvpStatuses,
    aggregates,
    customFieldDefinitions,
    customValues,
    guestAttachments,
    canEdit,
    canExport,
}: {
    guests: PageData;
    filters: {
        search: string;
        side: string;
        group: string;
        invitation: string;
        rsvp: string;
    };
    sides: ReferenceOption[];
    groups: ReferenceOption[];
    invitationStatuses: StatusOption[];
    rsvpStatuses: StatusOption[];
    aggregates: {
        guestCount: number;
        invitationCount: number;
        paxCount: number;
        attendingCount: number;
    };
    customFieldDefinitions: Parameters<
        typeof CustomFieldsPanel
    >[0]['definitions'];
    customValues: Record<string, Record<string, unknown>>;
    guestAttachments: Record<
        string,
        Parameters<typeof AttachmentsPanel>[0]['attachments']
    >;
    canEdit: boolean;
    canExport: boolean;
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const base = guestsIndex(activeWedding.uuid).url;
    const hasCustomFields = customFieldDefinitions.length > 0;

    return (
        <>
            <Head title="Tamu" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Tamu
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {guestSummaryLabel(aggregates)}
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
                                            `/weddings/${activeWedding.uuid}/exports/guests`,
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
                        </div>
                    </header>

                    {canEdit && (
                        <section
                            aria-label="Tambah tamu"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">Tamu baru</h2>
                            <Form
                                {...GuestController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="name">
                                                Nama tamu
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                placeholder="cth. Keluarga Wijaya"
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="guest_side_uuid">
                                                Sisi
                                            </Label>
                                            <select
                                                id="guest_side_uuid"
                                                name="guest_side_uuid"
                                                defaultValue=""
                                                className={selectClass}
                                            >
                                                <option value="">
                                                    Tanpa sisi
                                                </option>
                                                {sides.map((side) => (
                                                    <option
                                                        key={side.uuid}
                                                        value={side.uuid}
                                                    >
                                                        {side.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.guest_side_uuid}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="guest_group_uuid">
                                                Grup
                                            </Label>
                                            <select
                                                id="guest_group_uuid"
                                                name="guest_group_uuid"
                                                defaultValue=""
                                                className={selectClass}
                                            >
                                                <option value="">
                                                    Tanpa grup
                                                </option>
                                                {groups.map((group) => (
                                                    <option
                                                        key={group.uuid}
                                                        value={group.uuid}
                                                    >
                                                        {group.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.guest_group_uuid
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="phone">
                                                No. telepon
                                            </Label>
                                            <Input
                                                id="phone"
                                                name="phone"
                                                placeholder="cth. 0812…"
                                            />
                                            <InputError
                                                message={errors.phone}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="invitation_count">
                                                Jumlah undangan
                                            </Label>
                                            <Input
                                                id="invitation_count"
                                                name="invitation_count"
                                                type="number"
                                                min={0}
                                                max={100}
                                                defaultValue={1}
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.invitation_count
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="pax_count">
                                                Jumlah orang (pax)
                                            </Label>
                                            <Input
                                                id="pax_count"
                                                name="pax_count"
                                                type="number"
                                                min={0}
                                                max={100}
                                                defaultValue={2}
                                                required
                                            />
                                            <InputError
                                                message={errors.pax_count}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="invitation_status">
                                                Status undangan
                                            </Label>
                                            <select
                                                id="invitation_status"
                                                name="invitation_status"
                                                defaultValue="not_sent"
                                                className={selectClass}
                                            >
                                                {invitationStatuses.map(
                                                    (status) => (
                                                        <option
                                                            key={status.value}
                                                            value={status.value}
                                                        >
                                                            {status.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.invitation_status
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="rsvp_status">
                                                Jawaban RSVP
                                            </Label>
                                            <select
                                                id="rsvp_status"
                                                name="rsvp_status"
                                                defaultValue=""
                                                className={selectClass}
                                            >
                                                <option value="">
                                                    Belum dijawab
                                                </option>
                                                {rsvpStatuses.map((status) => (
                                                    <option
                                                        key={status.value}
                                                        value={status.value}
                                                    >
                                                        {status.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.rsvp_status}
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
                                                    : 'Tambah tamu'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    {canEdit && (
                        <section
                            aria-label="Kelola referensi tamu"
                            className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                        >
                            <h2 className="font-semibold">
                                Sisi &amp; grup tamu
                            </h2>
                            <div className="mt-3 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                        Sisi
                                    </p>
                                    <ul className="mt-2 flex flex-wrap gap-2">
                                        {sides.map((side) => (
                                            <li
                                                key={side.uuid}
                                                className="flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                                            >
                                                {side.name}
                                                <ConfirmSubmit
                                                    form={GuestController.destroyReference.form(
                                                        [
                                                            activeWedding.uuid,
                                                            'sides',
                                                            side.uuid,
                                                        ],
                                                    )}
                                                    label="hapus"
                                                    context={`sisi ${side.name}`}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                    <Form
                                        {...GuestController.storeReference.form(
                                            [activeWedding.uuid, 'sides'],
                                        )}
                                        className="mt-3 flex flex-wrap items-end gap-3"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="side-name">
                                                        Sisi baru
                                                    </Label>
                                                    <Input
                                                        id="side-name"
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
                                                    Tambah sisi
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                        Grup
                                    </p>
                                    <ul className="mt-2 flex flex-wrap gap-2">
                                        {groups.map((group) => (
                                            <li
                                                key={group.uuid}
                                                className="flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                                            >
                                                {group.name}
                                                <ConfirmSubmit
                                                    form={GuestController.destroyReference.form(
                                                        [
                                                            activeWedding.uuid,
                                                            'groups',
                                                            group.uuid,
                                                        ],
                                                    )}
                                                    label="hapus"
                                                    context={`grup ${group.name}`}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                    <Form
                                        {...GuestController.storeReference.form(
                                            [activeWedding.uuid, 'groups'],
                                        )}
                                        className="mt-3 flex flex-wrap items-end gap-3"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-1">
                                                    <Label htmlFor="group-name">
                                                        Grup baru
                                                    </Label>
                                                    <Input
                                                        id="group-name"
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
                                                    Tambah grup
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </div>
                            </div>
                        </section>
                    )}

                    <form
                        action={base}
                        method="get"
                        className="bg-card mt-5 grid gap-4 rounded-2xl border p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto_auto]"
                        aria-label="Filter tamu"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="search">Cari nama tamu</Label>
                            <div className="relative">
                                <Search
                                    className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="search"
                                    name="search"
                                    defaultValue={filters.search}
                                    placeholder="cth. Wijaya"
                                    className="pl-9"
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="side">Sisi</Label>
                            <select
                                id="side"
                                name="side"
                                defaultValue={filters.side || 'all'}
                                className={selectClass}
                            >
                                <option value="all">Semua sisi</option>
                                {sides.map((side) => (
                                    <option key={side.uuid} value={side.uuid}>
                                        {side.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="group">Grup</Label>
                            <select
                                id="group"
                                name="group"
                                defaultValue={filters.group || 'all'}
                                className={selectClass}
                            >
                                <option value="all">Semua grup</option>
                                {groups.map((group) => (
                                    <option key={group.uuid} value={group.uuid}>
                                        {group.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="invitation">Status undangan</Label>
                            <select
                                id="invitation"
                                name="invitation"
                                defaultValue={filters.invitation || 'all'}
                                className={selectClass}
                            >
                                <option value="all">Semua status</option>
                                {invitationStatuses.map((status) => (
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
                            <Label htmlFor="rsvp">Jawaban RSVP</Label>
                            <select
                                id="rsvp"
                                name="rsvp"
                                defaultValue={filters.rsvp || 'all'}
                                className={selectClass}
                            >
                                <option value="all">Semua jawaban</option>
                                {rsvpStatuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end sm:col-span-2 lg:col-span-1">
                            <Button type="submit" className="min-h-11 w-full">
                                Terapkan filter
                            </Button>
                        </div>
                    </form>

                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[960px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Tamu</th>
                                        <th className="px-4 py-3">Sisi</th>
                                        <th className="px-4 py-3">Grup</th>
                                        <th className="px-4 py-3 text-right">
                                            Undangan
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Pax
                                        </th>
                                        <th className="px-4 py-3">
                                            Status undangan
                                        </th>
                                        <th className="px-4 py-3">RSVP</th>
                                        {canEdit && (
                                            <th className="px-4 py-3 text-right">
                                                Aksi
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {guests.data.map((guest) => (
                                        <GuestTableRows
                                            key={guest.uuid}
                                            guest={guest}
                                            weddingUuid={activeWedding.uuid}
                                            canEdit={canEdit}
                                            hasCustomFields={hasCustomFields}
                                            definitions={customFieldDefinitions}
                                            values={
                                                customValues[guest.uuid] ?? {}
                                            }
                                            attachments={
                                                guestAttachments[guest.uuid] ??
                                                []
                                            }
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {guests.data.map((guest) => (
                                <article key={guest.uuid} className="p-4">
                                    <p className="font-semibold">
                                        {guest.name}
                                    </p>
                                    <p className="mt-1 flex flex-wrap gap-2">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                invitationStyles[
                                                    invitationTone(
                                                        guest.invitationStatus,
                                                    )
                                                ] ?? ''
                                            }`}
                                        >
                                            {invitationNames[
                                                guest.invitationStatus
                                            ] ?? guest.invitationStatus}
                                        </span>
                                        {guest.rsvpStatus !== null && (
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    rsvpStyles[
                                                        rsvpTone(
                                                            guest.rsvpStatus,
                                                        ) ?? ''
                                                    ] ?? ''
                                                }`}
                                            >
                                                {rsvpNames[guest.rsvpStatus] ??
                                                    guest.rsvpStatus}
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {guest.side ?? 'Tanpa sisi'} ·{' '}
                                        {guest.group ?? 'Tanpa grup'} ·{' '}
                                        {guest.invitationCount} undangan ·{' '}
                                        {guest.paxCount} pax
                                    </p>
                                    <GuestExtras
                                        guest={guest}
                                        weddingUuid={activeWedding.uuid}
                                        canEdit={canEdit}
                                        hasCustomFields={hasCustomFields}
                                        definitions={customFieldDefinitions}
                                        values={customValues[guest.uuid] ?? {}}
                                        attachments={
                                            guestAttachments[guest.uuid] ?? []
                                        }
                                    />
                                    {canEdit && (
                                        <ConfirmSubmit
                                            form={GuestController.destroy.form([
                                                activeWedding.uuid,
                                                guest.uuid,
                                            ])}
                                            label="hapus tamu"
                                            context={`tamu ${guest.name}`}
                                        />
                                    )}
                                </article>
                            ))}
                        </div>
                        {guests.data.length === 0 && (
                            <div className="p-10 text-center">
                                <UsersRound
                                    className="text-muted-foreground mx-auto size-6"
                                    aria-hidden="true"
                                />
                                <h2 className="mt-3 font-semibold">
                                    Belum ada tamu yang cocok
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Ubah filter atau tambahkan tamu pertama
                                    lewat formulir di atas.
                                </p>
                            </div>
                        )}
                    </section>

                    <nav
                        aria-label="Paginasi tamu"
                        className="mt-5 flex flex-wrap gap-2"
                    >
                        {guests.links.map((link, index) =>
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

/**
 * Desktop table row pair: the guest row plus an optional expanded details
 * row rendering the custom-fields and attachments panels (D-027/D-028).
 * Only shown when the wedding actually defines guest custom fields or the
 * guest has attachments — otherwise the list stays dense.
 */
function GuestTableRows({
    guest,
    weddingUuid,
    canEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    guest: GuestRow;
    weddingUuid: string;
    canEdit: boolean;
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
                            aria-label={`${open ? 'Tutup' : 'Buka'} detail ${guest.name}`}
                        >
                            <ChevronDown
                                className={`size-4 transition-transform ${
                                    open ? 'rotate-180' : ''
                                }`}
                                aria-hidden="true"
                            />
                        </button>
                    )}
                    {guest.name}
                    {guest.phone && (
                        <span className="text-muted-foreground mt-1 block text-xs">
                            {guest.phone}
                        </span>
                    )}
                </td>
                <td className="px-4 py-3">{guest.side ?? '—'}</td>
                <td className="px-4 py-3">{guest.group ?? '—'}</td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {guest.invitationCount}
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                    {guest.paxCount}
                </td>
                <td className="px-4 py-3">
                    <span
                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                            invitationStyles[
                                invitationTone(guest.invitationStatus)
                            ] ?? ''
                        }`}
                    >
                        {invitationNames[guest.invitationStatus] ??
                            guest.invitationStatus}
                    </span>
                </td>
                <td className="px-4 py-3">
                    {guest.rsvpStatus === null ? (
                        '—'
                    ) : (
                        <span
                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                rsvpStyles[rsvpTone(guest.rsvpStatus) ?? ''] ??
                                ''
                            }`}
                        >
                            {rsvpNames[guest.rsvpStatus] ?? guest.rsvpStatus}
                        </span>
                    )}
                </td>
                {canEdit && (
                    <td className="px-4 py-3 text-right">
                        <ConfirmSubmit
                            form={GuestController.destroy.form([
                                weddingUuid,
                                guest.uuid,
                            ])}
                            label="hapus"
                            context={`tamu ${guest.name}`}
                        />
                    </td>
                )}
            </tr>
            {open && expandable && (
                <tr>
                    <td
                        colSpan={canEdit ? 8 : 7}
                        className="bg-muted/30 px-4 py-4"
                    >
                        <GuestPanels
                            guest={guest}
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
function GuestExtras({
    guest,
    weddingUuid,
    canEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    guest: GuestRow;
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
                    <GuestPanels
                        guest={guest}
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

function GuestPanels({
    guest,
    weddingUuid,
    canEdit,
    hasCustomFields,
    definitions,
    values,
    attachments,
}: {
    guest: GuestRow;
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
                    module="guest"
                    subjectUuid={guest.uuid}
                    definitions={definitions}
                    values={values}
                    canEdit={canEdit}
                />
            )}
            <AttachmentsPanel
                weddingUuid={weddingUuid}
                module="guest"
                subjectUuid={guest.uuid}
                attachments={attachments}
                canEdit={canEdit}
                context={guest.name}
            />
        </div>
    );
}
