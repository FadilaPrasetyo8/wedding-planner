import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Option = { uuid: string; name: string };
type AssigneeOption = { id: number; name: string };
type PlanningFilters = Record<string, string | number | boolean>;

const planningFilterKeys = [
    'search',
    'status',
    'category',
    'priority',
    'assignee',
    'event',
    'overdue',
    'date_from',
    'date_to',
    'sort',
    'direction',
] as const;

export function queryString(filters: PlanningFilters) {
    const params = new URLSearchParams();

    planningFilterKeys.forEach((key) => {
        const value = filters[key];
        if (
            value !== '' &&
            value !== false &&
            value !== null &&
            value !== undefined
        ) {
            params.set(
                key,
                key === 'overdue' && value === true ? '1' : String(value),
            );
        }
    });

    return params.toString();
}

export default function PlanningFiltersForm({
    action,
    filters,
    categories,
    statuses,
    assignees,
    events,
}: {
    action: string;
    filters: PlanningFilters;
    categories: Option[];
    statuses: Option[];
    assignees: AssigneeOption[];
    events: Option[];
}) {
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(
            new FormData(event.currentTarget).entries(),
        );

        router.get(action, data, { preserveState: true, preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="bg-card mt-5 grid gap-4 rounded-2xl border p-4"
        >
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label className="relative md:col-span-2">
                    <span className="sr-only">Cari tugas</span>
                    <Search
                        className="text-muted-foreground absolute top-3.5 left-3 size-4"
                        aria-hidden="true"
                    />
                    <Input
                        name="search"
                        defaultValue={String(filters.search ?? '')}
                        placeholder="Cari judul atau deskripsi"
                        className="min-h-11 pl-9"
                    />
                </label>
                <SelectFilter
                    name="status"
                    label="Status"
                    value={filters.status}
                    empty="Semua status"
                    options={statuses.map((item) => ({
                        value: item.uuid,
                        label: item.name,
                    }))}
                />
                <SelectFilter
                    name="category"
                    label="Kategori"
                    value={filters.category}
                    empty="Semua kategori"
                    options={categories.map((item) => ({
                        value: item.uuid,
                        label: item.name,
                    }))}
                />
                <SelectFilter
                    name="priority"
                    label="Prioritas"
                    value={filters.priority}
                    empty="Semua prioritas"
                    options={[
                        { value: 'urgent', label: 'Mendesak' },
                        { value: 'high', label: 'Tinggi' },
                        { value: 'medium', label: 'Sedang' },
                        { value: 'low', label: 'Rendah' },
                    ]}
                />
                <SelectFilter
                    name="assignee"
                    label="Penanggung jawab"
                    value={filters.assignee}
                    empty="Semua penanggung jawab"
                    options={assignees.map((item) => ({
                        value: String(item.id),
                        label: item.name,
                    }))}
                />
                <SelectFilter
                    name="event"
                    label="Acara"
                    value={filters.event}
                    empty="Semua acara"
                    options={events.map((item) => ({
                        value: item.uuid,
                        label: item.name,
                    }))}
                />
                <SelectFilter
                    name="overdue"
                    label="Keterlambatan"
                    value={filters.overdue ? '1' : ''}
                    empty="Semua tenggat"
                    options={[{ value: '1', label: 'Hanya terlambat' }]}
                />
            </div>

            <fieldset className="grid gap-3 border-t pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <legend className="sr-only">Rentang tanggal dan urutan</legend>
                <DateFilter
                    name="date_from"
                    label="Aktif mulai"
                    value={filters.date_from}
                />
                <DateFilter
                    name="date_to"
                    label="Aktif sampai"
                    value={filters.date_to}
                />
                <SelectFilter
                    name="sort"
                    label="Urutkan"
                    value={filters.sort ?? 'due_date'}
                    empty="Pilih urutan"
                    options={[
                        { value: 'due_date', label: 'Tenggat' },
                        { value: 'title', label: 'Judul' },
                        { value: 'start_date', label: 'Tanggal mulai' },
                        { value: 'priority', label: 'Prioritas' },
                        { value: 'status', label: 'Status' },
                        { value: 'category', label: 'Kategori' },
                        { value: 'updated_at', label: 'Terakhir diperbarui' },
                    ]}
                />
                <SelectFilter
                    name="direction"
                    label="Arah urutan"
                    value={filters.direction ?? 'asc'}
                    empty="Pilih arah"
                    options={[
                        { value: 'asc', label: 'Naik' },
                        { value: 'desc', label: 'Turun' },
                    ]}
                />
            </fieldset>

            <div className="flex flex-wrap justify-end gap-2 border-t pt-4">
                <Button
                    type="button"
                    variant="ghost"
                    className="min-h-11"
                    onClick={() => router.get(action)}
                >
                    Atur ulang
                </Button>
                <Button className="min-h-11">Terapkan filter</Button>
            </div>
        </form>
    );
}

function SelectFilter({
    name,
    label,
    value,
    empty,
    options,
}: {
    name: string;
    label: string;
    value?: string | number | boolean;
    empty: string;
    options: { value: string; label: string }[];
}) {
    return (
        <label className="grid gap-1.5">
            <span className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                {label}
            </span>
            <select
                name={name}
                defaultValue={String(value ?? '')}
                className="border-input bg-background focus-visible:ring-ring min-h-11 rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
            >
                <option value="">{empty}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}

function DateFilter({
    name,
    label,
    value,
}: {
    name: string;
    label: string;
    value?: string | number | boolean;
}) {
    return (
        <label className="grid gap-1.5">
            <span className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                {label}
            </span>
            <Input
                name={name}
                type="date"
                defaultValue={String(value ?? '')}
                className="min-h-11"
            />
        </label>
    );
}
