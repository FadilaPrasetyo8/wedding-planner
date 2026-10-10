import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { index as rundownIndex } from '@/routes/weddings/rundown';
import type { ActiveWedding } from '@/types';

type EventOption = { uuid: string; name: string; isPrimary: boolean };
type SelectedEvent = { uuid: string; name: string; eventDate: string };
type RundownRow = {
    uuid: string;
    title: string;
    pic: string | null;
    notes: string | null;
    highlights: string | null;
    statusLabel: string;
    startTime: string | null;
    endTime: string | null;
};

export default function RundownPrint({
    events,
    selectedEvent,
    items,
}: {
    events: EventOption[];
    selectedEvent: SelectedEvent | null;
    items: RundownRow[];
}) {
    const { activeWedding } = usePage<{
        activeWedding: ActiveWedding;
    }>().props;
    const back = rundownIndex(activeWedding.uuid, {
        query: { event: selectedEvent?.uuid },
    });

    return (
        <>
            <Head title="Cetak rundown" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8 print:p-0">
                <div className="mx-auto w-full max-w-3xl">
                    <div className="flex items-center justify-between print:hidden">
                        <Button asChild size="sm" variant="outline">
                            <Link href={back}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>
                        <Button size="sm" onClick={() => window.print()}>
                            <Printer aria-hidden="true" />
                            Cetak
                        </Button>
                    </div>

                    {events.length === 0 || !selectedEvent ? (
                        <div className="bg-card mt-5 rounded-2xl border p-10 text-center shadow-sm print:hidden">
                            <h2 className="font-semibold">
                                Belum ada acara untuk dicetak
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Tambahkan acara dan item rundown terlebih
                                dahulu.
                            </p>
                        </div>
                    ) : (
                        <article className="mt-5">
                            <header className="border-b pb-4">
                                <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                    {activeWedding.name}
                                </p>
                                <h1 className="font-display mt-2 text-2xl font-semibold tracking-tight">
                                    Rundown {selectedEvent.name}
                                </h1>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {formatDate(selectedEvent.eventDate)}
                                </p>
                            </header>

                            {items.length === 0 ? (
                                <p className="text-muted-foreground mt-6 text-sm">
                                    Belum ada item rundown untuk acara ini.
                                </p>
                            ) : (
                                <table className="mt-6 w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b text-xs uppercase">
                                            <th className="py-2 pr-3 font-semibold">
                                                No
                                            </th>
                                            <th className="py-2 pr-3 font-semibold">
                                                Waktu
                                            </th>
                                            <th className="py-2 pr-3 font-semibold">
                                                Aktivitas
                                            </th>
                                            <th className="py-2 font-semibold">
                                                PIC
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item, index) => (
                                            <tr
                                                key={item.uuid}
                                                className="border-b align-top"
                                            >
                                                <td className="py-2 pr-3 tabular-nums">
                                                    {index + 1}
                                                </td>
                                                <td className="py-2 pr-3 whitespace-nowrap">
                                                    {item.startTime ?? '—'}
                                                    {item.endTime
                                                        ? `–${item.endTime}`
                                                        : ''}
                                                </td>
                                                <td className="py-2 pr-3">
                                                    <span className="font-medium">
                                                        {item.title}
                                                    </span>
                                                    {item.highlights && (
                                                        <span className="block text-xs">
                                                            ★ {item.highlights}
                                                        </span>
                                                    )}
                                                    {item.notes && (
                                                        <span className="text-muted-foreground block text-xs">
                                                            {item.notes}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-2">
                                                    {item.pic ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </article>
                    )}
                </div>
            </main>
        </>
    );
}
