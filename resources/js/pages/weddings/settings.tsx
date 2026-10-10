import { Form, Head, usePage } from '@inertiajs/react';
import WeddingSettingsController from '@/actions/App/Http/Controllers/Weddings/WeddingSettingsController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding } from '@/types';

type WeddingSettings = {
    name: string;
    partnerOneName: string;
    partnerTwoName: string;
    weddingDate: string;
    planningStartDate: string | null;
    timezone: string;
    targetBudget: number | null;
};

export default function Settings({ wedding }: { wedding: WeddingSettings }) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;

    return (
        <>
            <Head title="Pengaturan pernikahan" />
            <main className="flex flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-3xl">
                    <p className="text-primary text-sm font-semibold tracking-[0.14em] uppercase">
                        {activeWedding.name}
                    </p>
                    <h1 className="font-display mt-3 text-3xl font-semibold tracking-tight">
                        Pengaturan ruang
                    </h1>
                    <p className="text-muted-foreground mt-3 leading-7">
                        Jaga identitas dan tanggal penting ruang pernikahan
                        tetap akurat.
                    </p>
                    <section className="bg-card mt-8 rounded-3xl border p-5 shadow-sm sm:p-8">
                        <Form
                            {...WeddingSettingsController.update.form(
                                activeWedding.uuid,
                            )}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <Field
                                            id="partner_one_name"
                                            label="Nama pasangan pertama"
                                            error={errors.partner_one_name}
                                        >
                                            <Input
                                                id="partner_one_name"
                                                name="partner_one_name"
                                                defaultValue={
                                                    wedding.partnerOneName
                                                }
                                                required
                                            />
                                        </Field>
                                        <Field
                                            id="partner_two_name"
                                            label="Nama pasangan kedua"
                                            error={errors.partner_two_name}
                                        >
                                            <Input
                                                id="partner_two_name"
                                                name="partner_two_name"
                                                defaultValue={
                                                    wedding.partnerTwoName
                                                }
                                                required
                                            />
                                        </Field>
                                    </div>
                                    <Field
                                        id="name"
                                        label="Nama ruang"
                                        error={errors.name}
                                    >
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={wedding.name}
                                            required
                                        />
                                    </Field>
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <Field
                                            id="wedding_date"
                                            label="Tanggal pernikahan"
                                            error={errors.wedding_date}
                                        >
                                            <Input
                                                id="wedding_date"
                                                name="wedding_date"
                                                type="date"
                                                defaultValue={
                                                    wedding.weddingDate
                                                }
                                                required
                                            />
                                        </Field>
                                        <Field
                                            id="planning_start_date"
                                            label="Mulai merencanakan"
                                            error={errors.planning_start_date}
                                        >
                                            <Input
                                                id="planning_start_date"
                                                name="planning_start_date"
                                                type="date"
                                                defaultValue={
                                                    wedding.planningStartDate ??
                                                    ''
                                                }
                                            />
                                        </Field>
                                    </div>
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <Field
                                            id="timezone"
                                            label="Zona waktu"
                                            error={errors.timezone}
                                        >
                                            <select
                                                id="timezone"
                                                name="timezone"
                                                defaultValue={wedding.timezone}
                                                className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                            >
                                                <option value="Asia/Jakarta">
                                                    WIB — Jakarta
                                                </option>
                                                <option value="Asia/Makassar">
                                                    WITA — Makassar
                                                </option>
                                                <option value="Asia/Jayapura">
                                                    WIT — Jayapura
                                                </option>
                                            </select>
                                        </Field>
                                        <Field
                                            id="target_budget"
                                            label="Target anggaran"
                                            error={errors.target_budget}
                                        >
                                            <Input
                                                id="target_budget"
                                                name="target_budget"
                                                type="number"
                                                min="0"
                                                step="1"
                                                defaultValue={
                                                    wedding.targetBudget ?? ''
                                                }
                                            />
                                        </Field>
                                    </div>
                                    <Button disabled={processing}>
                                        Simpan perubahan
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                    <section className="border-destructive/30 bg-destructive/5 mt-8 rounded-3xl border p-5 sm:p-8">
                        <h2 className="text-lg font-semibold">
                            Hapus ruang pernikahan
                        </h2>
                        <p className="text-muted-foreground mt-2 text-sm leading-6">
                            Tindakan ini menonaktifkan ruang. Ketik{' '}
                            <strong className="text-foreground">
                                {wedding.name}
                            </strong>{' '}
                            untuk mengonfirmasi.
                        </p>
                        <Form
                            {...WeddingSettingsController.destroy.form(
                                activeWedding.uuid,
                            )}
                            className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex-1">
                                        <Label
                                            htmlFor="confirmation"
                                            className="sr-only"
                                        >
                                            Konfirmasi nama ruang
                                        </Label>
                                        <Input
                                            id="confirmation"
                                            name="confirmation"
                                            placeholder={wedding.name}
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.confirmation}
                                        />
                                    </div>
                                    <Button
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Hapus ruang
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                </div>
            </main>
        </>
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
