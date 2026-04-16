<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ShowController
{
    public function __invoke(Post $post): JsonResponse
    {
        return new JsonResponse(
            data: new PostResource($post),
            status: Response::HTTP_OK,
        );
    }
}
