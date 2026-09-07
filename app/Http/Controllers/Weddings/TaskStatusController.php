<?php

namespace App\Http\Controllers\Weddings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\StoreTaskStatusRequest;
use App\Http\Requests\Weddings\UpdateTaskStatusRequest;
use App\Models\TaskStatus;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

class TaskStatusController extends Controller
{
    public function store(StoreTaskStatusRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->taskStatuses()->create($request->validated());

        return back()->with('success', 'Status tugas berhasil ditambahkan.');
    }

    public function update(UpdateTaskStatusRequest $request, Wedding $wedding, TaskStatus $taskStatus): RedirectResponse
    {
        $taskStatus->update($request->validated());

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function destroy(Wedding $wedding, TaskStatus $taskStatus): RedirectResponse
    {
        abort_unless(request()->user()->can('editOperationalData', $wedding), 403);
        if ($taskStatus->tasks()->exists()) {
            return back()->withErrors(['status' => 'Status yang sudah digunakan tidak dapat dihapus.']);
        }
        $taskStatus->delete();

        return back()->with('success', 'Status tugas berhasil dihapus.');
    }
}
