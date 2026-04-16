# Conventions Reference

This document covers folder structure, naming conventions, and complete worked examples for the API skill.

---

## Folder Structure

```
app/
  Actions/
    Posts/
      StorePostAction.php
      UpdatePostAction.php
      DestroyPostAction.php
  Http/
    Controllers/
      Auth/
        V1/
          LoginController.php
          LogoutController.php
          RegisterController.php
      Posts/
        V1/
          IndexController.php
          ShowController.php
          StoreController.php
          UpdateController.php
          DestroyController.php
    Middleware/
      ForceJsonResponse.php
      Sunset.php
    Payloads/
      Posts/
        StorePayload.php
        UpdatePayload.php
      Auth/
        RegisterUserPayload.php
    Requests/
      Auth/
        V1/
          LoginRequest.php
          RegisterRequest.php
      Posts/
        V1/
          StoreRequest.php
          UpdateRequest.php
    Resources/
      PostResource.php
      UserResource.php
  Jobs/
    Posts/
      StorePostJob.php
  Policies/
    PostPolicy.php
routes/
  api/
    routes.php
    auth.php
    posts.php
tests/
  Feature/
    Auth/
      V1/
        LoginTest.php
        RegisterTest.php
    Posts/
      V1/
        IndexTest.php
        ShowTest.php
        StoreTest.php
        UpdateTest.php
        DestroyTest.php
```

---

## Naming Conventions

| Layer | Convention | Example |
|---|---|---|
| Controller | `{Action}Controller` | `StoreController`, `DestroyController` |
| Action | `{Action}{Resource}Action` | `StorePostAction`, `UpdatePostAction` |
| Payload (DTO) | `{Action}Payload` | `StorePayload` |
| Form Request | `{Action}Request` | `StoreRequest` |
| API Resource | `{Resource}Resource` | `PostResource` |
| Job | `{Action}{Resource}Job` | `StorePostJob` |
| Route name | `{resource}:{version}:{action}` | `posts:v1:store` |
| Test file | `{Action}Test` in the matching path | `StoreTest.php` |

---

## Route Files

### `routes/api/routes.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::as('auth:')->group(base_path(
    path: 'routes/api/auth.php',
));

Route::as('posts:')->group(base_path(
    path: 'routes/api/posts.php',
));
```

### `routes/api/auth.php`

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('throttle:api')->group(function (): void {
    Route::post('/register', Auth\V1\RegisterController::class)->name('v1:register');
    Route::post('/login', Auth\V1\LoginController::class)->name('v1:login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/logout', Auth\V1\LogoutController::class)->name('v1:logout');
    });
});
```

### `routes/api/posts.php`

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Posts;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/posts')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', Posts\V1\IndexController::class)->name('v1:index');
    Route::post('/', Posts\V1\StoreController::class)->name('v1:store');
    Route::get('/{post}', Posts\V1\ShowController::class)->name('v1:show');
    Route::put('/{post}', Posts\V1\UpdateController::class)->name('v1:update');
    Route::delete('/{post}', Posts\V1\DestroyController::class)->name('v1:destroy');
});
```

---

## Complete Worked Example — Storing a Post

### Payload

```php
<?php

declare(strict_types=1);

namespace App\Http\Payloads\Posts;

final class StorePayload
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly string $userId,
    ) {}

    public function toArray(): array
    {
        return [
            'title'   => $this->title,
            'content' => $this->content,
            'user_id' => $this->userId,
        ];
    }
}
```

### Form Request

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts\V1;

use App\Http\Payloads\Posts\StorePayload;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }

    public function payload(): StorePayload
    {
        return new StorePayload(
            title:   $this->string('title')->toString(),
            content: $this->string('content')->toString(),
            userId:  $this->user()->id,
        );
    }
}
```

### Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Http\Payloads\Posts\StorePayload;
use App\Models\Post;
use Illuminate\Database\DatabaseManager;

final class StorePostAction
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function handle(StorePayload $payload): Post
    {
        return $this->database->transaction(
            callback: fn (): Post => Post::query()->create(
                attributes: $payload->toArray(),
            ),
        );
    }
}
```

### Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Actions\Posts\StorePostAction;
use App\Http\Requests\Posts\V1\StoreRequest;
use App\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class StoreController
{
    public function __construct(
        private readonly StorePostAction $action,
    ) {}

    public function __invoke(StoreRequest $request): JsonResponse
    {
        $post = $this->action->handle(
            payload: $request->payload(),
        );

        return new JsonResponse(
            data: new PostResource($post),
            status: Response::HTTP_CREATED,
        );
    }
}
```

---

## Complete Worked Example — Deleting a Post (Background Job)

### Action

```php
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
```

### Job

```php
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
```

### Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Actions\Posts\DestroyPostAction;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DestroyController
{
    public function __construct(
        private readonly DestroyPostAction $action,
    ) {}

    public function __invoke(Post $post): JsonResponse
    {
        $this->action->handle(post: $post);

        return new JsonResponse(
            status: Response::HTTP_ACCEPTED,
        );
    }
}
```

---

## Complete Worked Example — Registration (Synchronous)

Registration is always synchronous. The user needs a token immediately.

The `RegisterUserPayload::toArray()` returns the password as plain text. This is safe because the `User` model must cast the `password` attribute to `hashed`, which Laravel (12+) handles automatically on assignment:

```php
// app/Models/User.php
protected function casts(): array
{
    return [
        'password' => 'hashed',
    ];
}
```

Without this cast, the password will be stored unhashed. Never rely on the action to hash it manually.

### Payload

```php
<?php

declare(strict_types=1);

namespace App\Http\Payloads\Auth;

