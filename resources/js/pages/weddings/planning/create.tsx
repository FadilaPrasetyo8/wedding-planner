import { Head, usePage } from '@inertiajs/react';
import TaskForm, { type Assignee, type Option } from './task-form';
import type { ActiveWedding } from '@/types';

export default function CreateTask({
    categories,
    statuses,
    assignees,
    events,
}: {
    categories: Option[];
    statuses: Option[];
    assignees: Assignee[];
    events: Option[];
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    return (
        <>
            <Head title="Tambah tugas" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-3xl">
                    <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                        {activeWedding.name}
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Tambah tugas
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Catat pekerjaan, tenggat, dan penanggung jawabnya.
                    </p>
                    <section className="bg-card mt-6 rounded-2xl border p-5 shadow-sm sm:p-8">
                        <TaskForm
                            categories={categories}
                            statuses={statuses}
                            assignees={assignees}
                            events={events}
                        />
                    </section>
                </div>
            </main>
        </>
    );
}
