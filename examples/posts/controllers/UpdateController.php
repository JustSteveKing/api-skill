<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Actions\Posts\UpdatePostAction;
use App\Http\Requests\Posts\V1\UpdateRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class UpdateController
{
    public function __construct(
        private readonly UpdatePostAction $action,
    ) {}

    public function __invoke(UpdateRequest $request, Post $post): JsonResponse
    {
        $post = $this->action->handle(
            post:    $post,
            payload: $request->payload(),
        );

        return new JsonResponse(
            data: new PostResource($post),
            status: Response::HTTP_OK,
        );
    }
}
