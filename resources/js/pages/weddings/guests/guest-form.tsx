import { Form, usePage } from '@inertiajs/react';
import GuestController from '@/actions/App/Http/Controllers/Logistics/GuestController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding } from '@/types';

export type GuestValue = {
    uuid: string;
    name: string;
    phone: string | null;
    invitationCount: number;
    paxCount: number;
    invitationStatus: string;
    rsvpStatus: string | null;
    notes: string | null;
    sideUuid: string | null;
    groupUuid: string | null;
};

type ReferenceOption = { uuid: string; name: string };
type StatusOption = { value: string; label: string };

const selectClass =
    'border-input bg-background focus-visible:ring-ring min-h-11 w-full rounded-md border px-3 text-sm focus-visible:ring-2';

export default function GuestForm({
    guest,
    sides,
    groups,
    invitationStatuses,
    rsvpStatuses,
    onCancel,
    onSuccess,
}: {
    guest: GuestValue;
    sides: ReferenceOption[];
    groups: ReferenceOption[];
    invitationStatuses: StatusOption[];
    rsvpStatuses: StatusOption[];
    onCancel: () => void;
    onSuccess: () => void;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    return (
        <Form
            {...GuestController.update.form([activeWedding.uuid, guest.uuid])}
            onSuccess={onSuccess}
            className="grid gap-5 sm:grid-cols-2"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="guest-name">Nama tamu</Label>
                        <Input
                            id="guest-name"
                            name="name"
                            defaultValue={guest.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-side">Sisi</Label>
                        <select
                            id="guest-side"
                            name="guest_side_uuid"
                            defaultValue={guest.sideUuid ?? ''}
                            className={selectClass}
                        >
                            <option value="">Tanpa sisi</option>
                            {sides.map((side) => (
                                <option key={side.uuid} value={side.uuid}>
                                    {side.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.guest_side_uuid} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-group">Grup</Label>
                        <select
                            id="guest-group"
                            name="guest_group_uuid"
                            defaultValue={guest.groupUuid ?? ''}
                            className={selectClass}
                        >
                            <option value="">Tanpa grup</option>
                            {groups.map((group) => (
                                <option key={group.uuid} value={group.uuid}>
                                    {group.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.guest_group_uuid} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-phone">No. telepon</Label>
                        <Input
                            id="guest-phone"
                            name="phone"
                            defaultValue={guest.phone ?? ''}
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-invitation-count">
                            Jumlah undangan
                        </Label>
                        <Input
                            id="guest-invitation-count"
                            name="invitation_count"
                            type="number"
                            min={0}
                            max={100}
                            defaultValue={guest.invitationCount}
                            required
                        />
                        <InputError message={errors.invitation_count} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-pax-count">
                            Jumlah orang (pax)
                        </Label>
                        <Input
                            id="guest-pax-count"
                            name="pax_count"
                            type="number"
                            min={0}
                            max={100}
                            defaultValue={guest.paxCount}
                            required
                        />
                        <InputError message={errors.pax_count} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-invitation-status">
                            Status undangan
                        </Label>
                        <select
                            id="guest-invitation-status"
                            name="invitation_status"
                            defaultValue={guest.invitationStatus}
                            className={selectClass}
                        >
                            {invitationStatuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.invitation_status} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="guest-rsvp-status">Status RSVP</Label>
                        <select
                            id="guest-rsvp-status"
                            name="rsvp_status"
                            defaultValue={guest.rsvpStatus ?? ''}
                            className={selectClass}
                        >
                            <option value="">Belum ada RSVP</option>
                            {rsvpStatuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.rsvp_status} />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="guest-notes">Catatan</Label>
                        <textarea
                            id="guest-notes"
                            name="notes"
                            defaultValue={guest.notes ?? ''}
                            rows={3}
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2"
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="flex flex-col-reverse gap-3 sm:col-span-2 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            className="min-h-11"
                            disabled={processing}
                            onClick={onCancel}
                        >
                            Batal
                        </Button>
                        <Button disabled={processing} className="min-h-11">
                            {processing ? 'Menyimpan...' : 'Simpan tamu'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
