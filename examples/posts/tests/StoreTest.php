<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a post and returns 201', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/v1/posts', [
            'title'   => 'Hello World',
            'content' => 'This is the content.',
        ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('title', 'Hello World');

    expect(Post::query()->count())->toBe(1);
});

it('returns problem details when title is missing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/v1/posts', ['content' => 'Missing a title.'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
});

it('returns 401 when unauthenticated', function (): void {
    $this->postJson('/v1/posts', [])
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
