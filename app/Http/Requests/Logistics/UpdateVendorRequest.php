<?php

namespace App\Http\Requests\Logistics;

use App\Enums\VendorStatus;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return $this->user()->can('editOperationalData', $wedding);
    }

    /** @return array<string, list<string|Rule>> */
    public function rules(): array
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return [
            'name' => ['required', 'string', 'max:200'],
            'vendor_category_uuid' => ['required', 'uuid', Rule::exists('vendor_categories', 'uuid')->where('wedding_id', $wedding->id)],
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'internal_rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:500'],
            'social_media' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, mixed> */
    public function vendorData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'vendor_category_uuid' => (string) $this->string('vendor_category_uuid'),
            'status' => $this->string('status')->toString(),
            'internal_rating' => $this->input('internal_rating') !== null && $this->input('internal_rating') !== '' ? (int) $this->integer('internal_rating') : null,
            'address' => $this->input('address') !== null ? $this->string('address')->toString() : null,
            'website' => $this->input('website') !== null && $this->input('website') !== '' ? $this->string('website')->toString() : null,
            'social_media' => $this->input('social_media') !== null && $this->input('social_media') !== '' ? $this->string('social_media')->toString() : null,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }
}
