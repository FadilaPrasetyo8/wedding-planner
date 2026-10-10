<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Wedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Reconciles attachment metadata against the storage listing, per D-028:
 * the command is the reconciliation procedure — no attachment-operation
 * record is kept. Reports metadata without objects (stale) and objects
 * without metadata (orphaned), and can prune both with --prune.
 */
class ReconcileAttachments extends Command
{
    protected $signature = 'attachments:reconcile
        {--wedding= : Limit the reconciliation to one wedding uuid}
        {--prune : Delete stale metadata rows and orphaned storage objects}';

    protected $description = 'Compare attachment metadata with stored objects and report or remove mismatches';

    protected int $staleMetadata = 0;

    protected int $orphanedObjects = 0;

    public function handle(): int
    {
        $query = Attachment::query()
            ->orderBy('id');

        if ($this->option('wedding')) {
            $query->whereHas('wedding', fn (Wedding $wedding) => $wedding->where('uuid', $this->option('wedding')));
        }

        $attachments = $query->get();
        $prune = (bool) $this->option('prune');

        // Pass 1: metadata → object existence per disk.
        foreach ($attachments->groupBy('disk') as $diskName => $group) {
            $disk = Storage::disk($diskName);

            $this->info("Memeriksa disk {$diskName} ({$group->count()} metadata)…");

            foreach ($group as $attachment) {
                if (! $disk->exists($attachment->path)) {
                    $this->staleMetadata++;
                    $this->warn("  metadata tanpa objek: {$attachment->path}");

                    if ($prune) {
                        $attachment->delete();
                        $this->line("  → metadata dihapus: {$attachment->uuid}");
                    }
                }
            }
        }

        // Pass 2: storage listing → metadata, so cascaded weddings or failed
        // cleanups surface as orphaned objects.
        $this->scanOrphanedObjects($attachments, $prune);

        $this->newLine();
        $this->info("Metadata tanpa objek: {$this->staleMetadata}".($prune ? ' (dihapus)' : ''));
        $this->info("Objek tanpa metadata: {$this->orphanedObjects}".($prune ? ' (dihapus)' : ''));

        if (! $prune && ($this->staleMetadata > 0 || $this->orphanedObjects > 0)) {
            $this->line('Jalankan ulang dengan --prune untuk membersihkan ketidakcocokan.');
        }

        return self::SUCCESS;
    }

    /**
     * Walk the weddings/{uuid}/{module} namespaces on each disk and compare
     * against known metadata paths.
     *
     * @param  \Illuminate\Support\Collection<array-key, Attachment>  $attachments
     */
    private function scanOrphanedObjects($attachments, bool $prune): void
    {
        foreach ($attachments->groupBy('disk') as $diskName => $group) {
            $knownPaths = $group->pluck('path')->flip();
            $disk = Storage::disk($diskName);

            foreach ($disk->directories('weddings') as $weddingDir) {
                foreach ($disk->directories($weddingDir) as $moduleDir) {
                    foreach ($disk->files($moduleDir) as $file) {
                        if ($knownPaths->has($file)) {
                            continue;
                        }

                        $this->orphanedObjects++;
                        $this->warn("  objek tanpa metadata: {$file}");

                        if ($prune) {
                            $disk->delete($file);
                            $this->line('  → objek dihapus');
                        }
                    }
                }
            }
        }
    }
}
