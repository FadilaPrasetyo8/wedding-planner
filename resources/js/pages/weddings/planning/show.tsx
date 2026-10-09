import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Pencil } from 'lucide-react';
import { AttachmentsPanel } from '@/components/attachments-panel';
import { CustomFieldsPanel } from '@/components/custom-fields-panel';
import { Button } from '@/components/ui/button';
import type { ActiveWedding } from '@/types';

type TaskDetail = {
    uuid: string;
    title: string;
    description: string | null;
    priority: string;
    category: string;
    status: string;
    statusBehavior: string;
    assignee: string | null;
    event: string | null;
    dateMode: string;
    startDate: string | null;
    dueDate: string | null;
    completedAt: string | null;
    notes: string | null;
};

const priorityLabels: Record<string, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    urgent: 'Mendesak',
};
const formatDate = (date: string) =>
    new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));

export default function TaskShow({
    task,
    customFields,
    attachments,
    canEdit,
}: {
    task: TaskDetail;
    customFields: {
        definitions: Parameters<typeof CustomFieldsPanel>[0]['definitions'];
        values: Record<string, unknown>;
    };
    attachments: Parameters<typeof AttachmentsPanel>[0]['attachments'];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const base = `/weddings/${activeWedding.uuid}/planning`;

    return (
        <>
            <Head title={task.title} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-4xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                {task.title}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Detail tugas perencanaan.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-11"
                            >
                                <Link href={base}>
                                    <ArrowLeft aria-hidden="true" />
                                    Kembali
                                </Link>
                            </Button>
                            {canEdit ? (
                                <Button asChild className="min-h-11">
                                    <Link href={`${base}/${task.uuid}/edit`}>
                                        <Pencil aria-hidden="true" />
                                        Edit tugas
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
                            <Detail label="Status" value={task.status} />
                            <Detail
                                label="Prioritas"
                                value={
                                    priorityLabels[task.priority] ??
                                    task.priority
                                }
                            />
                            <Detail label="Kategori" value={task.category} />
                            <Detail
                                label="Penanggung jawab"
                                value={task.assignee ?? 'Belum ditugaskan'}
                            />
                            <Detail
                                label="Acara terkait"
                                value={task.event ?? 'Tidak terkait acara'}
                            />
                            <Detail
                                label="Mode tanggal"
                                value={
                                    task.dateMode === 'template_relative'
                                        ? 'Relatif ke acara utama'
                                        : 'Tanggal manual'
                                }
                            />
                            <Detail
                                label="Tanggal mulai"
                                value={
                                    task.startDate
                                        ? formatDate(task.startDate)
                                        : 'Belum ditentukan'
                                }
                            />
                            <Detail
                                label="Tenggat"
                                value={
                                    task.dueDate
                                        ? formatDate(task.dueDate)
                                        : 'Belum ditentukan'
                                }
                            />
                        </dl>
                        {(task.description || task.notes) && (
                            <div className="grid border-t sm:grid-cols-2">
                                {task.description && (
                                    <TextDetail
                                        label="Deskripsi"
                                        value={task.description}
                                    />
                                )}
                                {task.notes && (
                                    <TextDetail
                                        label="Catatan"
                                        value={task.notes}
                                    />
                                )}
                            </div>
                        )}
                    </section>
                    <CustomFieldsPanel
                        weddingUuid={activeWedding.uuid}
                        module="task"
                        subjectUuid={task.uuid}
                        definitions={customFields.definitions}
                        values={customFields.values}
                        canEdit={canEdit}
                    />
                    <AttachmentsPanel
                        weddingUuid={activeWedding.uuid}
                        module="task"
                        subjectUuid={task.uuid}
                        attachments={attachments}
                        canEdit={canEdit}
                        context={task.title}
                    />
                    <p className="text-muted-foreground mt-4 flex items-center gap-2 text-xs">
                        <CalendarDays className="size-4" aria-hidden="true" />
                        Semua tanggal mengikuti konfigurasi ruang pernikahan.
                    </p>
                </div>
            </main>
        </>
    );
}
function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-b p-5 odd:sm:border-r">
            <dt className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                {label}
            </dt>
            <dd className="mt-2 font-medium">{value}</dd>
        </div>
    );
}
function TextDetail({ label, value }: { label: string; value: string }) {
    return (
        <div className="p-5">
            <h2 className="text-sm font-semibold">{label}</h2>
            <p className="text-muted-foreground mt-2 text-sm leading-6 whitespace-pre-wrap">
                {value}
            </p>
        </div>
    );
}
