import { Form, Link, usePage } from '@inertiajs/react';
import WeddingEventController from '@/actions/App/Http/Controllers/Weddings/WeddingEventController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding } from '@/types';

export type EventValue = {
    uuid: string;
    name: string;
    type: string;
    eventDate: string;
    startsAt: string | null;
    endsAt: string | null;
    venue: string | null;
    notes: string | null;
    isPrimary: boolean;
};

export default function EventForm({ event }: { event?: EventValue }) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const form = event
        ? WeddingEventController.update.form([activeWedding.uuid, event.uuid])
        : WeddingEventController.store.form(activeWedding.uuid);

    return (
        <Form {...form} className="space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field id="name" label="Nama acara" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={event?.name ?? ''}
                                required
                            />
                        </Field>
                        <Field
                            id="type"
                            label="Jenis acara"
                            error={errors.type}
                        >
                            <select
                                id="type"
                                name="type"
                                defaultValue={event?.type ?? 'custom'}
                                className="border-input bg-background min-h-11 rounded-md border px-3 text-sm"
                            >
                                <option value="engagement">Lamaran</option>
                                <option value="akad">Akad</option>
                                <option value="reception">Resepsi</option>
                                <option value="custom">Lainnya</option>
                            </select>
                        </Field>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-3">
                        <Field
                            id="event_date"
                            label="Tanggal"
                            error={errors.event_date}
                        >
                            <Input
                                id="event_date"
                                name="event_date"
                                type="date"
                                defaultValue={event?.eventDate ?? ''}
                                required
                            />
                        </Field>
                        <Field
                            id="starts_at"
                            label="Waktu mulai"
                            error={errors.starts_at}
                        >
                            <Input
                                id="starts_at"
                                name="starts_at"
                                type="time"
                                defaultValue={
                                    event?.startsAt?.slice(0, 5) ?? ''
                                }
                            />
                        </Field>
                        <Field
                            id="ends_at"
                            label="Waktu selesai"
                            error={errors.ends_at}
                        >
                            <Input
                                id="ends_at"
                                name="ends_at"
                                type="time"
                                defaultValue={event?.endsAt?.slice(0, 5) ?? ''}
                            />
                        </Field>
                    </div>
                    <Field id="venue" label="Lokasi" error={errors.venue}>
                        <Input
                            id="venue"
                            name="venue"
                            defaultValue={event?.venue ?? ''}
                        />
                    </Field>
                    <Field id="notes" label="Catatan" error={errors.notes}>
                        <textarea
                            id="notes"
                            name="notes"
                            defaultValue={event?.notes ?? ''}
                            rows={4}
                            className="border-input bg-background focus-visible:ring-ring min-h-24 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2"
                        />
                    </Field>
                    <div className="rounded-xl border p-4">
                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="is_primary"
                                name="is_primary"
                                value="1"
                                defaultChecked={event?.isPrimary ?? false}
                            />
                            <div>
                                <Label htmlFor="is_primary">
                                    Jadikan acara utama
                                </Label>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    Acara ini menjadi dasar hitung mundur dan
                                    tanggal tugas relatif.
                                </p>
                            </div>
                        </div>
                        <InputError
                            className="mt-2"
                            message={errors.is_primary}
                        />
                        {event && !event.isPrimary && (
                            <div className="mt-4 flex items-start gap-3 border-t pt-4">
                                <Checkbox
                                    id="confirm_recalculate_relative_tasks"
                                    name="confirm_recalculate_relative_tasks"
                                    value="1"
                                />
                                <Label
                                    htmlFor="confirm_recalculate_relative_tasks"
                                    className="leading-5"
                                >
                                    Saya memahami tanggal tugas relatif akan
                                    dihitung ulang.
                                </Label>
                            </div>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.confirm_recalculate_relative_tasks}
                        />
                    </div>
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        {event && !event.isPrimary ? (
                            <Button
                                type="submit"
                                form="delete-event"
                                variant="destructive"
                                className="min-h-11"
                            >
                                Hapus acara
                            </Button>
                        ) : (
                            <span />
                        )}
                        <div className="flex gap-3">
                            <Button
                                variant="outline"
                                asChild
                                className="min-h-11"
                            >
                                <Link
                                    href={WeddingEventController.index.url(
                                        activeWedding.uuid,
                                    )}
                                >
                                    Batal
                                </Link>
                            </Button>
                            <Button disabled={processing} className="min-h-11">
                                {processing ? 'Menyimpan…' : 'Simpan acara'}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}

export function DeleteEventForm({ event }: { event: EventValue }) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    return (
        <Form
            id="delete-event"
            {...WeddingEventController.destroy.form([
                activeWedding.uuid,
                event.uuid,
            ])}
        >
            {() => null}
        </Form>
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
