import { useState } from 'react';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Music4, Plus } from 'lucide-react';
import PlaylistController from '@/actions/App/Http/Controllers/EventDay/PlaylistController';
import { ConfirmSubmit } from '@/components/confirm-submit';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionTabs } from '@/components/section-tabs';
import { formatDate } from '@/lib/format';
import { index as rundownIndex } from '@/routes/weddings/rundown';
import { index as playlistIndex } from '@/routes/weddings/playlist';
import type { ActiveWedding } from '@/types';

type EventOption = {
    uuid: string;
    name: string;
    eventDate: string;
    isPrimary: boolean;
};
type SelectedEvent = { uuid: string; name: string; eventDate: string };
type PlaylistRow = {
    uuid: string;
    title: string;
    artist: string | null;
    link: string | null;
    requestedBy: string | null;
    moment: string | null;
    notes: string | null;
    sortOrder: number;
};

export default function PlaylistIndex({
    events,
    selectedEvent,
    items,
    canEdit,
}: {
    events: EventOption[];
    selectedEvent: SelectedEvent | null;
    items: PlaylistRow[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const base = playlistIndex(activeWedding.uuid).url;
    const [momentFilter, setMomentFilter] = useState('all');

    const moments = [
        ...new Set(
            items
                .map((item) => item.moment)
                .filter((moment): moment is string => moment !== null),
        ),
    ].sort((a, b) => a.localeCompare(b, 'id'));

    const visibleItems =
        momentFilter === 'all'
            ? items
            : items.filter((item) => item.moment === momentFilter);

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
            <Head title="Playlist acara" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-5xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Playlist acara
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Daftar lagu dan request untuk MC atau band.
                            </p>
                        </div>
                        {!canEdit && (
                            <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                Mode hanya lihat
                            </span>
                        )}
                    </header>

                    <SectionTabs
                        ariaLabel="Navigasi hari acara"
                        items={[
                            {
                                title: 'Rundown',
                                href: rundownIndex(activeWedding.uuid).url,
                            },
                            { title: 'Playlist', href: base },
                        ]}
                    />

                    {events.length === 0 ? (
                        <div className="bg-card mt-5 rounded-2xl border p-10 text-center shadow-sm">
                            <Music4
                                className="text-muted-foreground mx-auto size-8"
                                aria-hidden="true"
                            />
                            <h2 className="mt-3 font-semibold">
                                Belum ada acara
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Tambahkan acara terlebih dahulu lewat menu Acara
                                untuk mengisi playlist.
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
                                            data={{ event: event.uuid }}
                                        >
                                            {event.name}
                                        </Link>
                                    </Button>
                                ))}
                            </nav>

                            {selectedEvent && canEdit && (
                                <section
                                    aria-label="Tambah lagu"
                                    className="bg-card mt-5 rounded-2xl border p-5 shadow-sm"
                                >
                                    <h2 className="font-semibold">
                                        Tambah ke {selectedEvent.name}
                                    </h2>
                                    <Form
                                        {...PlaylistController.store.form(
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
                                                        Judul lagu
                                                    </Label>
                                                    <Input
                                                        id="title"
                                                        name="title"
                                                        placeholder="cth. Bridal Chorus"
                                                        required
                                                        maxLength={200}
                                                    />
                                                    <InputError
                                                        message={errors.title}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="artist">
                                                        Artis
                                                    </Label>
                                                    <Input
                                                        id="artist"
                                                        name="artist"
                                                    />
                                                    <InputError
                                                        message={errors.artist}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="requested_by">
                                                        Diminta oleh
                                                    </Label>
                                                    <Input
                                                        id="requested_by"
                                                        name="requested_by"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.requested_by
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="moment">
                                                        Momen
                                                    </Label>
                                                    <Input
                                                        id="moment"
                                                        name="moment"
                                                    />
                                                    <InputError
                                                        message={errors.moment}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="link">
                                                        Link
                                                    </Label>
                                                    <Input
                                                        id="link"
                                                        name="link"
                                                        type="url"
                                                    />
                                                    <InputError
                                                        message={errors.link}
                                                    />
                                                </div>
                                                <div className="grid gap-2 sm:col-span-2">
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
                                                            : 'Tambah lagu'}
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
                                            · {visibleItems.length} lagu
                                        </p>
                                        {moments.length > 0 && (
                                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                                <label
                                                    htmlFor="moment-filter"
                                                    className="text-muted-foreground text-xs"
                                                >
                                                    Filter momen
                                                </label>
                                                <select
                                                    id="moment-filter"
                                                    value={momentFilter}
                                                    onChange={(event) =>
                                                        setMomentFilter(
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="border-input bg-background min-h-9 rounded-md border px-2 text-sm"
                                                >
                                                    <option value="all">
                                                        Semua momen
                                                    </option>
                                                    {moments.map((moment) => (
                                                        <option
                                                            key={moment}
                                                            value={moment}
                                                        >
                                                            {moment}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        )}
                                    </div>
                                    <ol className="divide-y">
                                        {visibleItems.map((item, index) => (
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
                                                    </p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        {item.artist ??
                                                            'Tanpa artis'}
                                                        {item.moment
                                                            ? ` · ${item.moment}`
                                                            : ''}
                                                        {item.requestedBy
                                                            ? ` · request ${item.requestedBy}`
                                                            : ''}
                                                    </p>
                                                    {item.notes && (
                                                        <p className="text-muted-foreground mt-1 text-xs">
                                                            {item.notes}
                                                        </p>
                                                    )}
                                                    {canEdit && (
                                                        <details className="mt-2">
                                                            <summary className="text-muted-foreground cursor-pointer text-xs underline">
                                                                ubah lagu
                                                            </summary>
                                                            <Form
                                                                {...PlaylistController.update.form(
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
                                                                                Artis
                                                                            </Label>
                                                                            <Input
                                                                                name="artist"
                                                                                defaultValue={
                                                                                    item.artist ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors.artist
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Diminta
                                                                                oleh
                                                                            </Label>
                                                                            <Input
                                                                                name="requested_by"
                                                                                defaultValue={
                                                                                    item.requestedBy ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Momen
                                                                            </Label>
                                                                            <Input
                                                                                name="moment"
                                                                                defaultValue={
                                                                                    item.moment ??
                                                                                    ''
                                                                                }
                                                                            />
                                                                        </div>
                                                                        <div className="grid gap-2">
                                                                            <Label>
                                                                                Link
                                                                            </Label>
                                                                            <Input
                                                                                name="link"
                                                                                type="url"
                                                                                defaultValue={
                                                                                    item.link ??
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
                                                                form={PlaylistController.destroy.form(
                                                                    [
                                                                        activeWedding.uuid,
                                                                        item.uuid,
                                                                    ],
                                                                )}
                                                                label="hapus lagu"
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
                                    {visibleItems.length === 0 && (
                                        <div className="p-10 text-center">
                                            <h2 className="font-semibold">
                                                {items.length === 0
                                                    ? 'Belum ada lagu'
                                                    : 'Tidak ada lagu untuk momen ini'}
                                            </h2>
                                            <p className="text-muted-foreground mt-2 text-sm">
                                                {items.length === 0
                                                    ? `Tambahkan lagu pertama untuk ${selectedEvent.name}.`
                                                    : 'Pilih momen lain atau kembali ke semua momen.'}
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
