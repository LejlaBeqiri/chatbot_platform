<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chatbot>
 */
class ChatbotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                  => $this->faker->word(),
            'description'           => $this->faker->optional()->sentence(),
            'tenant_id'             => Tenant::factory(),
            'chatbot_system_prompt' => $this->faker->sentence(),
        ];
    }
}
