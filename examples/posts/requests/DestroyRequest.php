<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts\V1;

use Illuminate\Foundation\Http\FormRequest;

final class DestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('post'));
    }

    public function rules(): array
    {
        return [];
    }
}
