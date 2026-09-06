import { Head, usePage } from '@inertiajs/react';
import { CalendarHeart, ShieldCheck } from 'lucide-react';

type PageProps = {
    activeWedding: {
        uuid: string;
        name: string;
        partnerNames: string;
        weddingDate: string;
    };
    activeMembership: { role: 'owner' | 'editor' | 'viewer' };
};

type Event = {
    uuid: string;
    name: string;
    event_date: string;
    is_primary: boolean;
};

const roleNames = {
    owner: 'Pemilik',
    editor: 'Editor',
    viewer: 'Hanya lihat',
} as const;

export default function WeddingOverview({ events }: { events: Event[] }) {
    const { activeWedding, activeMembership } = usePage<PageProps>().props;

    return (
        <>
            <Head title={activeWedding.name} />
            <main className="flex flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-sm font-semibold tracking-[0.14em] uppercase">
                                Ruang pernikahan
                            </p>
                            <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                                {activeWedding.partnerNames}
                            </h1>
                            <p className="text-muted-foreground mt-3">
                                {activeWedding.name}
                            </p>
                        </div>
                        <span className="bg-accent text-accent-foreground inline-flex w-fit items-center gap-2 rounded-full px-3 py-2 text-sm font-medium">
                            <ShieldCheck
                                className="size-4"
                                aria-hidden="true"
                            />
                            {roleNames[activeMembership.role]}
                        </span>
                    </div>

                    <section
                        className="bg-card mt-10 rounded-3xl border p-6 shadow-sm sm:p-8"
                        aria-labelledby="events-title"
                    >
                        <div className="flex items-center gap-3">
                            <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                                <CalendarHeart
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <div>
                                <h2 id="events-title" className="font-semibold">
                                    Acara awal
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Fondasi ruang pernikahan Anda.
                                </p>
                            </div>
                        </div>
                        <div className="mt-6 grid gap-3 sm:grid-cols-2">
                            {events.map((event) => (
                                <article
                                    key={event.uuid}
                                    className="bg-muted/35 rounded-2xl border p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="font-medium">
                                            {event.name}
                                        </h3>
                                        {event.is_primary && (
                                            <span className="text-primary text-xs font-semibold">
                                                Utama
                                            </span>
                                        )}
                                    </div>
                                    <time
                                        className="text-muted-foreground mt-2 block text-sm"
                                        dateTime={event.event_date}
                                    >
                                        {new Intl.DateTimeFormat('id-ID', {
                                            dateStyle: 'long',
                                        }).format(
                                            new Date(
                                                `${event.event_date}T00:00:00`,
                                            ),
                                        )}
                                    </time>
                                </article>
                            ))}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
