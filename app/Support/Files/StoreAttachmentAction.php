<?php

namespace App\Support\Files;

use App\Enums\AttachmentModule;
use App\Models\Attachment;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores private attachment objects and their metadata with compensation, per D-028/D-029.
 */
class StoreAttachmentAction
{
    /**
     * Supported extensions per CLAUDE.md §5.6 — anything else is rejected before storage.
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * @param  array{module: AttachmentModule, subject_id: int}  $subject
     */
    public function execute(
        Wedding $wedding,
        WeddingMember $uploader,
        AttachmentModule $module,
        int $subjectId,
        UploadedFile $file,
    ): Attachment {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $mime = (string) $file->getMimeType();

        $this->assertAllowed($file, $extension, $mime);

        $diskName = (string) config('filesystems.attachment_disk', 'local');
        $disk = Storage::disk($diskName);
        $uuid = (string) Str::uuid();
        $path = sprintf('weddings/%s/%s/%s.%s', $wedding->uuid, $module->value, $uuid, $extension);

        $stored = $disk->put($path, $file->getContent());

        if ($stored === false) {
            abort(500, 'Berkas gagal disimpan.');
        }

        try {
            return DB::transaction(function () use ($wedding, $uploader, $module, $subjectId, $diskName, $path, $file, $uuid, $extension, $mime): Attachment {
                $attachment = new Attachment;
                $attachment->forceFill([
                    'uuid' => $uuid,
                    'wedding_id' => $wedding->id,
                    'module' => $module,
                    $module->subjectColumn() => $subjectId,
                    'uploaded_by_member_id' => $uploader->id,
                    'disk' => $diskName,
                    'path' => $path,
                    'filename_original' => $file->getClientOriginalName() !== '' ? $file->getClientOriginalName() : $uuid.'.'.$extension,
                    'filename_stored' => basename($path),
                    'mime_type' => $mime,
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                ]);
                $attachment->save();

                return $attachment;
            });
        } catch (\Throwable $exception) {
            // Compensation: no active metadata may reference an object that failed to record.
            $disk->delete($path);
            throw $exception;
        }
    }

    /**
     * Server-side validation of extension, detected MIME type, and size (CLAUDE.md §5.6).
     */
    private function assertAllowed(UploadedFile $file, string $extension, string $mime): void
    {
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            abort(422, 'Tipe berkas tidak didukung.');
        }

        $allowedMimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];

        if (! in_array($mime, $allowedMimes[$extension] ?? [], true)) {
            abort(422, 'Tipe berkas tidak sesuai.');
        }

        if ($file->getSize() === false || $file->getSize() > 10 * 1024 * 1024) {
            abort(422, 'Ukuran berkas melebihi 10 MB.');
        }
    }
}
