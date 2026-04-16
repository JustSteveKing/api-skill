<?php

declare(strict_types=1);

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('revokes the current token and returns 204', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson('/v1/auth/logout')
        ->assertStatus(Response::HTTP_NO_CONTENT);

    expect($user->tokens()->count())->toBe(0);
});

it('returns 401 when unauthenticated', function (): void {
    $this->deleteJson('/v1/auth/logout')
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
