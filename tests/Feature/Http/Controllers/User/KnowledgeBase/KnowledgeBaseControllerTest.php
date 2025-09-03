<?php

use App\Http\Controllers\User\KnowledgeBase\KnowledgeBaseController;
use App\Models\KnowledgeBase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Create a tenant and associate it with a user.
    $user   = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'user_id' => $user->id,
    ]);
    login($user);
    // Store tenant for use in tests.
    $this->tenant = $tenant;
});

test('user can list knowledge bases', function () {
    // Create several KnowledgeBase records using the tenant_id.
    KnowledgeBase::factory()->count(5)->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->getJson(action([KnowledgeBaseController::class, 'index']));
    $response->assertOk();
});

test('user can create a new knowledge base with valid data', function () {
    // Fake the storage disk to intercept file storage.
    Storage::fake('private');

    // $file = UploadedFile::fake()->create('document.jsonl', 100, 'application/');
    $file = UploadedFile::fake()->create('document.jsonl', 100, 'text/plain');

    $data = [
        'name'        => 'Test Knowledge Base',
        'description' => 'A test description for the knowledge base',
        'tenant_id'   => $this->tenant->id,
        'file'        => $file,
    ];

    $response = $this->post(action([KnowledgeBaseController::class, 'store']), $data);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Test Knowledge Base']);
    $this->assertDatabaseHas('knowledge_bases', [
        'name'        => 'Test Knowledge Base',
        'description' => 'A test description for the knowledge base',
        'tenant_id'   => $this->tenant->id,
    ]);
});

test('user fails to create a new knowledge base with invalid data', function () {
    $data = [
        'name'        => '', // Empty name should trigger a validation error.
        'description' => 'A description without a valid name',
        'tenant_id'   => $this->tenant->id,
    ];

    $response = $this->postJson(action([KnowledgeBaseController::class, 'store']), $data);
    $response->assertStatus(422);
});

test('user can show a knowledge base', function () {
    $knowledgeBase = KnowledgeBase::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->getJson(action([KnowledgeBaseController::class, 'show'], ['knowledgeBase' => $knowledgeBase->id]));
    $response->assertOk();
    $response->assertJsonFragment([
        'id'   => $knowledgeBase->id,
        'name' => $knowledgeBase->name,
    ]);
});

test('user can update a knowledge base with valid data', function () {
    Storage::fake('private');

    $knowledgeBase = KnowledgeBase::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $file = UploadedFile::fake()->create('document.jsonl', 100, 'text/plain');

    $data = [
        'name'        => 'Updated Knowledge Base Name',
        'description' => 'Updated description',
        'tenant_id'   => $this->tenant->id,
        'file'        => $file,
    ];

    // Use put() to allow file uploads.
    $response = $this->put(action([KnowledgeBaseController::class, 'update'], ['knowledgeBase' => $knowledgeBase->id]), $data);
    $response->assertOk();
    $this->assertDatabaseHas('knowledge_bases', [
        'id'   => $knowledgeBase->id,
        'name' => 'Updated Knowledge Base Name',
    ]);
});

test('fails to update a knowledge base with invalid data', function () {
    $knowledgeBase = KnowledgeBase::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $data = [
        'name'        => str_repeat('a', 300), // Assuming this exceeds allowed length.
        'description' => 'Updated description',
        'tenant_id'   => $this->tenant->id,
    ];

    $response = $this->putJson(action([KnowledgeBaseController::class, 'update'], ['knowledgeBase' => $knowledgeBase->id]), $data);
    $response->assertStatus(422);
});

test('user can delete a knowledge base', function () {
    $knowledgeBase = KnowledgeBase::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->deleteJson(action([KnowledgeBaseController::class, 'destroy'], ['knowledgeBase' => $knowledgeBase->id]));
    $response->assertOk();
    $this->assertDatabaseMissing('knowledge_bases', ['id' => $knowledgeBase->id]);
});
