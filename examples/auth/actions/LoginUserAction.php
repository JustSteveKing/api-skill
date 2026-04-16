<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Payloads\Auth\LoginPayload;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginUserAction
{
    public function handle(LoginPayload $payload): array
    {
        $user = User::query()
            ->where('email', $payload->email)
            ->first();

        if ($user === null || !Hash::check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken(name: 'api')->plainTextToken;

        return compact('user', 'token');
    }
}
