<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Actions\Posts\DestroyPostAction;
use App\Http\Requests\Posts\V1\DestroyRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DestroyController
{
    public function __construct(
        private readonly DestroyPostAction $action,
    ) {}

    public function __invoke(DestroyRequest $request, Post $post): JsonResponse
    {
        $this->action->handle(post: $post);

        return new JsonResponse(
            status: Response::HTTP_ACCEPTED,
        );
    }
}
