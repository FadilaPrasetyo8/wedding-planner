import { Form, Link, usePage } from '@inertiajs/react';
import TaskController from '@/actions/App/Http/Controllers/Weddings/TaskController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding } from '@/types';

type Option = { uuid: string; name: string };
type Assignee = { id: number; name: string };
type TaskValue = {
    uuid: string;
    title: string;
    description: string | null;
    categoryUuid: string;
    statusUuid: string;
    priority: string;
    assigneeMembershipId: number | null;
    eventUuid: string | null;
    dateMode: string;
    startDate: string | null;
    dueDate: string | null;
    relativeStartOffset: number | null;
    relativeDueOffset: number | null;
    notes: string | null;
};

export default function TaskForm({
    task,
    categories,
    statuses,
    assignees,
    events,
    onCancel,
    onSuccess,
}: {
    task?: TaskValue;
    categories: Option[];
    statuses: Option[];
    assignees: Assignee[];
    events: Option[];
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const form = task
        ? TaskController.update.form([activeWedding.uuid, task.uuid])
        : TaskController.store.form(activeWedding.uuid);
    return (
        <Form {...form} onSuccess={onSuccess} className="space-y-6">
            {({ processing, errors }) => (
                <>
                    <Field id="title" label="Judul tugas" error={errors.title}>
                        <Input
                            id="title"
                            name="title"
                            defaultValue={task?.title ?? ''}
                            required
                        />
                    </Field>
                    <Field
                        id="description"
                        label="Deskripsi"
                        error={errors.description}
                    >
                        <textarea
                            id="description"
                            name="description"
                            defaultValue={task?.description ?? ''}
                            rows={3}
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2"
                        />
                    </Field>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <SelectField
                            id="task_category_id"
                            label="Kategori"
                            error={errors.task_category_id}
                            defaultValue={task?.categoryUuid}
                            options={categories}
                        />
                        <SelectField
                            id="task_status_id"
                            label="Status"
                            error={errors.task_status_id}
                            defaultValue={task?.statusUuid}
                            options={statuses}
                        />
                        <Field
                            id="priority"
                            label="Prioritas"
                            error={errors.priority}
                        >
                            <select
                                id="priority"
                                name="priority"
                                defaultValue={task?.priority ?? 'medium'}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="low">Rendah</option>
                                <option value="medium">Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Mendesak</option>
                            </select>
                        </Field>
                        <Field
                            id="assignee_membership_id"
                            label="Penanggung jawab"
                            error={errors.assignee_membership_id}
                        >
                            <select
                                id="assignee_membership_id"
                                name="assignee_membership_id"
                                defaultValue={task?.assigneeMembershipId ?? ''}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="">Belum ditugaskan</option>
                                {assignees.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field
                            id="wedding_event_id"
                            label="Acara terkait"
                            error={errors.wedding_event_id}
                        >
                            <select
                                id="wedding_event_id"
                                name="wedding_event_id"
                                defaultValue={task?.eventUuid ?? ''}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="">Tidak terkait acara</option>
                                {events.map((item) => (
                                    <option key={item.uuid} value={item.uuid}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field
                            id="date_mode"
                            label="Mode tanggal"
                            error={errors.date_mode}
                        >
                            <select
                                id="date_mode"
                                name="date_mode"
                                defaultValue={task?.dateMode ?? 'manual'}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="manual">Tanggal manual</option>
                                <option value="template_relative">
                                    Relatif ke acara utama
                                </option>
                            </select>
                        </Field>
                    </div>
                    <div className="grid gap-5 rounded-xl border p-4 sm:grid-cols-2">
                        <Field
                            id="start_date"
                            label="Tanggal mulai manual"
                            error={errors.start_date}
                        >
                            <Input
                                id="start_date"
                                name="start_date"
                                type="date"
                                defaultValue={task?.startDate ?? ''}
                            />
                        </Field>
                        <Field
                            id="due_date"
                            label="Tenggat manual"
                            error={errors.due_date}
                        >
                            <Input
                                id="due_date"
                                name="due_date"
                                type="date"
                                defaultValue={task?.dueDate ?? ''}
                            />
                        </Field>
                        <Field
                            id="relative_start_offset"
                            label="Offset mulai (hari)"
                            error={errors.relative_start_offset}
                        >
                            <Input
                                id="relative_start_offset"
                                name="relative_start_offset"
                                type="number"
                                defaultValue={task?.relativeStartOffset ?? ''}
                            />
                        </Field>
                        <Field
                            id="relative_due_offset"
                            label="Offset tenggat (hari)"
                            error={errors.relative_due_offset}
                        >
                            <Input
                                id="relative_due_offset"
                                name="relative_due_offset"
                                type="number"
                                defaultValue={task?.relativeDueOffset ?? ''}
                            />
                        </Field>
                    </div>
                    <Field id="notes" label="Catatan" error={errors.notes}>
                        <textarea
                            id="notes"
                            name="notes"
                            defaultValue={task?.notes ?? ''}
                            rows={3}
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2"
                        />
                    </Field>
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        {onCancel ? (
                            <Button
                                type="button"
                                variant="outline"
                                className="min-h-11"
                                disabled={processing}
                                onClick={onCancel}
                            >
                                Batal
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                asChild
                                className="min-h-11"
                            >
                                <Link
                                    href={`/weddings/${activeWedding.uuid}/planning`}
                                >
                                    Batal
                                </Link>
                            </Button>
                        )}
                        <Button disabled={processing} className="min-h-11">
                            {processing ? 'Menyimpan…' : 'Simpan tugas'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function SelectField({
    id,
    label,
    error,
    defaultValue,
    options,
}: {
    id: string;
    label: string;
    error?: string;
    defaultValue?: string;
    options: Option[];
}) {
    return (
        <Field id={id} label={label} error={error}>
            <select
                id={id}
                name={id}
                defaultValue={defaultValue ?? ''}
                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                required
            >
                <option value="">Pilih {label.toLowerCase()}</option>
                {options.map((item) => (
                    <option key={item.uuid} value={item.uuid}>
                        {item.name}
                    </option>
                ))}
            </select>
        </Field>
    );
}
function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
export type { Assignee, Option, TaskValue };
