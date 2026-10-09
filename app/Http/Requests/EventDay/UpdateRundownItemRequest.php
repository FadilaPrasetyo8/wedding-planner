<?php

namespace App\Http\Requests\EventDay;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRundownItemRequest extends StoreRundownItemRequest
{
    public function authorize(): bool
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return $this->user()->can('editOperationalData', $wedding);
    }
}
