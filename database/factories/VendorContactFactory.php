<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VendorContact> */
class VendorContactFactory extends Factory
{
    protected $model = VendorContact::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->name(),
            'phone' => fake()->optional()->numerify('08##########'),
            'email' => fake()->optional()->safeEmail(),
            'role' => fake()->optional()->jobTitle(),
            'is_primary' => false,
        ];
    }

    /**
     * Create a contact for the given vendor, inheriting its wedding.
     */
    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn () => [
            'vendor_id' => $vendor->id,
            'wedding_id' => $vendor->wedding_id,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
