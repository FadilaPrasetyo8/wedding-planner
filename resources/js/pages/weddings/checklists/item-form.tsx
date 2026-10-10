import { Form, usePage } from '@inertiajs/react';
import ChecklistController from '@/actions/App/Http/Controllers/Logistics/ChecklistController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding } from '@/types';

export type ChecklistItemValue = {
    uuid: string;
    name: string;
    quantity: number;
    estimatedCost: number | null;
    actualCost: number | null;
    deadline: string | null;
    purchaseUrl: string | null;
    notes: string | null;
    groupUuid: string | null;
    categoryUuid: string | null;
    statusUuid: string | null;
    assigneeMemberId: number | null;
};

type CategoryOption = { uuid: string; name: string };
type StatusOption = { uuid: string; name: string };
type MemberOption = { id: number; name: string };

const selectClass =
    'border-input bg-background focus-visible:ring-ring min-h-11 w-full rounded-md border px-3 text-sm focus-visible:ring-2';

export default function ChecklistItemForm({
    item,
    type,
    isSeserahan,
    categories,
    statuses,
    members,
    onCancel,
    onSuccess,
}: {
    item?: ChecklistItemValue;
    type: string;
    isSeserahan: boolean;
    categories: CategoryOption[];
    statuses: StatusOption[];
    members: MemberOption[];
    onCancel: () => void;
    onSuccess: () => void;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const action = item
        ? ChecklistController.update.form([activeWedding.uuid, type, item.uuid])
        : ChecklistController.store.form([activeWedding.uuid, type]);

    return (
        <Form
            {...action}
            onSuccess={onSuccess}
            className="grid gap-5 sm:grid-cols-2"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="item-name">Nama item</Label>
                        <Input
                            id="item-name"
                            name="name"
                            defaultValue={item?.name ?? ''}
                            placeholder={
                                isSeserahan
                                    ? 'cth. Kalung emas'
                                    : 'cth. Surat nikah'
                            }
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-quantity">Jumlah</Label>
                        <Input
                            id="item-quantity"
                            name="quantity"
                            type="number"
                            min={1}
                            defaultValue={item?.quantity ?? 1}
                            required
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-deadline">Tenggat</Label>
                        <Input
                            id="item-deadline"
                            name="deadline"
                            type="date"
                            defaultValue={item?.deadline ?? ''}
                        />
                        <InputError message={errors.deadline} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-estimated-cost">
                            Estimasi satuan (rupiah)
                        </Label>
                        <Input
                            id="item-estimated-cost"
                            name="estimated_cost"
                            type="number"
                            min={0}
                            defaultValue={item?.estimatedCost ?? ''}
                        />
                        <InputError message={errors.estimated_cost} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-actual-cost">
                            Harga aktual satuan (rupiah)
                        </Label>
                        <Input
                            id="item-actual-cost"
                            name="actual_cost"
                            type="number"
                            min={0}
                            defaultValue={item?.actualCost ?? ''}
                        />
                        <InputError message={errors.actual_cost} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-category">Kategori</Label>
                        <select
                            id="item-category"
                            name="checklist_category_uuid"
                            defaultValue={item?.categoryUuid ?? ''}
                            className={selectClass}
                        >
                            <option value="">Tanpa kategori</option>
                            {categories.map((category) => (
                                <option
                                    key={category.uuid}
                                    value={category.uuid}
                                >
                                    {category.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.checklist_category_uuid} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-status">Status</Label>
                        <select
                            id="item-status"
                            name="checklist_status_uuid"
                            defaultValue={item?.statusUuid ?? ''}
                            className={selectClass}
                        >
                            <option value="">Tanpa status</option>
                            {statuses.map((status) => (
                                <option key={status.uuid} value={status.uuid}>
                                    {status.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.checklist_status_uuid} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="item-assignee">Penanggung jawab</Label>
                        <select
                            id="item-assignee"
                            name="assignee_member_id"
                            defaultValue={item?.assigneeMemberId ?? ''}
                            className={selectClass}
                        >
                            <option value="">Belum ditugaskan</option>
                            {members.map((member) => (
                                <option key={member.id} value={member.id}>
                                    {member.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.assignee_member_id} />
                    </div>
                    {isSeserahan && (
                        <div className="grid gap-2">
                            <Label htmlFor="item-purchase-url">
                                Tautan pembelian
                            </Label>
                            <Input
                                id="item-purchase-url"
                                name="purchase_url"
                                type="url"
                                placeholder="https://"
                                defaultValue={item?.purchaseUrl ?? ''}
                            />
                            <InputError message={errors.purchase_url} />
                        </div>
                    )}
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="item-notes">Catatan</Label>
                        <Input
                            id="item-notes"
                            name="notes"
                            defaultValue={item?.notes ?? ''}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    {item && (
                        <input
                            type="hidden"
                            name="checklist_group_uuid"
                            value={item.groupUuid ?? ''}
                        />
                    )}
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
                            {processing
                                ? 'Menyimpan...'
                                : item
                                  ? 'Simpan item'
                                  : 'Tambah item'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
