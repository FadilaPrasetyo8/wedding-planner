import { Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import TaskForm, {
    type Assignee,
    type Option,
    type TaskValue,
} from './task-form';
import { Button } from '@/components/ui/button';
import TaskController from '@/actions/App/Http/Controllers/Weddings/TaskController';
import type { ActiveWedding } from '@/types';

export default function EditTask({
    task,
    categories,
    statuses,
    assignees,
    events,
}: {
    task: TaskValue;
    categories: Option[];
    statuses: Option[];
    assignees: Assignee[];
    events: Option[];
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const remove = () => {
        if (
            window.confirm(
                `Hapus tugas “${task.title}”? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(
                TaskController.destroy.url([activeWedding.uuid, task.uuid]),
            );
        }
    };
    return (
        <>
            <Head title={`Edit ${task.title}`} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-3xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight">
                                Edit tugas
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Perbarui detail tanpa melewati validasi ruang
                                pernikahan.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="destructive"
                            className="min-h-11"
                            onClick={remove}
                        >
                            <Trash2 aria-hidden="true" />
                            Hapus tugas
                        </Button>
                    </div>
                    <section className="bg-card mt-6 rounded-2xl border p-5 shadow-sm sm:p-8">
                        <TaskForm
                            task={task}
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
