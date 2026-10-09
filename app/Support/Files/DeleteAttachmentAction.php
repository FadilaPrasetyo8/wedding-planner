<?php

namespace App\Support\Files;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an attachment object first, then its metadata, per D-028: active
 * metadata must never reference a missing object.
 */
class DeleteAttachmentAction
{
    public function execute(Attachment $attachment): void
    {
        $disk = Storage::disk($attachment->disk);

        // Object first: if deletion fails, metadata stays with the object still
        // present, which is a consistent state. Deleting metadata first could
        // leave an unmanaged object with no reference.
        $disk->delete($attachment->path);

        $attachment->delete();
    }
}
