<?php

namespace App\Http\Requests\Logistics;

use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;

class StoreVendorContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return $this->user()->can('editOperationalData', $wedding);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:200'],
            'role' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function contactData(): array
    {
        /** @var Vendor $vendor */
        $vendor = $this->route('vendor');

        return [
            'name' => $this->string('name')->toString(),
            'phone' => $this->input('phone') !== null && $this->input('phone') !== '' ? $this->string('phone')->toString() : null,
            'email' => $this->input('email') !== null && $this->input('email') !== '' ? $this->string('email')->toString() : null,
            'role' => $this->input('role') !== null && $this->input('role') !== '' ? $this->string('role')->toString() : null,
            'is_primary' => $this->boolean('is_primary'),
            'vendor_id' => $vendor->id,
            'wedding_id' => $vendor->wedding_id,
        ];
    }
}
