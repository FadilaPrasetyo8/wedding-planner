import { Form } from '@inertiajs/react';
import { SlidersHorizontal } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update as updateCustomFieldValues } from '@/routes/weddings/custom-field-values';

export type CustomFieldDefinitionProps = {
    uuid: string;
    fieldKey: string;
    label: string;
    fieldType: string;
    isRequired: boolean;
    options: string[];
};

type ValueMap = Record<string, unknown>;

const selectClass =
    'border-input bg-background min-h-11 w-full rounded-md border px-3 text-sm';

/**
 * Custom-field value editor for one subject. Values are keyed by field_key
 * and PATCHed via weddings.custom-field-values.update; server-side
 * validation is authoritative (D-027).
 */
export function CustomFieldsPanel({
    weddingUuid,
    module,
    subjectUuid,
    definitions,
    values,
    canEdit,
}: {
    weddingUuid: string;
    module: string;
    subjectUuid: string;
    definitions: CustomFieldDefinitionProps[];
    values: ValueMap;
    canEdit: boolean;
}) {
    if (definitions.length === 0) return null;

    return (
        <section
            aria-label="Field kustom"
            className="bg-card mt-6 overflow-hidden rounded-2xl border shadow-sm"
        >
            <div className="border-b p-5">
                <h2 className="flex items-center gap-2 text-lg font-semibold">
                    <SlidersHorizontal className="size-5" aria-hidden="true" />
                    Field kustom
                </h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    Atribut tambahan yang ditentukan untuk ruang pernikahan ini.
                </p>
            </div>
            {canEdit ? (
                <Form
                    action={updateCustomFieldValues.url({
                        wedding: weddingUuid,
                    })}
                    method="patch"
                    errorBag={`custom-values-${subjectUuid}`}
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-4 p-5">
                            <input type="hidden" name="module" value={module} />
                            <input
                                type="hidden"
                                name="subject_uuid"
                                value={subjectUuid}
                            />
                            {definitions.map((definition) => (
                                <div
                                    key={definition.uuid}
                                    className="grid gap-1"
                                >
                                    <Label htmlFor={`cf-${definition.uuid}`}>
                                        {definition.label}
                                        {definition.isRequired && (
                                            <span
                                                className="text-destructive ml-1"
                                                aria-hidden="true"
                                            >
                                                *
                                            </span>
                                        )}
                                    </Label>
                                    <CustomFieldValueInput
                                        definition={definition}
                                        value={values[definition.fieldKey]}
                                    />
                                    <InputError
                                        message={errors[definition.fieldKey]}
                                    />
                                </div>
                            ))}
                            <Button
                                disabled={processing}
                                className="min-h-11 sm:w-auto"
                            >
                                {processing
                                    ? 'Menyimpan…'
                                    : 'Simpan field kustom'}
                            </Button>
                        </div>
                    )}
                </Form>
            ) : (
                <dl className="grid gap-4 p-5">
                    {definitions.map((definition) => (
                        <div
                            key={definition.uuid}
                            className="grid gap-1 sm:grid-cols-[12rem_1fr]"
                        >
                            <dt className="text-muted-foreground text-sm">
                                {definition.label}
                            </dt>
                            <dd className="text-sm font-medium">
                                {displayValue(definition, values)}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </section>
    );
}

/**
 * Renders the typed input for one definition, carrying the current value.
 * Multi-select uses repeated options[] checkbox inputs.
 */
function CustomFieldValueInput({
    definition,
    value,
}: {
    definition: CustomFieldDefinitionProps;
    value: unknown;
}) {
    const id = `cf-${definition.uuid}`;

    switch (definition.fieldType) {
        case 'textarea':
            return (
                <textarea
                    id={id}
                    name={definition.fieldKey}
                    defaultValue={typeof value === 'string' ? value : ''}
                    rows={3}
                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                />
            );
        case 'checkbox':
            return (
                <Label className="flex min-h-11 items-center gap-2 rounded-md border px-3 text-sm">
                    <input type="hidden" name={definition.fieldKey} value="0" />
                    <Checkbox
                        name={definition.fieldKey}
                        value="1"
                        defaultChecked={value === true}
                    />
                    Ya
                </Label>
            );
        case 'select':
            return (
                <select
                    id={id}
                    name={definition.fieldKey}
                    defaultValue={typeof value === 'string' ? value : ''}
                    className={selectClass}
                >
                    <option value="">—</option>
                    {definition.options.map((option) => (
                        <option key={option} value={option}>
                            {option}
                        </option>
                    ))}
                </select>
            );
        case 'multi_select': {
            const selected = Array.isArray(value) ? value : [];
            return (
                <span className="flex flex-wrap gap-3">
                    {definition.options.map((option) => (
                        <Label
                            key={option}
                            className="flex min-h-11 items-center gap-2 rounded-md border px-3 text-sm"
                        >
                            <Checkbox
                                name={`${definition.fieldKey}[]`}
                                value={option}
                                defaultChecked={selected.includes(option)}
                            />
                            {option}
                        </Label>
                    ))}
                </span>
            );
        }
        case 'currency':
            return (
                <Input
                    id={id}
                    name={definition.fieldKey}
                    type="number"
                    min="0"
                    step="1"
                    defaultValue={
                        typeof value === 'number' ? String(value) : ''
                    }
                />
            );
        case 'number':
            return (
                <Input
                    id={id}
                    name={definition.fieldKey}
                    type="number"
                    step="1"
                    defaultValue={
                        typeof value === 'number' ? String(value) : ''
                    }
                />
            );
        case 'date':
            return (
                <Input
                    id={id}
                    name={definition.fieldKey}
                    type="date"
                    defaultValue={typeof value === 'string' ? value : ''}
                />
            );
        case 'url':
            return (
                <Input
                    id={id}
                    name={definition.fieldKey}
                    type="url"
                    placeholder="https://"
                    defaultValue={typeof value === 'string' ? value : ''}
                />
            );
        default:
            return (
                <Input
                    id={id}
                    name={definition.fieldKey}
                    defaultValue={typeof value === 'string' ? value : ''}
                />
            );
    }
}

function displayValue(
    definition: CustomFieldDefinitionProps,
    values: ValueMap,
): string {
    const value = values[definition.fieldKey];

    if (value === null || value === undefined || value === '') return '—';

    switch (definition.fieldType) {
        case 'checkbox':
            return value ? 'Ya' : 'Tidak';
        case 'multi_select':
            return Array.isArray(value)
                ? value.map((item) => formatDisplayValue(item)).join(', ')
                : formatDisplayValue(value);
        case 'currency':
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(value));
        default:
            return formatDisplayValue(value);
    }
}

function formatDisplayValue(value: unknown): string {
    if (typeof value === 'object' && value !== null) {
        return JSON.stringify(value);
    }

    return String(value);
}
