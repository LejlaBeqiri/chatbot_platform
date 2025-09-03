<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('can log in user', function () {
    $user = User::factory()->create([
        'email'    => 'test@test.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson(route('login'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);
    $response->assertStatus(200);
});
