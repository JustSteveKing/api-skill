<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('updates a post and returns 200', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson("/v1/posts/{$post->id}", [
            'title'   => 'Updated Title',
            'content' => 'Updated content.',
        ])
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('title', 'Updated Title');
});

it('returns 403 when updating another user\'s post', function (): void {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $post  = Post::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->putJson("/v1/posts/{$post->id}", [
            'title'   => 'Hijacked',
            'content' => 'Hijacked content.',
        ])
        ->assertStatus(Response::HTTP_FORBIDDEN)
        ->assertJsonPath('title', 'Forbidden')
        ->assertJsonStructure(['type', 'title', 'status', 'detail']);
});

it('returns problem details when title is missing', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson("/v1/posts/{$post->id}", ['content' => 'No title.'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error');
});

it('returns 401 when unauthenticated', function (): void {
    $post = Post::factory()->create();

    $this->putJson("/v1/posts/{$post->id}", [])
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
