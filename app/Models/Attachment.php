<?php

namespace App\Models;

use App\Enums\AttachmentModule;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property AttachmentModule $module
 * @property int|null $budget_item_id
 * @property int|null $payment_term_id
 * @property int|null $checklist_item_id
 * @property int|null $vendor_id
 * @property int|null $guest_id
 * @property int|null $task_id
 * @property int|null $uploaded_by_member_id
 * @property string $disk
 * @property string $path
 * @property string $filename_original
 * @property string $filename_stored
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'module', 'budget_item_id', 'payment_term_id', 'checklist_item_id', 'vendor_id', 'guest_id', 'task_id', 'uploaded_by_member_id', 'disk', 'path', 'filename_original', 'filename_stored', 'mime_type', 'extension', 'size_bytes'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (Attachment $attachment) => $attachment->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'module' => AttachmentModule::class,
        ];
    }

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
