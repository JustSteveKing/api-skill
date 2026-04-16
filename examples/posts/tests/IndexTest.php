<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a paginated list of posts', function (): void {
    $user = User::factory()->create();
    Post::factory()->count(5)->create();

    $this->actingAs($user)
        ->getJson('/v1/posts')
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure(['data', 'links']);
});

it('filters posts by exact status', function (): void {
    $user = User::factory()->create();
    Post::factory()->create(['status' => 'published']);
    Post::factory()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->getJson('/v1/posts?filter[status]=published')
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(1, 'data');
});

it('returns 401 when unauthenticated', function (): void {
    $this->getJson('/v1/posts')
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
