import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Clock3, MapPin, Pencil } from 'lucide-react';
import WeddingEventController from '@/actions/App/Http/Controllers/Weddings/WeddingEventController';
import { Button } from '@/components/ui/button';
import type { ActiveWedding } from '@/types';

type EventDetail = {
    uuid: string;
    name: string;
    type: string;
    eventDate: string;
    startsAt: string | null;
    endsAt: string | null;
    venue: string | null;
    notes: string | null;
    isPrimary: boolean;
};

const typeLabels: Record<string, string> = {
    engagement: 'Lamaran',
    akad: 'Akad',
    reception: 'Resepsi',
    custom: 'Acara lainnya',
};

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));

export default function EventShow({
    event,
    canEdit,
}: {
    event: EventDetail;
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    return (
        <>
            <Head title={event.name} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-4xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <div className="mt-2 flex flex-wrap items-center gap-3">
                                <h1 className="font-display text-3xl font-semibold tracking-tight">
                                    {event.name}
                                </h1>
                                {event.isPrimary && (
                                    <span className="border-primary/25 bg-primary/10 text-primary rounded-full border px-2.5 py-1 text-xs font-semibold">
                                        Acara utama
                                    </span>
                                )}
                            </div>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Detail rangkaian acara pernikahan.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-11"
                            >
                                <Link
                                    href={WeddingEventController.index.url(
                                        activeWedding.uuid,
                                    )}
                                >
                                    <ArrowLeft aria-hidden="true" />
                                    Kembali
                                </Link>
                            </Button>
                            {canEdit ? (
                                <Button asChild className="min-h-11">
                                    <Link
                                        href={WeddingEventController.edit.url([
                                            activeWedding.uuid,
                                            event.uuid,
                                        ])}
                                    >
                                        <Pencil aria-hidden="true" />
                                        Edit acara
                                    </Link>
                                </Button>
                            ) : (
                                <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                    Mode hanya lihat
                                </span>
                            )}
                        </div>
                    </header>

                    <section className="bg-card mt-6 overflow-hidden rounded-2xl border shadow-sm">
                        <dl className="grid sm:grid-cols-2">
                            <Detail
                                label="Jenis"
                                value={typeLabels[event.type] ?? event.type}
                            />
                            <Detail
                                label="Tanggal"
                                value={formatDate(event.eventDate)}
                                icon={<CalendarDays aria-hidden="true" />}
                            />
                            <Detail
                                label="Waktu"
                                value={formatTime(event.startsAt, event.endsAt)}
                                icon={<Clock3 aria-hidden="true" />}
                            />
                            <Detail
                                label="Lokasi"
                                value={event.venue ?? 'Belum ditentukan'}
                                icon={<MapPin aria-hidden="true" />}
                            />
                        </dl>
                        {event.notes && (
                            <div className="border-t p-5">
                                <h2 className="text-sm font-semibold">
                                    Catatan
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm leading-6 whitespace-pre-wrap">
                                    {event.notes}
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </>
    );
}

function Detail({
    label,
    value,
    icon,
}: {
    label: string;
    value: string;
    icon?: React.ReactNode;
}) {
    return (
        <div className="border-b p-5 odd:sm:border-r">
            <dt className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                {label}
            </dt>
            <dd className="mt-2 flex items-center gap-2 font-medium">
                {icon && (
                    <span className="text-primary [&>svg]:size-4">{icon}</span>
                )}
                {value}
            </dd>
        </div>
    );
}

function formatTime(startsAt: string | null, endsAt: string | null) {
    if (!startsAt) return 'Belum ditentukan';

    return `${startsAt.slice(0, 5)}${endsAt ? `–${endsAt.slice(0, 5)}` : ''}`;
}
