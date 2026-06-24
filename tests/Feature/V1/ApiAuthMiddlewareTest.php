<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('custom api auth middleware rejects requests without a bearer token', function () {
    $this->getJson('/api/v1/users/me')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('custom api auth middleware accepts a valid sanctum bearer token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('middleware-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});
