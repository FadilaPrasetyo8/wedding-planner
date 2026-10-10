<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreVendorContactRequest;
use App\Http\Requests\Logistics\UpdateVendorContactRequest;
use App\Models\Vendor;
use App\Models\VendorContact;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorContactController extends Controller
{
    public function store(StoreVendorContactRequest $request, Wedding $wedding, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->wedding_id === $wedding->id, 404);

        $data = $request->contactData();

        if ($data['is_primary']) {
            $vendor->contacts()->update(['is_primary' => false]);
        }

        $vendor->contacts()->create($data);

        return back()->with('success', 'Kontak '.$data['name'].' berhasil ditambahkan.');
    }

    public function update(UpdateVendorContactRequest $request, Wedding $wedding, Vendor $vendor, VendorContact $contact): RedirectResponse
    {
        abort_unless($vendor->wedding_id === $wedding->id && $contact->vendor_id === $vendor->id, 404);

        $data = $request->contactData();

        if ($data['is_primary']) {
            $vendor->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($data);

        return back()->with('success', 'Kontak '.$contact->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, Vendor $vendor, VendorContact $contact): RedirectResponse
    {
        abort_unless($vendor->wedding_id === $wedding->id && $contact->vendor_id === $vendor->id, 404);
        abort_unless($request->user()->can('delete', $contact), 403);

        $contact->delete();

        return back()->with('success', 'Kontak vendor berhasil dihapus.');
    }
}
