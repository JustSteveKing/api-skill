<?php

declare(strict_types=1);

use App\Jobs\Posts\DestroyPostJob;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('accepts a delete request and dispatches the job', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/v1/posts/{$post->id}")
        ->assertStatus(Response::HTTP_ACCEPTED);

    Queue::assertDispatched(DestroyPostJob::class);
});

it('returns 403 when deleting another user\'s post', function (): void {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $post  = Post::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->deleteJson("/v1/posts/{$post->id}")
        ->assertStatus(Response::HTTP_FORBIDDEN)
        ->assertJsonPath('title', 'Forbidden');
});

it('returns 401 when unauthenticated', function (): void {
    $post = Post::factory()->create();

    $this->deleteJson("/v1/posts/{$post->id}")
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
