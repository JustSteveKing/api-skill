<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a single post', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->create(['title' => 'Hello World']);

    $this->actingAs($user)
        ->getJson("/v1/posts/{$post->id}")
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('title', 'Hello World');
});

it('returns problem details for a non-existent post', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/v1/posts/01JNONEXISTENT000000000000')
        ->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJsonPath('title', 'Not Found')
        ->assertJsonStructure(['type', 'title', 'status', 'detail']);
});

it('returns 401 when unauthenticated', function (): void {
    $post = Post::factory()->create();

    $this->getJson("/v1/posts/{$post->id}")
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
