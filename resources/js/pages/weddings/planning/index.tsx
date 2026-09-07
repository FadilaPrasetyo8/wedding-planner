import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, CalendarRange, Plus, Settings2 } from 'lucide-react';
import TaskController from '@/actions/App/Http/Controllers/Weddings/TaskController';
import { Button } from '@/components/ui/button';
import PlanningFiltersForm, { queryString } from './planning-filters';
import type { ActiveWedding } from '@/types';

type TaskRow = {
    uuid: string;
    title: string;
    priority: string;
    startDate: string | null;
    dueDate: string | null;
    category: { name: string };
    status: { name: string; behavior: string };
    assignee: string | null;
    event: string | null;
    isOverdue: boolean;
};
type PageData = {
    data: TaskRow[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};
type Option = { uuid: string; name: string };
type AssigneeOption = { id: number; name: string };

export default function PlanningIndex({
    tasks,
    filters,
    canEdit,
    categories,
    statuses,
    assignees,
    events,
}: {
    tasks: PageData;
    filters: Record<string, string | number | boolean>;
    canEdit: boolean;
    categories: Option[];
    statuses: Option[];
    assignees: AssigneeOption[];
    events: Option[];
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const planningUrl = `/weddings/${activeWedding.uuid}/planning`;
    const timelineQuery = queryString(filters);

    return (
        <>
            <Head title="Rencana tugas" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                                Rencana tugas
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {tasks.total} tugas tersimpan dalam ruang
                                pernikahan ini.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                asChild
                                className="min-h-11"
                            >
                                <Link
                                    href={`/weddings/${activeWedding.uuid}/planning/configuration`}
                                >
                                    <Settings2 aria-hidden="true" />
                                    Konfigurasi
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                asChild
                                className="min-h-11"
                            >
                                <Link
                                    href={`${planningUrl}/timeline${timelineQuery ? `?${timelineQuery}` : ''}`}
                                >
                                    <CalendarRange aria-hidden="true" />
                                    Linimasa
                                </Link>
                            </Button>
                            {canEdit ? (
                                <Button asChild className="min-h-11">
                                    <Link
                                        href={TaskController.create.url(
                                            activeWedding.uuid,
                                        )}
                                    >
                                        <Plus aria-hidden="true" />
                                        Tambah tugas
                                    </Link>
                                </Button>
                            ) : (
                                <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                    Mode hanya lihat
                                </span>
                            )}
                        </div>
                    </header>
                    <PlanningFiltersForm
                        action={planningUrl}
                        filters={filters}
                        categories={categories}
                        statuses={statuses}
                        assignees={assignees}
                        events={events}
                    />
                    <section className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full min-w-[850px] text-sm">
                                <thead className="bg-muted/60 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Tugas</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3">Tenggat</th>
                                        <th className="px-4 py-3">
                                            Penanggung jawab
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {tasks.data.map((task) => (
                                        <tr key={task.uuid}>
                                            <td className="px-4 py-3 font-medium">
                                                <Link
                                                    className="hover:text-primary focus-visible:outline-2"
                                                    href={TaskController.show.url(
                                                        [
                                                            activeWedding.uuid,
                                                            task.uuid,
                                                        ],
                                                    )}
                                                >
                                                    {task.title}
                                                </Link>
                                                {task.isOverdue && (
                                                    <span className="text-destructive mt-1 flex items-center gap-1 text-xs">
                                                        <AlertTriangle className="size-3" />
                                                        Terlambat
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {task.status.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {task.category.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {task.dueDate ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {task.assignee ??
                                                    'Belum ditugaskan'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="divide-y md:hidden">
                            {tasks.data.map((task) => (
                                <article key={task.uuid} className="p-4">
                                    <Link
                                        className="font-semibold"
                                        href={TaskController.show.url([
                                            activeWedding.uuid,
                                            task.uuid,
                                        ])}
                                    >
                                        {task.title}
                                    </Link>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {task.status.name} ·{' '}
                                        {task.category.name}
                                    </p>
                                    <p className="mt-2 text-sm">
                                        Tenggat{' '}
                                        {task.dueDate ?? 'belum ditentukan'}
                                        {task.isOverdue ? ' · Terlambat' : ''}
                                    </p>
                                </article>
                            ))}
                        </div>
                        {tasks.data.length === 0 && (
                            <div className="p-10 text-center">
                                <h2 className="font-semibold">
                                    Belum ada tugas yang cocok
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Ubah filter atau tambahkan tugas pertama.
                                </p>
                            </div>
                        )}
                    </section>
                    <nav
                        aria-label="Paginasi tugas"
                        className="mt-5 flex flex-wrap gap-2"
                    >
                        {tasks.links.map((link, index) =>
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
