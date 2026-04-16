<?php

declare(strict_types=1);

namespace App\Http\Payloads\Posts;

final class UpdatePayload
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
    ) {}

    public function toArray(): array
    {
        return [
            'title'   => $this->title,
            'content' => $this->content,
        ];
    }
}