final class RegisterUserPayload
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
        ];
    }
}
```

### Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Payloads\Auth\RegisterUserPayload;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

final class RegisterUserAction
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function handle(RegisterUserPayload $payload): array
    {
        return $this->database->transaction(function () use ($payload): array {
            $user = User::query()->create($payload->toArray());

            $token = $user->createToken(name: 'api')->plainTextToken;

            return compact('user', 'token');
        });
    }
}
```

### Controller

```php
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
        [
            'user' => $user,
            'token' => $token,
        ] = $this->action->handle(
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
```

---

## RFC 9457 Problem Details — Exception Handler

Register this in `bootstrap/app.php`. Every exception type that can occur on an API route must be mapped to a Problem Details response. The catch-all `Throwable` handler ensures nothing falls through to an HTML error page:

```php
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (ValidationException $e, Request $request): JsonResponse {
        return new JsonResponse(
            data: [
                'type'   => 'https://example.com/problems/validation-error',
                'title'  => 'Validation Error',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'detail' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ],
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    });

    $exceptions->render(function (AuthenticationException $e, Request $request): JsonResponse {
        return new JsonResponse(
            data: [
                'type'   => 'https://example.com/problems/unauthenticated',
                'title'  => 'Unauthenticated',
                'status' => Response::HTTP_UNAUTHORIZED,
                'detail' => 'You are not authenticated.',
            ],
            status: Response::HTTP_UNAUTHORIZED,
        );
    });

    $exceptions->render(function (AuthorizationException $e, Request $request): JsonResponse {
        return new JsonResponse(
            data: [
                'type'   => 'https://example.com/problems/forbidden',
                'title'  => 'Forbidden',
                'status' => Response::HTTP_FORBIDDEN,
                'detail' => 'You are not authorised to perform this action.',
            ],
            status: Response::HTTP_FORBIDDEN,
        );
    });

    $exceptions->render(function (ModelNotFoundException $e, Request $request): JsonResponse {
        return new JsonResponse(
            data: [
                'type'   => 'https://example.com/problems/not-found',
                'title'  => 'Not Found',
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'The requested resource could not be found.',
            ],
            status: Response::HTTP_NOT_FOUND,
        );
    });

    $exceptions->render(function (\Throwable $e, Request $request): JsonResponse {
        return new JsonResponse(
            data: [
                'type'   => 'https://example.com/problems/server-error',
                'title'  => 'Server Error',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => 'An unexpected error occurred.',
            ],
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    });
})
```

---

## AppServiceProvider — Boot Configuration

Both the rate limiter and the resource wrapping setting belong in `AppServiceProvider::boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    JsonResource::withoutWrapping();

    RateLimiter::for('api', function (Request $request): Limit {
        return Limit::perMinute(60)->by(
            key: $request->user()?->id ?: $request->ip(),
        );
    });
}
```

`JsonResource::withoutWrapping()` disables the automatic `data` envelope on all API resources globally, so resources serialise consistently whether returned directly or wrapped in a `JsonResponse`.

---

## Sunset Middleware

Create `app/Http/Middleware/Sunset.php` to attach the `Sunset` header ([RFC 8594](https://www.rfc-editor.org/rfc/rfc8594)) to deprecated route groups:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class Sunset
{
    public function handle(Request $request, Closure $next, string $date): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Sunset',
            (new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231),
        );

        return $response;
    }
}
```

Register the alias in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'sunset' => \App\Http\Middleware\Sunset::class,
    ]);
})
```

Apply to a versioned route group when a deprecation date is known. Both versions coexist in the same resource file:

```php
// routes/api/posts.php

Route::prefix('v1/posts')
    ->middleware(['auth:sanctum', 'throttle:api', 'sunset:2026-12-31'])
    ->group(function (): void {
        Route::get('/', Posts\V1\IndexController::class)->name('v1:index');
        // ...
    });

Route::prefix('v2/posts')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        Route::get('/', Posts\V2\IndexController::class)->name('v2:index');
        // ...
    });
```

Consumers receive a `Sunset: Wed, 31 Dec 2026 00:00:00 GMT` header on every v1 response, giving them a clear migration deadline.

---

## ForceJsonResponse Middleware

`app/Http/Middleware/ForceJsonResponse.php` — ensures `$request->expectsJson()` returns `true` for all API requests, which guarantees the exception handler always returns Problem Details JSON rather than HTML:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
```

Apply as the first entry in every route group's middleware stack so it fires before `auth:sanctum` and `throttle:api`:

```php
Route::prefix('v1/posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        // ...
    });
```

---

## CORS Configuration

`config/cors.php` — for a standalone API, `paths` must be `['*']` (no web prefix exists):

```php
return [
    'paths'                    => ['*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
```

Add to `.env` per environment:

```
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
```

`HandleCors` is part of Laravel's global middleware stack — no per-route changes are needed.

---

## Testing Conventions

- Test files mirror the controller structure under `tests/Feature/` (e.g. `tests/Feature/Posts/V1/StoreTest.php`).
- Use Pest PHP.
- Every endpoint has at minimum: one happy path test and one unhappy path test.
- Assert on the HTTP status code and the JSON structure.
- Use `actingAs()` with a factory-created user for authenticated endpoints.
- Assert Problem Details shape on error responses.

```php
uses(RefreshDatabase::class);

it('stores a post and returns 201', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/v1/posts', [
            'title'   => 'Hello World',
            'content' => 'Body text.',
        ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('title', 'Hello World');
});

it('returns problem details when title is missing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/v1/posts', ['content' => 'Body text.'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('status', 422)
        ->assertJsonPath('title', 'Validation Error')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
});

it('returns 401 when unauthenticated', function (): void {
    $this->postJson('/v1/posts', [])
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});
```
