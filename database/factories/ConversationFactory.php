<?php

namespace Database\Factories;

use App\Models\Chatbot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'       => Tenant::factory(),
            'chatbot_id'      => Chatbot::factory(),
            'user_identifier' => $this->faker->uuid(),
        ];
    }
}
