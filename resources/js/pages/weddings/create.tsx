import { Form, Head } from '@inertiajs/react';
import { CalendarHeart, Check, HeartHandshake } from 'lucide-react';
import WeddingController from '@/actions/App/Http/Controllers/Weddings/WeddingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Template = {
    id: number;
    name: string;
    description: string | null;
    items: Array<{ type: 'event'; name: string }>;
};

type Props = {
    templates: Template[];
    timezones: Array<{ value: string; label: string }>;
};

export default function CreateWedding({ templates, timezones }: Props) {
    return (
        <>
            <Head title="Mulai ruang pernikahan" />
            <main className="flex flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
                <div className="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-12">
                    <section
                        className="lg:pt-10"
                        aria-labelledby="onboarding-title"
                    >
                        <div className="bg-accent text-accent-foreground flex size-12 items-center justify-center rounded-2xl">
                            <HeartHandshake
                                className="size-6"
                                aria-hidden="true"
                            />
                        </div>
                        <p className="text-primary mt-6 text-sm font-semibold tracking-[0.14em] uppercase">
                            Awal yang tertata
                        </p>
                        <h1
                            id="onboarding-title"
                            className="mt-3 max-w-lg text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            Ciptakan ruang tenang untuk perjalanan kalian.
                        </h1>
                        <p className="text-muted-foreground mt-5 max-w-xl text-base leading-7">
                            Detail dasar ini membentuk ruang pernikahan pribadi.
                            Anda dapat mengundang pendamping perencana setelah
                            ruang siap.
                        </p>
                        <ul className="mt-8 space-y-4 text-sm">
                            {[
                                'Anda menjadi pemilik ruang',
                                'Data setiap pernikahan tetap terpisah',
                                'Acara awal dapat disesuaikan kapan saja',
                            ].map((item) => (
                                <li
                                    key={item}
                                    className="flex items-center gap-3"
                                >
                                    <span className="bg-primary/10 text-primary flex size-7 items-center justify-center rounded-full">
                                        <Check
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section
                        className="bg-card rounded-3xl border p-5 shadow-sm sm:p-8"
                        aria-label="Formulir ruang pernikahan"
                    >
                        <Form
                            {...WeddingController.store.form()}
                            disableWhileProcessing
                            className="space-y-8"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <fieldset className="space-y-5">
                                        <legend className="text-lg font-semibold">
                                            Tentang pernikahan
                                        </legend>
                                        <div className="grid gap-5 sm:grid-cols-2">
                                            <Field
                                                id="partner_one_name"
                                                label="Nama pasangan pertama"
                                                error={errors.partner_one_name}
                                            >
                                                <Input
                                                    id="partner_one_name"
                                                    name="partner_one_name"
                                                    required
                                                    autoFocus
                                                    autoComplete="name"
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
                                                    required
                                                    autoComplete="name"
                                                />
                                            </Field>
                                        </div>
                                        <Field
                                            id="name"
                                            label="Nama ruang pernikahan"
                                            error={errors.name}
                                            hint="Contoh: Pernikahan Alya & Bima"
                                        >
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                                placeholder="Pernikahan kami"
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
                                                    required
                                                />
                                            </Field>
                                            <Field
                                                id="planning_start_date"
                                                label="Mulai merencanakan"
                                                error={
                                                    errors.planning_start_date
                                                }
                                                hint="Opsional"
                                            >
                                                <Input
                                                    id="planning_start_date"
                                                    name="planning_start_date"
                                                    type="date"
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
                                                    defaultValue="Asia/Jakarta"
                                                    className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                                >
                                                    {timezones.map(
                                                        (timezone) => (
                                                            <option
                                                                key={
                                                                    timezone.value
                                                                }
                                                                value={
                                                                    timezone.value
                                                                }
                                                            >
                                                                {timezone.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </Field>
                                            <Field
                                                id="target_budget"
                                                label="Target anggaran"
                                                error={errors.target_budget}
                                                hint="Opsional, dalam rupiah utuh"
                                            >
                                                <Input
                                                    id="target_budget"
                                                    name="target_budget"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    inputMode="numeric"
                                                    placeholder="50000000"
                                                />
                                            </Field>
                                        </div>
                                    </fieldset>

                                    <fieldset className="space-y-5 border-t pt-7">
                                        <legend className="text-lg font-semibold">
                                            Acara awal
                                        </legend>
                                        <Field
                                            id="initial_event_name"
                                            label="Nama acara utama"
                                            error={errors.initial_event_name}
                                        >
                                            <Input
                                                id="initial_event_name"
                                                name="initial_event_name"
                                                required
                                                defaultValue="Akad"
                                            />
                                        </Field>
                                        <div className="space-y-3">
                                            <Label>
                                                Pilih susunan awal{' '}
                                                <span className="text-muted-foreground font-normal">
                                                    (opsional)
                                                </span>
                                            </Label>
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <label className="border-input has-[:checked]:border-primary has-[:checked]:bg-primary/5 cursor-pointer rounded-2xl border p-4 transition-colors">
                                                    <input
                                                        className="sr-only"
                                                        type="radio"
                                                        name="template_id"
                                                        value=""
                                                        defaultChecked
                                                    />
                                                    <span className="font-medium">
                                                        Mulai sederhana
                                                    </span>
                                                    <span className="text-muted-foreground mt-1 block text-sm leading-6">
                                                        Hanya acara utama yang
                                                        Anda isi.
                                                    </span>
                                                </label>
                                                {templates.map((template) => (
                                                    <label
                                                        key={template.id}
                                                        className="border-input has-[:checked]:border-primary has-[:checked]:bg-primary/5 cursor-pointer rounded-2xl border p-4 transition-colors"
                                                    >
                                                        <input
                                                            className="sr-only"
                                                            type="radio"
                                                            name="template_id"
                                                            value={template.id}
                                                        />
                                                        <span className="font-medium">
                                                            {template.name}
                                                        </span>
                                                        <span className="text-muted-foreground mt-1 block text-sm leading-6">
                                                            {
                                                                template.description
                                                            }
                                                        </span>
                                                        <span className="text-muted-foreground mt-3 flex items-center gap-2 text-xs">
                                                            <CalendarHeart
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                            {template.items
                                                                .map(
                                                                    (item) =>
                                                                        item.name,
                                                                )
                                                                .join(' · ')}
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                            <InputError
                                                message={errors.template_id}
                                            />
                                        </div>
                                    </fieldset>

                                    <div className="flex flex-col-reverse gap-3 border-t pt-7 sm:flex-row sm:items-center sm:justify-between">
                                        <p className="text-muted-foreground text-sm">
                                            Anda dapat mengubah detail ini
                                            nanti.
                                        </p>
                                        <Button
                                            type="submit"
                                            size="lg"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Buat ruang pernikahan
                                        </Button>
                                    </div>
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
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
