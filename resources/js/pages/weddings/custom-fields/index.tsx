import { Form, Head, router, usePage } from '@inertiajs/react';
import { ListPlus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionTabs } from '@/components/section-tabs';
import {
    index as customFieldsIndex,
    store as storeDefinition,
    update as updateDefinition,
    destroy as destroyDefinition,
} from '@/routes/weddings/custom-fields';
import type { ActiveWedding } from '@/types';

type ModuleOption = { value: string; label: string };

type Definition = {
    uuid: string;
    label: string;
    fieldKey: string;
    fieldType: string;
    fieldTypeLabel: string;
    isRequired: boolean;
    options: string[];
    sortOrder: number;
    isActive: boolean;
    hasValues: boolean;
};

const typeLabels: Record<string, string> = {
    text: 'Teks pendek',
    textarea: 'Teks panjang',
    number: 'Angka',
    currency: 'Mata uang (rupiah)',
    date: 'Tanggal',
    select: 'Pilihan (satu)',
    multi_select: 'Pilihan (banyak)',
    checkbox: 'Kotak centang',
    url: 'Tautan',
    file_reference: 'Referensi lampiran',
};

export default function CustomFieldsIndex({
    module,
    moduleLabel,
    definitions,
    modules,
    canEdit,
}: {
    module: string;
    moduleLabel: string;
    definitions: Definition[];
    modules: ModuleOption[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const weddingUuid = activeWedding.uuid;

    return (
        <>
            <Head title={`Field kustom — ${moduleLabel}`} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-6xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 flex items-center gap-3 text-3xl font-semibold tracking-tight">
                                <ListPlus
                                    className="size-7"
                                    aria-hidden="true"
                                />
                                Field kustom
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Tambahkan atribut opsional untuk data{' '}
                                {moduleLabel.toLowerCase()} tanpa mengganti
                                field utama.
                            </p>
                        </div>
                    </header>

                    <SectionTabs
                        ariaLabel="Navigasi modul field kustom"
                        items={modules.map((m) => ({
                            title: m.label,
                            href: customFieldsIndex(weddingUuid, {
                                query: { module: m.value },
                            }),
                        }))}
                    />

                    {!canEdit && (
                        <p className="bg-muted mt-5 rounded-xl border px-4 py-3 text-sm">
                            Mode hanya lihat. Pemilik atau editor dapat mengubah
                            field kustom.
                        </p>
                    )}

                    {canEdit && (
                        <CreateDefinitionForm
                            weddingUuid={weddingUuid}
                            module={module}
                            definitionsCount={definitions.length}
                        />
                    )}

                    <section className="bg-card mt-6 overflow-hidden rounded-2xl border shadow-sm">
                        <div className="border-b p-5">
                            <h2 className="text-lg font-semibold">
                                Field {moduleLabel}
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Field dengan nilai tersimpan tidak dapat dihapus
                                atau diganti tipenya — nonaktifkan sebagai
                                gantinya.
                            </p>
                        </div>
                        <div className="divide-y">
                            {definitions.map((definition) => (
                                <article key={definition.uuid} className="p-4">
                                    <DefinitionRow
                                        weddingUuid={weddingUuid}
                                        definition={definition}
                                        canEdit={canEdit}
                                    />
                                </article>
                            ))}
                        </div>
                        {definitions.length === 0 && (
                            <p className="text-muted-foreground p-8 text-center text-sm">
                                Belum ada field kustom untuk{' '}
                                {moduleLabel.toLowerCase()}.
                            </p>
                        )}
                    </section>
                </div>
            </main>
        </>
    );
}

function CreateDefinitionForm({
    weddingUuid,
    module,
    definitionsCount,
}: {
    weddingUuid: string;
    module: string;
    definitionsCount: number;
}) {
    return (
        <Form
            action={storeDefinition.url({ wedding: weddingUuid })}
            method="post"
            resetOnSuccess
            errorBag="custom-field-create"
            transform={(data) => ({
                ...data,
                options: String(data.options_text ?? '')
                    .split(',')
                    .map((option) => option.trim())
                    .filter((option) => option !== ''),
            })}
            className="bg-card mt-5 overflow-hidden rounded-2xl border shadow-sm"
        >
            {({ processing, errors }) => (
                <div className="grid gap-3 p-4 sm:grid-cols-2">
                    <input type="hidden" name="module" value={module} />
                    <label className="sm:col-span-2">
                        <span className="mb-1.5 block text-sm font-medium">
                            Nama field
                        </span>
                        <Input
                            name="label"
                            required
                            maxLength={255}
                            className="min-h-11"
                            aria-invalid={Boolean(errors.label)}
                        />
                        <InputError className="mt-1.5" message={errors.label} />
                    </label>
                    <label>
                        <span className="mb-1.5 block text-sm font-medium">
                            Tipe
                        </span>
                        <select
                            name="field_type"
                            defaultValue="text"
                            className="border-input bg-background min-h-11 w-full rounded-md border px-3 text-sm"
                            aria-invalid={Boolean(errors.field_type)}
                        >
                            {Object.entries(typeLabels).map(
                                ([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ),
                            )}
                        </select>
                        <InputError
                            className="mt-1.5"
                            message={errors.field_type}
                        />
                    </label>
                    <label>
                        <span className="mb-1.5 block text-sm font-medium">
                            Urutan
                        </span>
                        <Input
                            name="sort_order"
                            type="number"
                            min="0"
                            defaultValue={definitionsCount}
                            className="min-h-11"
                            aria-invalid={Boolean(errors.sort_order)}
                        />
                        <InputError
                            className="mt-1.5"
                            message={errors.sort_order}
                        />
                    </label>
                    <label className="sm:col-span-2">
                        <span className="mb-1.5 block text-sm font-medium">
                            Pilihan (hanya untuk tipe pilihan, pisahkan dengan
                            koma)
                        </span>
                        <Input
                            name="options_text"
                            className="min-h-11"
                            placeholder="Contoh: Utama, Keluarga, Kolega"
                        />
                        <span className="text-muted-foreground mt-1 block text-xs">
                            Diabaikan untuk tipe selain pilihan.
                        </span>
                        <InputError
                            className="mt-1.5"
                            message={errors.options}
                        />
                    </label>
                    <Label className="flex min-h-11 items-center gap-2 rounded-md border px-3 text-sm">
                        <Checkbox name="is_required" value="1" />
                        Wajib diisi
                    </Label>
                    <Button className="min-h-11" disabled={processing}>
                        {processing ? 'Menambahkan…' : 'Tambah field'}
                    </Button>
                </div>
            )}
        </Form>
    );
}

function DefinitionRow({
    weddingUuid,
    definition,
    canEdit,
}: {
    weddingUuid: string;
    definition: Definition;
    canEdit: boolean;
}) {
    const remove = () => {
        if (
            definition.hasValues ||
            !window.confirm(
                `Hapus field “${definition.label}”? Tindakan ini tidak dapat dibatalkan.`,
            )
        )
            return;
        router.delete(
            destroyDefinition.url({
                wedding: weddingUuid,
                custom_field_definition: definition.uuid,
            }),
            { preserveScroll: true },
        );
    };

    if (!canEdit) {
        return (
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="font-medium">{definition.label}</p>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        {definition.fieldTypeLabel} · kunci:{' '}
                        <code className="bg-muted rounded px-1">
                            {definition.fieldKey}
                        </code>
                    </p>
                </div>
                <span className="text-muted-foreground text-xs">
                    {definition.isRequired ? 'Wajib' : 'Opsional'} ·{' '}
                    {definition.isActive ? 'Aktif' : 'Nonaktif'}
                </span>
            </div>
        );
    }

    return (
        <Form
            action={updateDefinition.url({
                wedding: weddingUuid,
                custom_field_definition: definition.uuid,
            })}
            method="patch"
            errorBag={`custom-field-${definition.uuid}`}
        >
            {({ processing, errors }) => (
                <div className="grid gap-3 sm:grid-cols-[1fr_5rem_auto_auto]">
                    <div>
                        <Input
                            name="label"
                            defaultValue={definition.label}
                            aria-label={`Nama ${definition.label}`}
                            aria-invalid={Boolean(errors.label)}
                            className="min-h-11"
                        />
                        <p className="text-muted-foreground mt-1.5 text-xs">
                            {definition.fieldTypeLabel} · kunci:{' '}
                            <code className="bg-muted rounded px-1">
                                {definition.fieldKey}
                            </code>
                        </p>
                        <InputError className="mt-1.5" message={errors.label} />
                    </div>
                    <div>
                        <Input
                            name="sort_order"
                            type="number"
                            min="0"
                            defaultValue={definition.sortOrder}
                            aria-label={`Urutan ${definition.label}`}
                            aria-invalid={Boolean(errors.sort_order)}
                            className="min-h-11"
                        />
                        <InputError
                            className="mt-1.5"
                            message={errors.sort_order}
                        />
                    </div>
                    <div>
                        <input type="hidden" name="is_required" value="0" />
                        <Label className="flex min-h-11 items-center gap-2 rounded-md border px-2 text-sm">
                            <Checkbox
                                name="is_required"
                                value="1"
                                defaultChecked={definition.isRequired}
                            />
                            Wajib
                        </Label>
                        <InputError
                            className="mt-1.5"
                            message={errors.is_required}
                        />
                    </div>
                    <div className="flex flex-wrap items-start gap-2">
                        <input type="hidden" name="is_active" value="0" />
                        <Label className="flex min-h-11 items-center gap-2 rounded-md border px-2 text-sm">
                            <Checkbox
                                name="is_active"
                                value="1"
                                defaultChecked={definition.isActive}
                            />
                            Aktif
                        </Label>
                        <Button
                            variant="outline"
                            className="min-h-11"
                            disabled={processing}
                        >
                            {processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-destructive min-h-11 min-w-11"
                            disabled={definition.hasValues || processing}
                            onClick={remove}
                            aria-label={`Hapus ${definition.label}`}
                        >
                            <Trash2 aria-hidden="true" />
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
