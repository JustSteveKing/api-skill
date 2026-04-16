<?php

declare(strict_types=1);

namespace App\Http\Payloads\Auth;

final class RegisterPayload
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    /**
     * Maps to Eloquent. The User model must cast 'password' to 'hashed'
     * — this value is stored as-is and the cast handles hashing on write.
     */
    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
        ];
    }
}
