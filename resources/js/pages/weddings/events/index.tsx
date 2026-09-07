import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, Clock3, MapPin, Pencil, Plus } from 'lucide-react';
import WeddingEventController from '@/actions/App/Http/Controllers/Weddings/WeddingEventController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import type { ActiveWedding } from '@/types';

type EventRow = {
    uuid: string;
    name: string;
    type: string;
    event_date: string;
    starts_at: string | null;
    ends_at: string | null;
    venue: string | null;
    notes: string | null;
    is_primary: boolean;
};

export default function EventIndex({
    events,
    canEdit,
}: {
    events: EventRow[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    return (
        <>
            <Head title="Rangkaian acara" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-5xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                                Rangkaian acara
                            </h1>
                            <p className="text-muted-foreground mt-2 max-w-2xl text-sm leading-6">
                                Kelola tanggal penting yang menjadi penanda
                                utama rencana pernikahan.
                            </p>
                        </div>
                        {canEdit ? (
                            <Button asChild className="min-h-11">
                                <Link
                                    href={WeddingEventController.create.url(
                                        activeWedding.uuid,
                                    )}
                                >
                                    <Plus aria-hidden="true" /> Tambah acara
                                </Link>
                            </Button>
                        ) : (
                            <p className="bg-muted rounded-full px-4 py-2 text-sm font-medium">
                                Mode hanya lihat
                            </p>
                        )}
                    </header>

                    <InputError
                        className="mt-5"
                        message={
                            usePage<{ errors: { event?: string } }>().props
                                .errors?.event
                        }
                    />
                    <div className="bg-card mt-6 divide-y overflow-hidden rounded-2xl border shadow-sm">
                        {events.map((event) => (
                            <article
                                key={event.uuid}
                                className="grid gap-4 p-4 sm:grid-cols-[1fr_auto] sm:items-center sm:p-5"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-semibold">
                                            <Link
                                                href={WeddingEventController.show.url(
                                                    [
                                                        activeWedding.uuid,
                                                        event.uuid,
                                                    ],
                                                )}
                                                className="hover:text-primary focus-visible:ring-ring rounded-sm focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                {event.name}
                                            </Link>
                                        </h2>
                                        {event.is_primary && (
                                            <span className="border-primary/25 bg-primary/10 text-primary rounded-full border px-2.5 py-1 text-xs font-semibold">
                                                Acara utama
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-muted-foreground mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                                        <span className="inline-flex items-center gap-2">
                                            <CalendarDays
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            {formatDate(event.event_date)}
                                        </span>
                                        {event.starts_at && (
                                            <span className="inline-flex items-center gap-2">
                                                <Clock3
                                                    className="size-4"
                                                    aria-hidden="true"
                                                />
                                                {event.starts_at.slice(0, 5)}
                                                {event.ends_at
                                                    ? `–${event.ends_at.slice(0, 5)}`
                                                    : ''}
                                            </span>
                                        )}
                                        {event.venue && (
                                            <span className="inline-flex items-center gap-2">
                                                <MapPin
                                                    className="size-4"
                                                    aria-hidden="true"
                                                />
                                                {event.venue}
                                            </span>
                                        )}
                                    </div>
                                    {event.notes && (
                                        <p className="text-muted-foreground mt-3 text-sm leading-6">
                                            {event.notes}
                                        </p>
                                    )}
                                </div>
                                {canEdit && (
                                    <Button
                                        variant="outline"
                                        asChild
                                        className="min-h-11"
                                    >
                                        <Link
                                            href={WeddingEventController.edit.url(
                                                [
                                                    activeWedding.uuid,
                                                    event.uuid,
                                                ],
                                            )}
                                        >
                                            <Pencil aria-hidden="true" /> Edit
                                        </Link>
                                    </Button>
                                )}
                            </article>
                        ))}
                    </div>
                </div>
            </main>
        </>
    );
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${value}T00:00:00Z`));
}
