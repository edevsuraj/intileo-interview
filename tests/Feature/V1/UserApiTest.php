<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('it validates user creation data', function () {
    $this->postJson('/api/v1/users/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('it registers a user and returns an access token', function () {
    $response = $this->postJson('/api/v1/users/register', [
        'name' => 'Registered User',
        'email' => 'registered@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'registered@example.com')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['token']);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect(Hash::check('password123', User::firstOrFail()->password))->toBeTrue();
});

test('it logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/users/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
        'device_name' => 'test-suite',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Logged in successfully.')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['token', 'data']);
});

test('it rejects invalid login credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/users/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('an authenticated user can view their profile and logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-suite')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);

    $this->withToken($token)
        ->postJson('/api/v1/users/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    app('auth')->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertUnauthorized();
});

test('profile and logout require authentication', function () {
    $this->getJson('/api/v1/users/me')->assertUnauthorized();
    $this->postJson('/api/v1/users/logout')->assertUnauthorized();
});
