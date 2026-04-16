<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Http\Payloads\Posts\UpdatePayload;
use App\Models\Post;
use Illuminate\Database\DatabaseManager;

final class UpdatePostAction
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function handle(Post $post, UpdatePayload $payload): Post
    {
        return $this->database->transaction(function () use ($post, $payload): Post {
            $post->update(attributes: $payload->toArray());

            return $post->refresh();
        });
    }
}
