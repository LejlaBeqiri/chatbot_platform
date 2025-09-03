<?php

use App\Http\Controllers\Admin\Tenant\AdminTenantController;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

});
test('admin lists tenants', function () {

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);
    // Create multiple tenants
    Tenant::factory()->count(3)->create();

    $response = $this->getJson(action([AdminTenantController::class, 'index']));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'success',
            'message',
        ]);
});

test('admin creates a tenant', function () {

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);

    $tenantData['tenant'] = Tenant::factory()->make()->toArray();
    unset($tenantData['domain']);

    $tenantData['user'] = [
        'first_name'            => 'John',
        'last_name'             => 'Doe',
        'email'                 => 'john.doe@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson(action([AdminTenantController::class, 'store']), $tenantData);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'id',
            'business_name',
            'industry',
            'domain',
            'logo_url',
            'country',
            'language',
            'user_id',
            'phone',
            'created_at',
            'updated_at',
        ],
        'success',
        'message',
    ]);
});

test('admin shows a tenant', function () {

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);

    $tenant = Tenant::factory()->create();

    $response = $this->getJson(action([AdminTenantController::class, 'show'], ['tenant' => $tenant->id]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'business_name',
                'industry',
                'website',
                'logo_url',
                'country',
                'language',
                'user_id',
                'phone',
                'created_at',
                'updated_at',
            ],
            'success',
            'message',
        ]);
});

test('admin updates a tenant', function () {

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);

    $tenant     = Tenant::factory()->create();
    $updateData = ['business_name' => 'Updated Business Name'];

    $response = $this->putJson(action([AdminTenantController::class, 'update'], ['tenant' => $tenant->id]), $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment(['business_name' => 'Updated Business Name']);
});

test('admin deletes a tenant', function () {

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');
    login($adminUser);

    $tenant = Tenant::factory()->create();

    $response = $this->deleteJson(action([AdminTenantController::class, 'destroy'], ['tenant' => $tenant->id]));

    // Assuming the deletion returns a 204 No Content HTTP status.
    $response->assertStatus(204);
});
