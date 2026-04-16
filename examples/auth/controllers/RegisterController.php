<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\V1;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Requests\Auth\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RegisterController
{
    public function __construct(
        private readonly RegisterUserAction $action,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->action->handle(
            payload: $request->payload(),
        );

        return new JsonResponse(
            data: [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
            status: Response::HTTP_CREATED,
        );
    }
}
