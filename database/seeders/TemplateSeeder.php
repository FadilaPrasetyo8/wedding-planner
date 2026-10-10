<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'Bismillah Nikah', 'slug' => 'bismillah-nikah', 'description' => 'Susunan awal akad dan resepsi yang tenang dan mudah disesuaikan.', 'events' => [
                ['name' => 'Akad', 'type' => 'akad', 'date_offset_days' => 0, 'is_primary' => true, 'sort_order' => 1],
                ['name' => 'Resepsi', 'type' => 'reception', 'date_offset_days' => 0, 'is_primary' => false, 'sort_order' => 2],
            ], 'tasks' => [
                ['title' => 'Tentukan konsep pernikahan', 'category' => 'Persiapan Utama', 'status_behavior' => 'todo', 'priority' => 'high', 'start_offset_days' => -240, 'due_offset_days' => -210],
                ['title' => 'Siapkan dokumen pernikahan', 'category' => 'Dokumen & Administrasi', 'status_behavior' => 'todo', 'priority' => 'high', 'start_offset_days' => -120, 'due_offset_days' => -60],
            ], 'rundown_items' => [
                ['event_name' => 'Akad', 'title' => 'Pendaftaran tamu akad', 'pic' => 'Panitia', 'start_time' => '07:30', 'end_time' => '08:00', 'sort_order' => 10],
                ['event_name' => 'Akad', 'title' => 'Prosesi akad nikah', 'pic' => 'Penghulu', 'start_time' => '08:00', 'end_time' => '09:00', 'highlights' => 'Ijab kabul', 'sort_order' => 20],
                ['event_name' => 'Resepsi', 'title' => 'Penyambutan tamu', 'pic' => 'Panitia', 'start_time' => '10:00', 'end_time' => '11:00', 'sort_order' => 10],
                ['event_name' => 'Resepsi', 'title' => 'Penampilan musik pengantin', 'pic' => 'Operator musik', 'start_time' => '11:00', 'end_time' => '11:30', 'highlights' => 'Momen foto kedua mempelai', 'sort_order' => 20],
            ], 'playlist_items' => [
                ['event_name' => 'Akad', 'title' => 'Membaca Doa', 'artist' => 'Payung Teduh', 'moment' => 'Prosesi akad', 'sort_order' => 10],
                ['event_name' => 'Resepsi', 'title' => 'Kau Adalah', 'artist' => 'Isyana Sarasvati', 'moment' => 'Panggung pengantin', 'sort_order' => 10],
                ['event_name' => 'Resepsi', 'title' => 'Semesta dan Selamanya', 'artist' => 'Nadin Amizah', 'moment' => 'Panggung pengantin', 'sort_order' => 20],
            ]],
            ['name' => 'Inti Acara', 'slug' => 'inti-acara', 'description' => 'Awal ringkas untuk satu rangkaian acara utama.', 'events' => [
                ['name' => 'Acara Utama', 'type' => 'custom', 'date_offset_days' => 0, 'is_primary' => true, 'sort_order' => 1],
            ], 'tasks' => [
                ['title' => 'Susun rencana utama', 'category' => 'Persiapan Utama', 'status_behavior' => 'todo', 'priority' => 'medium', 'start_offset_days' => -120, 'due_offset_days' => -90],
            ], 'rundown_items' => [
                ['event_name' => 'Acara Utama', 'title' => 'Pembukaan acara', 'pic' => 'MC', 'start_time' => '09:00', 'end_time' => '09:15', 'sort_order' => 10],
            ], 'playlist_items' => [
                ['event_name' => 'Acara Utama', 'title' => 'Instrumen pembuka', 'moment' => 'Pembukaan', 'sort_order' => 10],
            ]],
        ];

        foreach ($templates as $definition) {
            $template = Template::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                ['name' => $definition['name'], 'description' => $definition['description'], 'is_active' => true],
            );
            $section = $template->sections()->updateOrCreate(['name' => 'Acara awal'], ['sort_order' => 1]);
            $section->items()->delete();
            foreach ($definition['events'] as $event) {
                $section->items()->create(['type' => 'event', 'payload' => $event, 'sort_order' => $event['sort_order']]);
            }
            foreach ($definition['tasks'] as $index => $task) {
                $section->items()->create(['type' => 'task', 'payload' => $task, 'sort_order' => 100 + $index]);
            }
            foreach ($definition['rundown_items'] ?? [] as $index => $rundownItem) {
                $section->items()->create(['type' => 'rundown_item', 'payload' => $rundownItem, 'sort_order' => 200 + $index]);
            }
            foreach ($definition['playlist_items'] ?? [] as $index => $playlistItem) {
                $section->items()->create(['type' => 'playlist_item', 'payload' => $playlistItem, 'sort_order' => 300 + $index]);
            }
        }
    }
}
