<?php

declare(strict_types=1);

namespace App\Http\Payloads\Auth;

final class LoginPayload
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
