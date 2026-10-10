<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vendor> */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'vendor_category_id' => VendorCategory::factory(),
            'name' => fake()->company(),
            'status' => VendorStatus::Candidate,
            'internal_rating' => fake()->optional()->numberBetween(0, 5),
            'address' => fake()->optional()->address(),
            'website' => fake()->optional()->url(),
            'social_media' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function selected(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Selected]);
    }
}
