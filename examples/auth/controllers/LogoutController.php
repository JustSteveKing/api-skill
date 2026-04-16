<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LogoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return new JsonResponse(
            status: Response::HTTP_NO_CONTENT,
        );
    }
}
