<?php

declare(strict_types=1);

namespace App\Jobs\Posts;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

final class DestroyPostJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private Post $post, // not readonly — SerializesModels rehydrates via __wakeup()
    ) {}

    public function handle(DatabaseManager $database): void
    {
        $database->transaction(
            callback: fn (): bool => $this->post->delete(),
        );
    }
}
