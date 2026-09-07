<?php

namespace App\Http\Controllers\Weddings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\StoreTaskCategoryRequest;
use App\Http\Requests\Weddings\UpdateTaskCategoryRequest;
use App\Models\TaskCategory;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

class TaskCategoryController extends Controller
{
    public function store(StoreTaskCategoryRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->taskCategories()->create($request->validated());

        return back()->with('success', 'Kategori tugas berhasil ditambahkan.');
    }

    public function update(UpdateTaskCategoryRequest $request, Wedding $wedding, TaskCategory $taskCategory): RedirectResponse
    {
        $taskCategory->update($request->validated());

        return back()->with('success', 'Kategori tugas berhasil diperbarui.');
    }

    public function destroy(Wedding $wedding, TaskCategory $taskCategory): RedirectResponse
    {
        abort_unless(request()->user()->can('editOperationalData', $wedding), 403);
        if ($taskCategory->tasks()->exists()) {
            return back()->withErrors(['category' => 'Kategori yang sudah digunakan tidak dapat dihapus.']);
        }
        $taskCategory->delete();

        return back()->with('success', 'Kategori tugas berhasil dihapus.');
    }
}
