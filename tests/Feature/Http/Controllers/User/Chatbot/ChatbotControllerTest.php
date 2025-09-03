<?php

use App\Http\Controllers\User\Chatbot\ChatbotController;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('user lists chatbots', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    // Create multiple chatbots for the tenant
    Chatbot::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $response = $this->getJson(action([ChatbotController::class, 'index']));

    $response->assertStatus(200)
        ->assertJsonStructure([
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
            'success',
            'message',
        ]);
});

test('user creates a chatbot', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    $chatbotData = Chatbot::factory()->make(['tenant_id' => $tenant->id])->toArray();

    $response = $this->postJson(action([ChatbotController::class, 'store']), $chatbotData);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'tenant_id',
            ],
            'success',
            'message',
        ]);
});

test('user shows a chatbot', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    $chatbot = Chatbot::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->getJson(action([ChatbotController::class, 'show'], ['chatbot' => $chatbot->id]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'tenant_id',
            ],
            'success',
            'message',
        ]);
});

test('user updates a chatbot', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    $chatbot    = Chatbot::factory()->create(['tenant_id' => $tenant->id]);
    $updateData = ['name' => 'Updated Chatbot Name'];

    $response = $this->putJson(action([ChatbotController::class, 'update'], ['chatbot' => $chatbot->id]), $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Chatbot Name']);
});

test('user deletes a chatbot', function () {
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    $chatbot = Chatbot::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->deleteJson(action([ChatbotController::class, 'destroy'], ['chatbot' => $chatbot->id]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'success',
            'message',
        ]);
});

test('user cannot access chatbot from different tenant', function () {
    $tenant2 = Tenant::factory()->create();

    $user = User::factory()->create();
    Tenant::factory()->create(['user_id' => $user->id]);
    $user->assignRole('tenant');
    login($user);

    $chatbot = Chatbot::factory()->create(['tenant_id' => $tenant2->id]);

    $response = $this->getJson(action([ChatbotController::class, 'show'], ['chatbot' => $chatbot->id]));
    $response->assertStatus(403);

    $response = $this->putJson(action([ChatbotController::class, 'update'], ['chatbot' => $chatbot->id]), ['name' => 'Updated']);
    $response->assertStatus(403);

    $response = $this->deleteJson(action([ChatbotController::class, 'destroy'], ['chatbot' => $chatbot->id]));
    $response->assertStatus(403);
});
