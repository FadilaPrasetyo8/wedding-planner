import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AlertTriangle, Download, Pencil, Plus, Trash2 } from 'lucide-react';
import TaskController from '@/actions/App/Http/Controllers/Weddings/TaskController';
import FormDialog from '@/components/form-dialog';
import { Button } from '@/components/ui/button';
import { exportUrl } from '@/lib/export-url';
import { SectionTabs } from '@/components/section-tabs';
import {
    index as planningIndex,
    timeline as planningTimeline,
    configuration as planningConfiguration,
} from '@/routes/weddings/planning';
import FilterDisclosure from '@/components/filter-disclosure';
import PlanningFiltersForm from './planning-filters';
import TaskForm, { type TaskValue } from './task-form';
import type { ActiveWedding } from '@/types';

type TaskRow = {
    uuid: string;
    title: string;
    description: string | null;
    priority: string;
    startDate: string | null;
    dueDate: string | null;
    category: { uuid: string; name: string };
    status: { uuid: string; name: string; behavior: string };
    assignee: string | null;
    assigneeMembershipId: number | null;
    event: string | null;
    eventUuid: string | null;
    dateMode: string;
    relativeStartOffset: number | null;
    relativeDueOffset: number | null;
    notes: string | null;
    isOverdue: boolean;
};

type ModalState = { mode: 'create' } | { mode: 'edit'; task: TaskValue } | null;

function toTaskValue(task: TaskRow): TaskValue {
    return {
        uuid: task.uuid,
        title: task.title,
        description: task.description,
        categoryUuid: task.category.uuid,
        statusUuid: task.status.uuid,
        priority: task.priority,
        assigneeMembershipId: task.assigneeMembershipId,
        eventUuid: task.eventUuid,
        dateMode: task.dateMode,
        startDate: task.startDate,
        dueDate: task.dueDate,
        relativeStartOffset: task.relativeStartOffset,
        relativeDueOffset: task.relativeDueOffset,
        notes: task.notes,
    };
}
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
    canExport,
    categories,
    statuses,
    assignees,
    events,
}: {
    tasks: PageData;
    filters: Record<string, string | number | boolean>;
    canEdit: boolean;
    canExport: boolean;
    categories: Option[];
    statuses: Option[];
    assignees: AssigneeOption[];
    events: Option[];
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const planningUrl = `/weddings/${activeWedding.uuid}/planning`;
    const [modal, setModal] = useState<ModalState>(() => {
        const editUuid = new URLSearchParams(window.location.search).get(
            'edit',
        );
        const target = tasks.data.find((task) => task.uuid === editUuid);

        return canEdit && target
            ? { mode: 'edit', task: toTaskValue(target) }
            : null;
    });

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
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Rencana tugas
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {tasks.total} tugas tersimpan dalam ruang
                                pernikahan ini.
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
                                            `/weddings/${activeWedding.uuid}/exports/planning`,
                                            filters,
                                        )}
                                    >
                                        <Download aria-hidden="true" />
                                        Ekspor CSV (Excel)
                                    </a>
                                </Button>
                            )}
                            {canEdit ? (
                                <Button
                                    className="min-h-11"
                                    onClick={() => setModal({ mode: 'create' })}
                                >
                                    <Plus aria-hidden="true" />
                                    Tambah tugas
                                </Button>
                            ) : (
                                <span className="bg-muted rounded-full px-4 py-3 text-sm">
                                    Mode hanya lihat
                                </span>
                            )}
                        </div>
                    </header>

                    <SectionTabs
                        ariaLabel="Navigasi rencana tugas"
                        items={[
                            {
                                title: 'Daftar',
                                href: planningIndex(activeWedding.uuid),
                            },
                            {
                                title: 'Linimasa',
                                href: planningTimeline(activeWedding.uuid),
                            },
                            {
                                title: 'Konfigurasi',
                                href: planningConfiguration(activeWedding.uuid),
                            },
                        ]}
                    />
                    <FilterDisclosure
                        activeCount={
                            Object.entries(filters).filter(
                                ([key, value]) =>
                                    key !== 'sort' &&
                                    key !== 'direction' &&
                                    value !== '' &&
                                    value !== false &&
                                    value !== null &&
                                    value !== undefined,
                            ).length
                        }
                    >
                        <PlanningFiltersForm
                            action={planningUrl}
                            filters={filters}
                            categories={categories}
                            statuses={statuses}
                            assignees={assignees}
                            events={events}
                        />
                    </FilterDisclosure>
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
                                        {canEdit && (
                                            <th className="px-4 py-3">
                                                <span className="sr-only">
                                                    Aksi
                                                </span>
                                            </th>
                                        )}
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
                                            {canEdit && (
                                                <td className="px-4 py-3 text-right">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="min-h-11"
                                                        onClick={() =>
                                                            setModal({
                                                                mode: 'edit',
                                                                task: toTaskValue(
                                                                    task,
                                                                ),
                                                            })
                                                        }
                                                    >
                                                        <Pencil aria-hidden="true" />
                                                        Edit
                                                    </Button>
                                                </td>
                                            )}
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
                                    {canEdit && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="mt-3 min-h-11"
                                            onClick={() =>
                                                setModal({
                                                    mode: 'edit',
                                                    task: toTaskValue(task),
                                                })
                                            }
                                        >
                                            <Pencil aria-hidden="true" />
                                            Edit
                                        </Button>
                                    )}
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

            <FormDialog
                open={modal !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setModal(null);
                    }
                }}
                title={modal?.mode === 'edit' ? 'Edit tugas' : 'Tambah tugas'}
                description={
                    modal?.mode === 'edit'
                        ? 'Perbarui detail tugas tanpa meninggalkan daftar.'
                        : 'Catat pekerjaan, tenggat, dan penanggung jawabnya.'
                }
            >
                {modal && (
                    <>
                        <TaskForm
                            key={
                                modal.mode === 'edit'
                                    ? modal.task.uuid
                                    : 'create'
                            }
                            task={
                                modal.mode === 'edit' ? modal.task : undefined
                            }
                            categories={categories}
                            statuses={statuses}
                            assignees={assignees}
                            events={events}
                            onCancel={() => setModal(null)}
                            onSuccess={() => setModal(null)}
                        />
                        {modal.mode === 'edit' && (
                            <DeleteTaskButton
                                task={modal.task}
                                onDeleted={() => setModal(null)}
                            />
                        )}
                    </>
                )}
            </FormDialog>
        </>
    );
}

function DeleteTaskButton({
    task,
    onDeleted,
}: {
    task: TaskValue;
    onDeleted: () => void;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);

    const remove = () => {
        setProcessing(true);
        router.delete(
            TaskController.destroy.url([activeWedding.uuid, task.uuid]),
            {
                onSuccess: onDeleted,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return confirming ? (
        <div
            role="group"
            aria-label="Konfirmasi hapus tugas"
            className="flex flex-wrap items-center gap-2 border-t pt-4"
        >
            <span className="text-sm">Hapus tugas ini?</span>
            <Button
                type="button"
                variant="destructive"
                className="min-h-11"
                disabled={processing}
                onClick={remove}
            >
                Yakin, hapus
            </Button>
            <Button
                type="button"
                variant="outline"
                className="min-h-11"
                disabled={processing}
                onClick={() => setConfirming(false)}
            >
                Batal
            </Button>
        </div>
    ) : (
        <div className="border-t pt-4">
            <Button
                type="button"
                variant="destructive"
                className="min-h-11"
                onClick={() => setConfirming(true)}
            >
                <Trash2 aria-hidden="true" />
                Hapus tugas
            </Button>
        </div>
    );
}
