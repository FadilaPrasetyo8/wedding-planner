<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreVendorCategoryRequest;
use App\Models\VendorCategory;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorCategoryController extends Controller
{
    public function store(StoreVendorCategoryRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->vendorCategories()->create($request->categoryData());

        return back()->with('success', 'Kategori vendor berhasil ditambahkan.');
    }

    public function destroy(Request $request, Wedding $wedding, VendorCategory $vendorCategory): RedirectResponse
    {
        abort_unless($vendorCategory->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $vendorCategory), 403);

        if ($vendorCategory->vendors()->exists()) {
            return back()->withErrors(['reference' => 'Kategori yang masih memiliki vendor tidak dapat dihapus.']);
        }

        $vendorCategory->delete();

        return back()->with('success', 'Kategori vendor berhasil dihapus.');
    }
}
