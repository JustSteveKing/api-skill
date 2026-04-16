<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts\V1;

use App\Http\Payloads\Posts\UpdatePayload;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }

    public function payload(): UpdatePayload
    {
        return new UpdatePayload(
            title:   $this->string('title')->toString(),
            content: $this->string('content')->toString(),
        );
    }
}
