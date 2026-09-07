import { Head, usePage } from '@inertiajs/react';
import EventForm, { DeleteEventForm, type EventValue } from './event-form';
import type { ActiveWedding } from '@/types';

export default function EditEvent({ event }: { event: EventValue }) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    return (
        <>
            <Head title={`Edit ${event.name}`} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-3xl">
                    <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                        {activeWedding.name}
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Edit acara
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                        Perubahan acara utama dapat menggeser tugas relatif
                        setelah dikonfirmasi.
                    </p>
                    <section className="bg-card mt-6 rounded-2xl border p-5 shadow-sm sm:p-8">
                        <EventForm event={event} />
                        <DeleteEventForm event={event} />
                    </section>
                </div>
            </main>
        </>
    );
}
