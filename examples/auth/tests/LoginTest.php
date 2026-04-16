<?php

declare(strict_types=1);

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a token for valid credentials', function (): void {
    User::factory()->create(['email' => 'steve@example.com']);

    $this->postJson('/v1/auth/login', [
        'email'    => 'steve@example.com',
        'password' => 'password', // default factory password
    ])
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure(['user', 'token'])
        ->assertJsonPath('user.email', 'steve@example.com');
});

it('returns problem details for incorrect credentials', function (): void {
    User::factory()->create(['email' => 'steve@example.com']);

    $this->postJson('/v1/auth/login', [
        'email'    => 'steve@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
});

it('returns problem details for a non-existent email', function (): void {
    $this->postJson('/v1/auth/login', [
        'email'    => 'nobody@example.com',
        'password' => 'password',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error');
});
