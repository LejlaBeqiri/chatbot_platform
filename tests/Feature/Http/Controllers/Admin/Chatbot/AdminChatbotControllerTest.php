<?php

use App\Http\Controllers\Admin\Chatbot\AdminChatbotController;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Seed roles and permissions before each test.
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);
});

test('admin can list chatbots', function () {
    // Create several chatbots for testing pagination/listing.
    Chatbot::factory()->count(5)->create();

    $response = $this->getJson(action([AdminChatbotController::class, 'index']));
    $response->assertOk();
    // Assert the JSON structure contains data array and message.
    $response->assertJsonStructure([
        'data' => [
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'tenant_id',
                    'created_at',
                    'updated_at',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ],
    ]);

});

test('admin can show a chatbot', function () {
    $chatbot = Chatbot::factory()->create();

    $response = $this->getJson(action([AdminChatbotController::class, 'show'], ['chatbot' => $chatbot->id]));

    $response->assertOk();
    $response->assertJsonFragment(['id' => $chatbot->id]);
});

test('admin can update a chatbot with valid data', function () {
    // Create a new tenant for the update and a chatbot to update.
    $tenant  = Tenant::factory()->create();
    $chatbot = Chatbot::factory()->create();

    $data = [
        'name'        => 'Updated Chatbot Name',
        'description' => 'Updated description',
        'tenant_id'   => $tenant->id,
    ];

    $response = $this->putJson(action([AdminChatbotController::class, 'update'], ['chatbot' => $chatbot->id]), $data);

    $response->assertOk();
    $response->assertJsonFragment(['message' => 'Chatbot updated successfully.']);
    $this->assertDatabaseHas('chatbots', ['id' => $chatbot->id, 'name' => 'Updated Chatbot Name']);
});

test('fails to update a chatbot with invalid data', function () {
    $chatbot = Chatbot::factory()->create();

    $data = [
        'name' => str_repeat('a', 300),
    ];

    $response = $this->putJson(action([AdminChatbotController::class, 'update'], ['chatbot' => $chatbot->id]), $data);

    $response->assertStatus(422);
});

test('admin can delete a chatbot', function () {
    $chatbot = Chatbot::factory()->create();

    $response = $this->deleteJson(action([AdminChatbotController::class, 'destroy'], ['chatbot' => $chatbot->id]));

    $response->assertOk();
    $this->assertDatabaseMissing('chatbots', ['id' => $chatbot->id]);
});
