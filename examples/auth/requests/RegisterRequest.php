<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\V1;

use App\Http\Payloads\Auth\RegisterPayload;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    public function payload(): RegisterPayload
    {
        return new RegisterPayload(
            name:     $this->string('name')->toString(),
            email:    $this->string('email')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
