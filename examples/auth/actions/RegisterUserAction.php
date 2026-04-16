<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Payloads\Auth\RegisterPayload;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

final class RegisterUserAction
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function handle(RegisterPayload $payload): array
    {
        return $this->database->transaction(function () use ($payload): array {
            $user  = User::query()->create($payload->toArray());
            $token = $user->createToken(name: 'api')->plainTextToken;

            return compact('user', 'token');
        });
    }
}
