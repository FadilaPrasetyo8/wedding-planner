<?php

namespace Database\Factories;

use App\Enums\AttachmentModule;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Attachment> */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'wedding_id' => Wedding::factory(),
            'module' => AttachmentModule::Guest->value,
            'disk' => 'local',
            'path' => 'weddings/test/guest/'.Str::uuid().'.pdf',
            'filename_original' => 'bukti.pdf',
            'filename_stored' => Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'uploaded_by_member_id' => WeddingMember::factory(),
        ];
    }
}
