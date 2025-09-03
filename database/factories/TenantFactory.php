<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => $this->faker->company(),
            'industry'      => $this->faker->randomElement(['Technology', 'Healthcare', 'Education', 'Finance', 'Retail']),
            'domain'        => $this->faker->url(),
            'logo_url'      => $this->faker->imageUrl(640, 480, 'business'),
            'country'       => $this->faker->randomElement(['US', 'GB', 'DE', 'FR', 'ES']),
            'language'      => $this->faker->randomElement(['en', 'de', 'fr', 'es']),
            'user_id'       => User::factory(),
            'phone'         => $this->faker->phoneNumber(),
        ];
    }
}
