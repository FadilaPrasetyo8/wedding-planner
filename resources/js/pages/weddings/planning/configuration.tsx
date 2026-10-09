import { Form, Head, router, usePage } from '@inertiajs/react';
import { Plus, Settings2, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionTabs } from '@/components/section-tabs';
import {
    index as planningIndex,
    timeline as planningTimeline,
    configuration as planningConfiguration,
} from '@/routes/weddings/planning';
import type { ActiveWedding } from '@/types';

type ConfigurationItem = {
    uuid: string;
    name: string;
    color: string;
    sort_order: number;
    is_active: boolean;
    tasks_count: number;
};

type StatusItem = ConfigurationItem & { behavior: string };

const colors = ['burgundy', 'clay', 'rose', 'sage', 'slate'];
const behaviorLabels: Record<string, string> = {
    todo: 'Belum mulai',
    in_progress: 'Sedang berjalan',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

export default function Configuration({
    categories,
    statuses,
    canEdit,
}: {
    categories: ConfigurationItem[];
    statuses: StatusItem[];
    canEdit: boolean;
}) {
    const { activeWedding } = usePage<{ activeWedding: ActiveWedding }>().props;
    const base = `/weddings/${activeWedding.uuid}/planning`;

    const remove = (
        kind: 'categories' | 'statuses',
        item: ConfigurationItem,
    ) => {
        if (
            item.tasks_count > 0 ||
            !window.confirm(
                `Hapus “${item.name}”? Tindakan ini tidak dapat dibatalkan.`,
            )
        )
            return;
        router.delete(`${base}/${kind}/${item.uuid}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Konfigurasi tugas" />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-6xl">
                    <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                {activeWedding.name}
                            </p>
                            <h1 className="font-display mt-2 flex items-center gap-3 text-3xl font-semibold tracking-tight">
                                <Settings2
                                    className="size-7"
                                    aria-hidden="true"
                                />
                                Konfigurasi tugas
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Atur kategori dan label status tanpa mengubah
                                perilaku siklus tugas.
                            </p>
                        </div>
                    </header>
                    <SectionTabs
                        ariaLabel="Navigasi rencana tugas"
                        items={[
                            {
                                title: 'Daftar',
                                href: planningIndex(activeWedding.uuid),
                            },
                            {
                                title: 'Linimasa',
                                href: planningTimeline(activeWedding.uuid),
                            },
                            {
                                title: 'Konfigurasi',
                                href: planningConfiguration(activeWedding.uuid),
                            },
                        ]}
                    />
                    {!canEdit && (
                        <p className="bg-muted mt-5 rounded-xl border px-4 py-3 text-sm">
                            Mode hanya lihat. Pemilik atau editor dapat mengubah
                            konfigurasi.
                        </p>
                    )}
                    <div className="mt-6 grid gap-6 lg:grid-cols-2">
                        <ConfigurationSection
                            title="Kategori"
                            description="Kelompokkan tugas agar daftar dan ringkasan mudah dipindai."
                            items={categories}
                            kind="categories"
                            canEdit={canEdit}
                            base={base}
                            remove={remove}
                        />
                        <ConfigurationSection
                            title="Status"
                            description="Nama dan warna dapat diubah; perilaku dasarnya tetap."
                            items={statuses}
                            kind="statuses"
                            canEdit={canEdit}
                            base={base}
                            remove={remove}
                        />
                    </div>
                </div>
            </main>
        </>
    );
}

function ConfigurationSection({
    title,
    description,
    items,
    kind,
    canEdit,
    base,
    remove,
}: {
    title: string;
    description: string;
    items: (ConfigurationItem | StatusItem)[];
    kind: 'categories' | 'statuses';
    canEdit: boolean;
    base: string;
    remove: (kind: 'categories' | 'statuses', item: ConfigurationItem) => void;
}) {
    return (
        <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
            <div className="border-b p-5">
                <h2 className="text-lg font-semibold">{title}</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    {description}
                </p>
            </div>
            {canEdit && (
                <Form
                    action={`${base}/${kind}`}
                    method="post"
                    className="bg-muted/25 border-b"
                    resetOnSuccess
                    errorBag={`${kind}-create`}
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-3 p-4 sm:grid-cols-2">
                            <label className="sm:col-span-2">
                                <span className="mb-1.5 block text-sm font-medium">
                                    Nama {title.toLowerCase()}
                                </span>
                                <Input
                                    name="name"
                                    required
                                    maxLength={255}
                                    className="min-h-11"
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError
                                    className="mt-1.5"
                                    message={errors.name}
                                />
                            </label>
                            <label>
                                <span className="mb-1.5 block text-sm font-medium">
                                    Warna
                                </span>
                                <select
                                    name="color"
                                    defaultValue="rose"
                                    className="border-input bg-background min-h-11 w-full rounded-md border px-3 text-sm"
                                    aria-invalid={Boolean(errors.color)}
                                >
                                    {colors.map((color) => (
                                        <option key={color} value={color}>
                                            {color}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    className="mt-1.5"
                                    message={errors.color}
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
                                    defaultValue={items.length}
                                    className="min-h-11"
                                    aria-invalid={Boolean(errors.sort_order)}
                                />
                                <InputError
                                    className="mt-1.5"
                                    message={errors.sort_order}
                                />
                            </label>
                            {kind === 'statuses' && (
                                <label className="sm:col-span-2">
                                    <span className="mb-1.5 block text-sm font-medium">
                                        Perilaku
                                    </span>
                                    <select
                                        name="behavior"
                                        defaultValue="todo"
                                        className="border-input bg-background min-h-11 w-full rounded-md border px-3 text-sm"
                                        aria-invalid={Boolean(errors.behavior)}
                                    >
                                        {Object.entries(behaviorLabels).map(
                                            ([value, label]) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                    <span className="text-muted-foreground mt-1 block text-xs">
                                        Satu status untuk setiap perilaku.
                                    </span>
                                    <InputError
                                        className="mt-1.5"
                                        message={errors.behavior}
                                    />
                                </label>
                            )}
                            <input type="hidden" name="is_active" value="1" />
                            <Button
                                className="min-h-11 sm:col-span-2"
                                disabled={processing}
                            >
                                <Plus aria-hidden="true" />
                                {processing
                                    ? 'Menambahkan…'
                                    : `Tambah ${title.toLowerCase()}`}
                            </Button>
                        </div>
                    )}
                </Form>
            )}
            <div className="divide-y">
                {items.map((item) => (
                    <article key={item.uuid} className="p-4">
                        {canEdit ? (
                            <Form
                                action={`${base}/${kind}/${item.uuid}`}
                                method="patch"
                                errorBag={`${kind}-${item.uuid}`}
                            >
                                {({ processing, errors }) => (
                                    <div className="grid gap-3 sm:grid-cols-[1fr_8rem_5rem_auto]">
                                        <div>
                                            <Input
                                                name="name"
                                                defaultValue={item.name}
                                                aria-label={`Nama ${item.name}`}
                                                aria-invalid={Boolean(
                                                    errors.name,
                                                )}
                                                className="min-h-11"
                                            />
                                            <InputError
                                                className="mt-1.5"
                                                message={errors.name}
                                            />
                                        </div>
                                        <div>
                                            <select
                                                name="color"
                                                defaultValue={item.color}
                                                aria-label={`Warna ${item.name}`}
                                                aria-invalid={Boolean(
                                                    errors.color,
                                                )}
                                                className="border-input bg-background min-h-11 w-full rounded-md border px-3 text-sm"
                                            >
                                                {colors.map((color) => (
                                                    <option
                                                        key={color}
                                                        value={color}
                                                    >
                                                        {color}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                className="mt-1.5"
                                                message={errors.color}
                                            />
                                        </div>
                                        <div>
                                            <Input
                                                name="sort_order"
                                                type="number"
                                                min="0"
                                                defaultValue={item.sort_order}
                                                aria-label={`Urutan ${item.name}`}
                                                aria-invalid={Boolean(
                                                    errors.sort_order,
                                                )}
                                                className="min-h-11"
                                            />
                                            <InputError
                                                className="mt-1.5"
                                                message={errors.sort_order}
                                            />
                                        </div>
                                        <div className="flex flex-wrap items-start gap-2">
                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value="0"
                                            />
                                            <Label className="flex min-h-11 items-center gap-2 rounded-md border px-3 text-sm">
                                                <Checkbox
                                                    name="is_active"
                                                    value="1"
                                                    defaultChecked={
                                                        item.is_active
                                                    }
                                                />
                                                Aktif
                                            </Label>
                                            <Button
                                                variant="outline"
                                                className="min-h-11"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? 'Menyimpan…'
                                                    : 'Simpan'}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="min-h-11 min-w-11"
                                                disabled={
                                                    item.tasks_count > 0 ||
                                                    processing
                                                }
                                                onClick={() =>
                                                    remove(kind, item)
                                                }
                                                aria-label={`Hapus ${item.name}`}
                                            >
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                            <InputError
                                                className="w-full"
                                                message={errors.is_active}
                                            />
                                        </div>
                                    </div>
                                )}
                            </Form>
                        ) : (
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <p className="font-medium">{item.name}</p>
                                    {'behavior' in item && (
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {behaviorLabels[item.behavior]}
                                        </p>
                                    )}
                                </div>
                                <span className="text-muted-foreground text-xs">
                                    {item.tasks_count} tugas
                                </span>
                            </div>
                        )}
                        <p className="text-muted-foreground mt-2 text-xs">
                            {item.tasks_count} tugas ·{' '}
                            {item.is_active ? 'Aktif' : 'Nonaktif'}
                        </p>
                    </article>
                ))}
            </div>
            {items.length === 0 && (
                <p className="text-muted-foreground p-8 text-center text-sm">
                    Belum ada {title.toLowerCase()}.
                </p>
            )}
        </section>
    );
}
