<?php

namespace App\Support\CustomFields;

use App\Enums\CustomFieldModule;
use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Wedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validates and persists custom-field values for one subject against its
 * wedding's active definitions, per D-027.
 */
class SyncCustomFieldValuesAction
{
    /**
     * @param  array<string, mixed>  $input  raw values keyed by field_key
     * @param  array<int, int>  $definitionIds  restrict sync to these definitions (optional)
     * @return array<string, mixed> validated payload keyed by definition id: {"v": value} or null
     */
    public function validated(Wedding $wedding, CustomFieldModule $module, int $subjectId, array $input): array
    {
        $definitions = $wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $errors = [];
        $payload = [];

        foreach ($definitions as $definition) {
            $present = array_key_exists($definition->field_key, $input);
            $raw = $present ? $input[$definition->field_key] : null;

            if (! $present || $raw === null || $raw === '' || $raw === []) {
                if ($definition->is_required && $present) {
                    $errors[$definition->field_key] = 'Kolom wajib diisi.';
                }

                continue;
            }

            try {
                $payload[$definition->id] = ['v' => $this->validateValue($wedding, $definition, $raw)];
            } catch (ValidationException $exception) {
                $errors[$definition->field_key] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $payload;
    }

    /**
     * Persist a validated payload; missing keys are cleared (subject save semantics).
     *
     * @param  array<int, array{v: mixed}|null>  $payload  definition id => value or null
     */
    public function persist(Wedding $wedding, CustomFieldModule $module, int $subjectId, array $payload): void
    {
        DB::transaction(function () use ($wedding, $module, $subjectId, $payload): void {
            foreach ($payload as $definitionId => $value) {
                $existing = $wedding->customFieldValues()
                    ->where('custom_field_definition_id', $definitionId)
                    ->where('subject_id', $subjectId)
                    ->first();

                if ($value === null) {
                    $existing?->delete();

                    continue;
                }

                if ($existing !== null) {
                    $existing->forceFill(['value' => $value])->save();
                } else {
                    $wedding->customFieldValues()->create([
                        'custom_field_definition_id' => $definitionId,
                        'module' => $module,
                        'subject_id' => $subjectId,
                        'value' => $value,
                    ]);
                }
            }
        });
    }

    /**
     * Type validation per D-027: number/currency whole-integer, currency as
     * whole-rupiah BIGINT, date ISO, select/multi_select within options,
     * checkbox boolean, url valid URL, file_reference same-wedding attachment.
     */
    private function validateValue(Wedding $wedding, CustomFieldDefinition $definition, mixed $raw): mixed
    {
        $key = $definition->field_key;

        return match ($definition->field_type) {
            CustomFieldType::Text => $this->string($raw, $key, 200),
            CustomFieldType::Textarea => $this->string($raw, $key, 2000),
            CustomFieldType::Number => $this->integer($raw, $key),
            CustomFieldType::Currency => $this->currency($raw, $key),
            CustomFieldType::Date => $this->date($raw, $key),
            CustomFieldType::Select => $this->select($definition, $raw, $key),
            CustomFieldType::MultiSelect => $this->multiSelect($definition, $raw, $key),
            CustomFieldType::Checkbox => $this->checkbox($raw, $key),
            CustomFieldType::Url => $this->url($raw, $key),
            CustomFieldType::FileReference => $this->fileReference($wedding, $raw, $key),
        };
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }

    private function string(mixed $raw, string $key, int $max): string
    {
        $value = trim((string) $raw);

        if ($value === '' || mb_strlen($value) > $max) {
            $this->fail($key, "Nilai teks maksimal {$max} karakter.");
        }

        return $value;
    }

    private function integer(mixed $raw, string $key): int
    {
        if (! is_numeric($raw) || (int) $raw != $raw) {
            $this->fail($key, 'Nilai harus berupa angka bulat.');
        }

        return (int) $raw;
    }

    private function currency(mixed $raw, string $key): int
    {
        $value = $this->integer($raw, $key);

        if ($value < 0) {
            $this->fail($key, 'Nilai mata uang tidak boleh negatif.');
        }

        return $value;
    }

    private function date(mixed $raw, string $key): string
    {
        try {
            return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', (string) $raw)->format('Y-m-d');
        } catch (\Throwable) {
            $this->fail($key, 'Nilai harus tanggal dengan format YYYY-MM-DD.');
        }
    }

    private function select(CustomFieldDefinition $definition, mixed $raw, string $key): string
    {
        $options = $definition->options ?? [];

        if (! in_array((string) $raw, $options, true)) {
            $this->fail($key, 'Nilai harus salah satu pilihan yang tersedia.');
        }

        return (string) $raw;
    }

    /** @return list<string> */
    private function multiSelect(CustomFieldDefinition $definition, mixed $raw, string $key): array
    {
        if (! is_array($raw)) {
            $this->fail($key, 'Nilai harus berupa daftar pilihan.');
        }

        $options = $definition->options ?? [];
        $values = array_values(array_map('strval', $raw));

        foreach ($values as $value) {
            if (! in_array($value, $options, true)) {
                $this->fail($key, 'Nilai harus salah satu pilihan yang tersedia.');
            }
        }

        return array_values(array_unique($values));
    }

    private function checkbox(mixed $raw, string $key): bool
    {
        return filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $this->fail($key, 'Nilai harus benar atau salah.');
    }

    private function url(mixed $raw, string $key): string
    {
        if (! filter_var((string) $raw, FILTER_VALIDATE_URL)) {
            $this->fail($key, 'Nilai harus URL yang valid.');
        }

        return (string) $raw;
    }

    private function fileReference(Wedding $wedding, mixed $raw, string $key): string
    {
        $exists = DB::table('attachments')
            ->where('wedding_id', $wedding->id)
            ->where('uuid', (string) $raw)
            ->exists();

        if (! $exists) {
            $this->fail($key, 'Referensi lampiran tidak ditemukan pada wedding ini.');
        }

        return (string) $raw;
    }
}
