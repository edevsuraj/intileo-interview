<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('post routes require authentication', function () {
    $this->getJson('/api/v1/posts')->assertUnauthorized();
    $this->postJson('/api/v1/posts', [])->assertUnauthorized();
});

test('an authenticated user can create and view posts', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/posts', [
        'title' => 'My first post',
        'content' => 'This is the post content.',
    ]);

    $postId = $response
        ->assertCreated()
        ->assertJsonPath('message', 'Post created successfully.')
        ->assertJsonPath('data.title', 'My first post')
        ->assertJsonPath('data.author.id', $user->id)
        ->json('data.id');

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonPath('data.0.id', $postId);

    $this->getJson("/api/v1/posts/{$postId}")
        ->assertOk()
        ->assertJsonPath('data.content', 'This is the post content.');
});

test('post creation validates its data', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/posts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'content']);
});

test('only the post owner can update or delete a post', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->for($owner)->create();

    Sanctum::actingAs($otherUser);

    $this->putJson("/api/v1/posts/{$post->id}", [
        'title' => 'Not allowed',
    ])->assertForbidden();

    $this->deleteJson("/api/v1/posts/{$post->id}")
        ->assertForbidden();

    Sanctum::actingAs($owner);

    $this->putJson("/api/v1/posts/{$post->id}", [
        'title' => 'Updated title',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated title');

    $this->deleteJson("/api/v1/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Post deleted successfully.');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});
