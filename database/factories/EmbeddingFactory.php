<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Embedding>
 */
class EmbeddingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_text' => $this->faker->paragraph,
            'vector'      => array_fill(0, 1536, $this->faker->randomFloat(-1, 1)),
            'metadata'    => [
                'type'      => $this->faker->randomElement(['document', 'question', 'answer']),
                'source'    => $this->faker->url,
                'timestamp' => $this->faker->dateTimeThisYear()->format('c'),
            ],
            'tenant_id'         => \App\Models\Tenant::factory(),
            'knowledge_base_id' => \App\Models\KnowledgeBase::factory(),
        ];
    }
}
