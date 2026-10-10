<?php

namespace App\Http\Controllers\CustomFields;

use App\Enums\CustomFieldModule;
use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Support\CustomFields\SyncCustomFieldValuesAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomFieldValueController extends Controller
{
    public function __construct(private SyncCustomFieldValuesAction $sync) {}

    public function update(Request $request, Wedding $wedding): RedirectResponse
    {
        abort_unless($request->user()->can('editOperationalData', $wedding), 403);

        $validated = $request->validate([
            'module' => ['required', 'string', Rule::enum(CustomFieldModule::class)],
            'subject_uuid' => ['required', 'uuid'],
            'values' => ['present', 'array'],
        ]);

        $module = CustomFieldModule::from($validated['module']);
        $subjectId = $this->resolveSubjectId($wedding, $module, $validated['subject_uuid']);

        // D-027: validation against the wedding's active definitions for this module.
        $payload = $this->sync->validated($wedding, $module, $subjectId, $validated['values']);
        $this->sync->persist($wedding, $module, $subjectId, $payload);

        return back()->with('success', 'Nilai field kustom berhasil disimpan.');
    }

    /**
     * Resolve the subject UUID to a numeric id scoped to the route wedding.
     */
    private function resolveSubjectId(Wedding $wedding, CustomFieldModule $module, string $subjectUuid): int
    {
        $subjectId = $wedding->getConnection()->table($module->subjectTable())
            ->where('wedding_id', $wedding->id)
            ->where('uuid', $subjectUuid)
            ->value('id');

        abort_unless($subjectId !== null, 404, 'Subjek tidak ditemukan pada wedding ini.');

        return (int) $subjectId;
    }
}
