<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\AttachmentModule;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreVendorRequest;
use App\Http\Requests\Logistics\UpdateVendorRequest;
use App\Models\Vendor;
use App\Models\VendorContact;
use App\Models\Wedding;
use App\Support\CustomFields\SubjectCustomFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function index(Request $request, Wedding $wedding): Response
    {
        $search = $request->string('search')->trim()->toString();
        $statusFilter = $request->string('status')->toString();
        $categoryFilter = $request->string('category')->toString();

        $vendors = $wedding->vendors()
            ->with(['category:id,uuid,name', 'contacts:uuid,vendor_id,name,phone,email,role,is_primary'])
            ->withCount('budgetItems')
            ->when($search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$search.'%'))
            ->when($statusFilter !== '' && $statusFilter !== 'all',
                fn ($query) => $query->where('status', $statusFilter))
            ->when($categoryFilter !== '' && $categoryFilter !== 'all',
                fn ($query) => $query->whereHas('category', fn ($category) => $category->where('uuid', $categoryFilter)))
            ->orderBy('name')->orderBy('id')
            ->paginate(20)->withQueryString();

        // Vendor attachments for the current page, keyed by vendor uuid.
        // Vendors deliberately have no custom fields (D-005).
        $vendorAttachments = collect(
            SubjectCustomFields::attachmentsForSubjects($wedding, AttachmentModule::Vendor, $vendors->getCollection())
        )->mapWithKeys(fn ($list, $subjectId): array => [
            (string) $vendors->getCollection()->firstWhere('id', (int) $subjectId)?->uuid => $list,
        ])->all();

        return Inertia::render('weddings/vendors/index', [
            'vendors' => $vendors->through(fn (Vendor $vendor): array => [
                'uuid' => $vendor->uuid,
                'name' => $vendor->name,
                'category' => $vendor->category?->name,
                'categoryUuid' => $vendor->category?->uuid,
                'status' => $vendor->status->value,
                'internalRating' => $vendor->internal_rating,
                'address' => $vendor->address,
                'website' => $vendor->website,
                'socialMedia' => $vendor->social_media,
                'notes' => $vendor->notes,
                'budgetItemsCount' => $vendor->budget_items_count,
                'contacts' => $vendor->contacts->map(fn (VendorContact $contact): array => [
                    'uuid' => $contact->uuid,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'role' => $contact->role,
                    'isPrimary' => $contact->is_primary,
                ])->all(),
            ]),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'category' => $categoryFilter,
            ],
            'statuses' => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], VendorStatus::cases()),
            'categories' => $wedding->vendorCategories()->orderBy('sort_order')->get(['uuid', 'name']),
            'vendorAttachments' => $vendorAttachments,
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreVendorRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->vendorData();

        $vendor = $wedding->vendors()->create([
            'vendor_category_id' => $this->resolveCategoryId($wedding, $data['vendor_category_uuid']),
            'name' => $data['name'],
            'status' => $data['status'],
            'internal_rating' => $data['internal_rating'],
            'address' => $data['address'],
            'website' => $data['website'],
            'social_media' => $data['social_media'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Vendor '.$vendor->name.' berhasil ditambahkan.');
    }

    public function update(UpdateVendorRequest $request, Wedding $wedding, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->wedding_id === $wedding->id, 404);

        $data = $request->vendorData();

        $vendor->update([
            'vendor_category_id' => $this->resolveCategoryId($wedding, $data['vendor_category_uuid']),
            'name' => $data['name'],
            'status' => $data['status'],
            'internal_rating' => $data['internal_rating'],
            'address' => $data['address'],
            'website' => $data['website'],
            'social_media' => $data['social_media'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Vendor '.$vendor->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $vendor), 403);

        $vendor->delete();

        return back()->with('success', 'Vendor berhasil dihapus.');
    }

    private function resolveCategoryId(Wedding $wedding, string $uuid): int
    {
        $id = $wedding->vendorCategories()->where('uuid', $uuid)->value('id');

        abort_unless($id !== null, 404);

        return (int) $id;
    }
}
