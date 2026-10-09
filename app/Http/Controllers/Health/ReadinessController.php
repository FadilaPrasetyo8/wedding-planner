<?php

namespace App\Http\Controllers\Health;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReadinessController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            Storage::disk((string) config('filesystems.attachment_disk', 'local'))->getDriver();

            return response()->json(['status' => 'ok']);
        } catch (Throwable $exception) {
            Log::warning('Readiness dependency unavailable', [
                'dependency' => 'database_or_attachment_storage',
                'exception_class' => $exception::class,
            ]);

            return response()->json(['status' => 'unavailable'], 503);
        }
    }
}
