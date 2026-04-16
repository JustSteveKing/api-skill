<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Jobs\Posts\DestroyPostJob;
use App\Models\Post;

final class DestroyPostAction
{
    public function handle(Post $post): void
    {
        dispatch(new DestroyPostJob($post));
    }
}
