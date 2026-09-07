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
            ]],
            ['name' => 'Inti Acara', 'slug' => 'inti-acara', 'description' => 'Awal ringkas untuk satu rangkaian acara utama.', 'events' => [
                ['name' => 'Acara Utama', 'type' => 'custom', 'date_offset_days' => 0, 'is_primary' => true, 'sort_order' => 1],
            ], 'tasks' => [
                ['title' => 'Susun rencana utama', 'category' => 'Persiapan Utama', 'status_behavior' => 'todo', 'priority' => 'medium', 'start_offset_days' => -120, 'due_offset_days' => -90],
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
        }
    }
}
