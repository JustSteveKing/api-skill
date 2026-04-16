<?php

declare(strict_types=1);

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('registers a user and returns a token', function (): void {
    $this->postJson('/v1/auth/register', [
        'name'                  => 'Steve King',
        'email'                 => 'steve@example.com',
        'password'              => 'super-secret-password',
        'password_confirmation' => 'super-secret-password',
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', 'steve@example.com');

    expect(User::query()->count())->toBe(1);
});

it('returns problem details when email is already taken', function (): void {
    User::factory()->create(['email' => 'steve@example.com']);

    $this->postJson('/v1/auth/register', [
        'name'                  => 'Steve King',
        'email'                 => 'steve@example.com',
        'password'              => 'super-secret-password',
        'password_confirmation' => 'super-secret-password',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
});

it('returns problem details when password confirmation does not match', function (): void {
    $this->postJson('/v1/auth/register', [
        'name'                  => 'Steve King',
        'email'                 => 'steve@example.com',
        'password'              => 'super-secret-password',
        'password_confirmation' => 'different-password',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error');
});
