import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    Download,
    Plus,
    Printer,
    TriangleAlert,
} from 'lucide-react';
import RundownController from '@/actions/App/Http/Controllers/EventDay/RundownController';
import { ConfirmSubmit } from '@/components/confirm-submit';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionTabs } from '@/components/section-tabs';
import { exportUrl } from '@/lib/export-url';
import { formatDate } from '@/lib/format';
import { index as rundownIndex } from '@/routes/weddings/rundown';
import { print as rundownPrint } from '@/routes/weddings/rundown';
import { index as playlistIndex } from '@/routes/weddings/playlist';
import type { ActiveWedding } from '@/types';

type EventOption = {
    uuid: string;
    name: string;
    eventDate: string;
    isPrimary: boolean;
};
type SelectedEvent = { uuid: string; name: string; eventDate: string };
type RundownRow = {
    uuid: string;
    title: string;
    pic: string | null;
    notes: string | null;
    highlights: string | null;
    status: string;
    statusLabel: string;
    startTime: string | null;
    endTime: string | null;
    sortOrder: number;
    hasOverlap: boolean;
};
type StatusOption = { value: string; label: string };

const statusStyles: Record<string, string> = {
    scheduled: 'bg-muted text-muted-foreground',
    completed:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    cancelled: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
};

export default function RundownIndex({
    events,
    selectedEvent,
    items,
    statuses,
    canEdit,
    canExport,
}: {
    events: EventOption[];
    selectedEvent: SelectedEvent | null;
    items: RundownRow[];
    statuses: StatusOption[];
    canEdit: boolean;
    canExport: boolean;
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const base = rundownIndex(activeWedding.uuid).url;

    const move = (uuid: string, direction: -1 | 1) => {
        const index = items.findIndex((item) => item.uuid === uuid);
        const target = index + direction;

        if (index < 0 || target < 0 || target >= items.length) {
            return;
        }

        const reordered = [...items];
        [reordered[index], reordered[target]] = [
            reordered[target],
            reordered[index],
        ];

        router.post(
            `${base}/reorder`,
            { items: reordered.map((item) => ({ uuid: item.uuid })) },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Rundown acara" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-5xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Rundown acara
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Susunan acara harian untuk dibagikan ke panitia
                                dan vendor.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            {canExport && (
                                <Button asChild size="sm" variant="outline">
                                    <a
                                        href={exportUrl(
                                            `/weddings/${activeWedding.uuid}/exports/rundown`,
                                            { event: selectedEvent?.uuid },
                                        )}
                                    >
                                        <Download aria-hidden="true" />
                                        Ekspor CSV (Excel)
                                    </a>
                                </Button>
                            )}
                            {selectedEvent && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={rundownPrint(activeWedding.uuid, {
                                            query: {
                                                event: selectedEvent.uuid,
                                            },
                                        })}
                                    >
                                        <Printer aria-hidden="true" />
                                        Cetak
                                    </Link>
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
                        ariaLabel="Navigasi hari acara"
                        items={[
                            { title: 'Rundown', href: base },
                            {
                                title: 'Playlist',
                                href: playlistIndex(activeWedding.uuid).url,
                            },
                        ]}
                    />

                    {events.length === 0 ? (
                        <div className="bg-card mt-5 rounded-2xl border p-10 text-center shadow-sm">
                            <CalendarClock
                                className="text-muted-foreground mx-auto size-8"
                                aria-hidden="true"
                            />
                            <h2 className="mt-3 font-semibold">
                                Belum ada acara
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Tambahkan acara terlebih dahulu lewat menu Acara
                                untuk menyusun rundown.
                            </p>
                            <Button asChild className="mt-4" size="sm">
                                <Link
                                    href={`/weddings/${activeWedding.uuid}/events`}
                                >
                                    Buka menu Acara
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <>
                            <nav
                                aria-label="Pilih acara"
                                className="mt-5 flex flex-wrap gap-2"
                            >
                                {events.map((event) => (
                                    <Button
                                        key={event.uuid}
                                        asChild
                                        size="sm"
                                        variant={
                                            selectedEvent?.uuid === event.uuid
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        <Link
                                            href={base}
                                            data={{
                                                event: event.uuid,
                                            }}
                                        >
                                            {event.name}
                                        </Link>
                                    </Button>
                                ))}
                            </nav>

                            {selectedEvent && canEdit && (
                                <section
                                    aria-label="Tambah item rundown"
                                    className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                                >
                                    <h2 className="font-semibold">
                                        Item baru untuk {selectedEvent.name}
                                    </h2>
                                    <Form
                                        {...RundownController.store.form(
                                            activeWedding.uuid,
                                        )}
                                        className="mt-4 grid gap-4 sm:grid-cols-2"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="event_uuid"
                                                    value={selectedEvent.uuid}
                                                />
                                                <div className="grid gap-2 sm:col-span-2">
                                                    <Label htmlFor="title">
                                                        Judul aktivitas
                                                    </Label>
                                                    <Input
                                                        id="title"
                                                        name="title"
                                                        placeholder="cth. Penyambutan tamu"
                                                        required
                                                        maxLength={200}
                                                    />
                                                    <InputError
                                                        message={errors.title}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="pic">
                                                        PIC
                                                    </Label>
                                                    <Input
                                                        id="pic"
                                                        name="pic"
                                                        placeholder="cth. Operator musik"
                                                    />
                                                    <InputError
                                                        message={errors.pic}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="status">
                                                        Status
                                                    </Label>
                                                    <select
                                                        id="status"
                                                        name="status"
                                                        defaultValue="scheduled"
                                                        className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                    >
                                                        {statuses.map(
                                                            (status) => (
                                                                <option
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    value={
                                                                        status.value
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        message={errors.status}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="start_time">
                                                        Mulai (HH:MM)
                                                    </Label>
                                                    <Input
                                                        id="start_time"
                                                        name="start_time"
                                                        type="time"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.start_time
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="end_time">
                                                        Selesai (HH:MM)
                                                    </Label>
                                                    <Input
                                                        id="end_time"
                                                        name="end_time"
                                                        type="time"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.end_time
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="highlights">
                                                        Momen penting
                                                    </Label>
                                                    <Input
                                                        id="highlights"
                                                        name="highlights"
                                                        placeholder="cth. Ijab kabul"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.highlights
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="notes">
                                                        Catatan
                                                    </Label>
                                                    <Input
                                                        id="notes"
                                                        name="notes"
                                                    />
                                                    <InputError
                                                        message={errors.notes}
                                                    />
                                                </div>
                                                <div className="flex items-end sm:col-span-2 sm:justify-self-end">
                                                    <Button
                                                        disabled={processing}
                                                        className="min-h-11"
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

                            {selectedEvent && (
                                <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                                    <div className="border-b p-5">
                                        <h2 className="font-semibold">
                                            {selectedEvent.name}
                                        </h2>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {formatDate(
                                                selectedEvent.eventDate,
                                            )}{' '}
                                            · {items.length} aktivitas
                                        </p>
                                    </div>
                                    <ol className="divide-y">
                                        {items.map((item, index) => (
                                            <li
                                                key={item.uuid}
                                                className="flex flex-wrap items-start gap-3 p-4 sm:flex-nowrap"
                                            >
                                                <span className="text-muted-foreground w-10 pt-1 text-center text-sm font-semibold tabular-nums">
                                                    {index + 1}.
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-medium">
                                                        {item.title}
                                                        {item.hasOverlap && (
                                                            <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                                                <TriangleAlert
                                                                    className="size-3"
                                                                    aria-hidden="true"
                                                                />
                                                                Waktu tumpang
                                                                tindih
                                                            </span>
                                                        )}
                                                    </p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        {item.startTime ?? '—'}–
                                                        {item.endTime ?? '—'} ·{' '}
                                                        {item.pic ??
                                                            'Tanpa PIC'}
                                                    </p>
                                                    {item.highlights && (
                                                        <p className="text-primary mt-1 text-sm">
                                                            ★ {item.highlights}
                                                        </p>
                                                    )}
                                                    {item.notes && (
                                                        <p className="text-muted-foreground mt-1 text-xs">
                                                            {item.notes}
                                                        </p>
                                                    )}
                                                    <p className="mt-2">
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                                statusStyles[
                                                                    item.status
                                                                ] ?? ''
                                                            }`}
                                                        >
                                                            {item.statusLabel}
                                                        </span>
                                                    </p>
                                                    {canEdit && (
                                                        <details className="mt-2">
                                                            <summary className="text-muted-foreground cursor-pointer text-xs underline">
                                                                ubah item
                                                            </summary>
                                                            <Form
                                                                {...RundownController.update.form(
                                                                    [
                                                                        activeWedding.uuid,
                                                                        item.uuid,
                                                                    ],
                                                                )}
                                                                className="mt-3 grid gap-3 sm:grid-cols-2"
                                                            >
                                                                {({
                                                                    processing,
                                                                    errors,
                                                                }) => (
                                                                    <>
                                                                        <input
                                                                            type="hidden"
                                                                            name="event_uuid"
                                                                            value={
                                                                                selectedEvent.uuid
                                                                            }
                                                                        />
                                                                        <div className="grid gap-2 sm:col-span-2">
                                                                            <Label>{`Judul ${item.title}`}</Label>
                                                                            <Input
                                                                                name="title"
                                                                                defaultValue={
                                                                                    item.title
                                                                                }
                                                                                required
                                                                                maxLength={
                                                                                    200
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.title
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                PIC
                                                                            </Label>
                                                                            <Input
                                                                                name="pic"
                                                                                defaultValue={
                                                                                    item.pic ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.pic
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Status
                                                                            </Label>
                                                                            <select
                                                                                name="status"
                                                                                defaultValue={
                                                                                    item.status
                                                                                }
                                                                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                                                                            >
                                                                                {statuses.map(
                                                                                    (
                                                                                        status,
                                                                                    ) => (
                                                                                        <option
                                                                                            key={
                                                                                                status.value
                                                                                            }
                                                                                            value={
                                                                                                status.value
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                status.label
                                                                                            }
                                                                                        </option>
                                                                                    ),
                                                                                )}
                                                                            </select>
                                                                            <InputError
                                                                                message={
                                                                                    errors.status
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Mulai
                                                                            </Label>
                                                                            <Input
                                                                                name="start_time"
                                                                                type="time"
                                                                                defaultValue={
                                                                                    item.startTime ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.start_time
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Selesai
                                                                            </Label>
                                                                            <Input
                                                                                name="end_time"
                                                                                type="time"
                                                                                defaultValue={
                                                                                    item.endTime ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.end_time
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Momen
                                                                                penting
                                                                            </Label>
                                                                            <Input
                                                                                name="highlights"
                                                                                defaultValue={
                                                                                    item.highlights ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Catatan
                                                                            </Label>
                                                                            <Input
                                                                                name="notes"
                                                                                defaultValue={
                                                                                    item.notes ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="flex items-end sm:col-span-2 sm:justify-self-end">
                                                                            <Button
                                                                                variant="outline"
                                                                                disabled={
                                                                                    processing
                                                                                }
                                                                                className="min-h-11"
                                                                            >
                                                                                {processing
                                                                                    ? 'Menyimpan…'
                                                                                    : 'Simpan'}
                                                                            </Button>
                                                                        </div>
                                                                    </>
                                                                )}
                                                            </Form>
                                                            <ConfirmSubmit
                                                                form={RundownController.destroy.form(
                                                                    [
                                                                        activeWedding.uuid,
                                                                        item.uuid,
                                                                    ],
                                                                )}
                                                                label="hapus item"
                                                                context={`item ${item.title}`}
                                                            />
                                                        </details>
                                                    )}
                                                    {canEdit &&
                                                        items.length > 1 && (
                                                            <div className="mt-2 flex gap-2">
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    disabled={
                                                                        index ===
                                                                        0
                                                                    }
                                                                    onClick={() =>
                                                                        move(
                                                                            item.uuid,
                                                                            -1,
                                                                        )
                                                                    }
                                                                >
                                                                    Naik
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    disabled={
                                                                        index ===
                                                                        items.length -
                                                                            1
                                                                    }
                                                                    onClick={() =>
                                                                        move(
                                                                            item.uuid,
                                                                            1,
                                                                        )
                                                                    }
                                                                >
                                                                    Turun
                                                                </Button>
                                                            </div>
                                                        )}
                                                </div>
                                            </li>
                                        ))}
                                    </ol>
                                    {items.length === 0 && (
                                        <div className="p-10 text-center">
                                            <h2 className="font-semibold">
                                                Belum ada item rundown
                                            </h2>
                                            <p className="text-muted-foreground mt-2 text-sm">
                                                Tambahkan aktivitas pertama
                                                untuk {selectedEvent.name}.
                                            </p>
                                        </div>
                                    )}
                                </section>
                            )}
                        </>
                    )}
                </div>
            </main>
        </>
    );
}
