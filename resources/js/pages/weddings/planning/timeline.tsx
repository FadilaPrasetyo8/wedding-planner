import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, CalendarDays, Pencil } from 'lucide-react';
import TaskController from '@/actions/App/Http/Controllers/Weddings/TaskController';
import { Button } from '@/components/ui/button';
import PlanningFiltersForm, { queryString } from './planning-filters';
import type { ActiveWedding } from '@/types';

type TimelineTask = {
    uuid: string;
    title: string;
    startDate: string;
    dueDate: string;
    status: string;
    isOverdue: boolean;
};
type EventMarker = {
    uuid: string;
    name: string;
    event_date: string;
    is_primary: boolean;
};
type Option = { uuid: string; name: string };
type AssigneeOption = { id: number; name: string };

export default function Timeline({
    tasks,
    events,
    filters,
    canEdit,
    categories,
    statuses,
    assignees,
}: {
    tasks: TimelineTask[];
    events: EventMarker[];
    filters: Record<string, string | number | boolean>;
    canEdit: boolean;
    categories: Option[];
    statuses: Option[];
    assignees: AssigneeOption[];
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const planningUrl = `/weddings/${activeWedding.uuid}/planning`;
    const listQuery = queryString(filters);
    const allDates = [
        ...tasks.flatMap((task) => [task.startDate, task.dueDate]),
        ...events.map((event) => event.event_date),
    ].sort();
    const start = new Date(
        `${allDates[0] ?? new Date().toISOString().slice(0, 10)}T00:00:00Z`,
    );
    const end = new Date(
        `${allDates.at(-1) ?? allDates[0] ?? new Date().toISOString().slice(0, 10)}T00:00:00Z`,
    );
    const total = Math.max(
        1,
        Math.round((end.getTime() - start.getTime()) / 86400000) + 1,
    );
    const position = (date: string) =>
        Math.max(
            0,
            Math.round(
                (new Date(`${date}T00:00:00Z`).getTime() - start.getTime()) /
                    86400000,
            ),
        );
    return (
        <>
            <Head title="Linimasa perencanaan" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                                Linimasa perencanaan
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Rentang tanggal tugas dan penanda acara dalam
                                satu tampilan.
                            </p>
                        </div>
                        <Button variant="outline" asChild className="min-h-11">
                            <Link
                                href={`${planningUrl}${listQuery ? `?${listQuery}` : ''}`}
                            >
                                Kembali ke daftar
                            </Link>
                        </Button>
                    </header>
                    <PlanningFiltersForm
                        action={`${planningUrl}/timeline`}
                        filters={filters}
                        categories={categories}
                        statuses={statuses}
                        assignees={assignees}
                        events={events.map((event) => ({
                            uuid: event.uuid,
                            name: event.name,
                        }))}
                    />
                    <section className="bg-card mt-6 overflow-x-auto rounded-2xl border shadow-sm">
                        <div className="min-w-[900px] p-5">
                            <div className="grid grid-cols-[220px_1fr] border-b pb-3 text-xs font-semibold tracking-wider uppercase">
                                <span>Tugas</span>
                                <span>
                                    {allDates.length
                                        ? `${allDates[0]} — ${allDates.at(-1)}`
                                        : 'Belum ada rentang tanggal'}
                                </span>
                            </div>
                            {events.length > 0 && (
                                <div className="grid grid-cols-[220px_1fr] border-b py-4">
                                    <span className="text-sm font-semibold">
                                        Penanda acara
                                    </span>
                                    <div className="bg-muted relative h-10 rounded-lg">
                                        {events.map((event) => (
                                            <span
                                                key={event.uuid}
                                                title={`${event.name} · ${event.event_date}`}
                                                className="bg-primary absolute top-1 h-8 w-1 rounded"
                                                style={{
                                                    left: `${(position(event.event_date) / total) * 100}%`,
                                                }}
                                            >
                                                <span className="sr-only">
                                                    {event.name},{' '}
                                                    {event.event_date}
                                                </span>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                            <div className="divide-y">
                                {tasks.map((task) => {
                                    const offset = position(task.startDate);
                                    const duration = Math.max(
                                        1,
                                        position(task.dueDate) - offset + 1,
                                    );
                                    return (
                                        <article
                                            key={task.uuid}
                                            className="grid grid-cols-[220px_1fr] items-center py-4"
                                        >
                                            <div className="pr-5">
                                                <Link
                                                    href={TaskController.show.url(
                                                        [
                                                            activeWedding.uuid,
                                                            task.uuid,
                                                        ],
                                                    )}
                                                    className="hover:text-primary font-medium"
                                                >
                                                    {task.title}
                                                </Link>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {task.status}
                                                </p>
                                                {task.isOverdue && (
                                                    <p className="text-destructive mt-1 flex items-center gap-1 text-xs">
                                                        <AlertTriangle className="size-3" />
                                                        Terlambat
                                                    </p>
                                                )}
                                            </div>
                                            <div className="bg-muted relative h-10 rounded-lg">
                                                <span
                                                    className="bg-primary/80 absolute top-2 h-6 min-w-2 rounded-md"
                                                    style={{
                                                        left: `${(offset / total) * 100}%`,
                                                        width: `${(duration / total) * 100}%`,
                                                    }}
                                                >
                                                    <span className="sr-only">
                                                        {task.startDate} sampai{' '}
                                                        {task.dueDate}
                                                    </span>
                                                </span>
                                                {canEdit && (
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="absolute right-0 min-h-10 min-w-10"
                                                    >
                                                        <Link
                                                            href={TaskController.edit.url(
                                                                [
                                                                    activeWedding.uuid,
                                                                    task.uuid,
                                                                ],
                                                            )}
                                                            aria-label={`Edit tanggal ${task.title}`}
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                            {tasks.length === 0 && (
                                <div className="py-14 text-center">
                                    <CalendarDays className="text-muted-foreground mx-auto size-8" />
                                    <h2 className="mt-3 font-semibold">
                                        Belum ada tugas bertanggal
                                    </h2>
                                </div>
                            )}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
