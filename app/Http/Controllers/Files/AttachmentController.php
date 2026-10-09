<?php

namespace App\Http\Controllers\Files;

use App\Enums\AttachmentModule;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Wedding;
use App\Models\WeddingMember;
use App\Support\ActiveWeddingContext;
use App\Support\Files\DeleteAttachmentAction;
use App\Support\Files\StoreAttachmentAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttachmentController extends Controller
{
    public function store(Request $request, Wedding $wedding): RedirectResponse
    {
        abort_unless($request->user()->can('editOperationalData', $wedding), 403);

        $validated = $request->validate([
            'module' => ['required', 'string', Rule::enum(AttachmentModule::class)],
            'subject_uuid' => ['required', 'uuid'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $module = AttachmentModule::from($validated['module']);
        $subjectId = $this->resolveSubjectId($wedding, $module, $validated['subject_uuid']);

        /** @var WeddingMember $membership */
        $membership = $wedding->memberships()
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->firstOrFail();

        $attachment = app(StoreAttachmentAction::class)->execute(
            $wedding,
            $membership,
            $module,
            $subjectId,
            $validated['file'],
        );

        return back()->with('success', 'Berkas '.$attachment->filename_original.' berhasil diunggah.');
    }

    /**
     * Short-lived signed URL access — membership-guarded, never a permanent URL (D-029).
     */
    public function download(Request $request, Wedding $wedding, Attachment $attachment): RedirectResponse
    {
        abort_unless($attachment->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('view', $attachment), 403);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 410, 'Objek berkas tidak lagi tersedia.');

        return redirect()->away(
            $disk->temporaryUrl($attachment->path, now()->addMinutes(10)),
        );
    }

    public function destroy(Request $request, Wedding $wedding, Attachment $attachment): RedirectResponse
    {
        abort_unless($attachment->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $attachment), 403);

        app(DeleteAttachmentAction::class)->execute($attachment);

        return back()->with('success', 'Berkas berhasil dihapus.');
    }

    /**
     * Resolve the subject UUID to a numeric id scoped to the route wedding.
     */
    private function resolveSubjectId(Wedding $wedding, AttachmentModule $module, string $subjectUuid): int
    {
        $subjectId = $wedding->getConnection()->table($module->subjectTable())
            ->where('wedding_id', $wedding->id)
            ->where('uuid', $subjectUuid)
            ->value('id');

        abort_unless($subjectId !== null, 404, 'Subjek lampiran tidak ditemukan pada wedding ini.');

        return (int) $subjectId;
    }
}
