---
name: api-skill
description: "Provides opinionated best practices and patterns for building production-ready REST APIs using Laravel. Use this skill when designing API endpoints, implementing resource controllers, or structuring JSON responses in a Laravel environment."
compatibility: "Requires Laravel 13+ and PHP 8.5+."
license: MIT
metadata:
  author: "juststeveking"
  version: "2.0"
---

# API Skill for Laravel Developers

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive. When in doubt, follow the rule.

---

## 1. Route Organisation

Standalone APIs have **no `api` prefix** on any route. Routes live under `routes/api/` as follows:

```
routes/
  api/
    routes.php       ← entry point, requires all resource files
    auth.php
    posts.php        ← one file per resource
    users.php
```

`routes/api/routes.php` loads in each resource using a prefix and naming:

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

Each resource file owns its own version prefix. This keeps versioning explicit and debuggable per resource:

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

- Always version from day one.
- Always use named routes, namespaced to their version (e.g. `posts:v1:index` and `posts:v1:store`).
- The `throttle:api` middleware must always be present.

---

## 2. Single-Action Invokable Controllers

Every controller is a `final` single-action invokable class. No resourceful controllers. No multiple methods per class. Just an invokable action and a constructor for any dependency injection.

Controllers live under `app/Http/Controllers/{Resource}/{Version}/`:

```
app/Http/Controllers/Posts/V1/
  IndexController.php
  ShowController.php
  StoreController.php
  UpdateController.php
  DestroyController.php
```

Dependencies are always injected via the constructor. Never use Facades, `app()`, or `resolve()` inside a controller. The `__invoke` method handles the request and returns a response:

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

## 3. Form Requests and Payloads (DTOs)

Every state-mutating endpoint uses a **Form Request**. Form Requests live under `app/Http/Requests/{Resource}/{Version}/`.

The Form Request **must** expose a `payload()` method that returns a typed DTO from `app/Http/Payloads/`. This keeps controllers free of array handling and makes the data contract explicit:

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

**Payloads (DTOs)** are plain PHP objects. They have a typed constructor and a `toArray()` method that returns what Eloquent expects. They live in `app/Http/Payloads/`:

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

---

## 4. API Resources

Always use Laravel's Eloquent API Resources to transform model data. Generate them with the `--json-api` CLI flag:

```bash
php artisan make:resource PostResource --json-api
```

Resources live under `app/Http/Resources/`. They define the contract for what consumers receive. Never return raw models or plain arrays from a controller.

Call `JsonResource::withoutWrapping()` in `AppServiceProvider::boot()` to disable the automatic `data` envelope globally. This ensures resources serialise consistently whether returned directly from a controller or encoded inside a `JsonResponse`:

```php
use Illuminate\Http\Resources\Json\JsonResource;

public function boot(): void
{
    JsonResource::withoutWrapping();
}
```

---

## 5. HTTP Status Codes

Always use Symfony's `Response::HTTP_*` constants. Never use bare integers. This makes intent readable at a glance:

| Scenario | Constant |
|---|---|
| Successful read | `Response::HTTP_OK` |
| Resource created | `Response::HTTP_CREATED` |
| Accepted for background processing | `Response::HTTP_ACCEPTED` |
| Successful delete (no body) | `Response::HTTP_NO_CONTENT` |
| Validation failed | `Response::HTTP_UNPROCESSABLE_ENTITY` |
| Unauthenticated | `Response::HTTP_UNAUTHORIZED` |
| Forbidden | `Response::HTTP_FORBIDDEN` |
| Not found | `Response::HTTP_NOT_FOUND` |
| Server error | `Response::HTTP_INTERNAL_SERVER_ERROR` |

Import: `use Symfony\Component\HttpFoundation\Response;`

---

## 6. Error Handling — RFC 9457 Problem Details

All error responses follow [RFC 9457 Problem Details](https://www.rfc-editor.org/rfc/rfc9457). The response shape is:

```json
{
    "type": "https://example.com/problems/validation-error",
    "title": "Validation Error",
    "status": 422,
    "detail": "The given data was invalid.",
    "errors": {
        "title": ["The title field is required."]
    }
}
```

The exception handler in `bootstrap/app.php` is responsible for rendering **all** exceptions as Problem Details JSON. No exception should ever produce an HTML response on an API route. At minimum, the following must be handled explicitly:

| Exception | Status | Problem type slug |
|---|---|---|
| `ValidationException` | `422` | `validation-error` |
| `AuthenticationException` | `401` | `unauthenticated` |
| `AuthorizationException` | `403` | `forbidden` |
| `ModelNotFoundException` | `404` | `not-found` |
| `Throwable` (catch-all) | `500` | `server-error` |

The catch-all ensures nothing falls through to Laravel's default HTML error page. See [references/CONVENTIONS.md](references/CONVENTIONS.md) for the full handler implementation.

---

## 7. Authentication — Laravel Sanctum (Stateless Tokens)

All API routes are protected with Laravel Sanctum using stateless API tokens. Never use session-based authentication for API routes.

- Middleware: `auth:sanctum`
- Token abilities/scopes are used to restrict what a token can do
- Registration and login flows are synchronous — token issuance must complete in the same request

```php
// Protecting routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    // ...
});

// Checking abilities in a controller/action
$request->user()->tokenCan('posts:create');
```

---

## 8. Authorization — Policies

Authentication (section 7) confirms who the user is. Authorization confirms what they can do. Use **Laravel Policies** for resource-level access control.

Authorization belongs in the Form Request's `authorize()` method — not in the controller, not in the Action:

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('post'));
}
```

Policies live in `app/Policies/`. A failed authorization throws an `AuthorizationException`, which the exception handler (section 6) renders as a `403 Forbidden` Problem Details response.

Actions operate on data that has already been validated and authorized. Never put policy checks inside an Action.

---

## 9. The Action Pattern

All business logic lives in **Action classes** under `app/Actions/{Resource}/`. Controllers orchestrate, Actions execute. One action per operation.

Actions are injected into controllers via the constructor. They receive a DTO and return a model or value:

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

Every action that writes to the database **must** be wrapped in `$this->database->transaction()` using the injected `DatabaseManager`.

---

## 10. Background Jobs — Non-Blocking Requests

Prefer non-blocking API responses. When an operation can be deferred, dispatch a job and return `202 Accepted`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Http\Payloads\Posts\StorePayload;
use App\Jobs\Posts\StorePostJob;

final class StorePostAction
{
    public function handle(StorePayload $payload): void
    {
        dispatch(new StorePostJob($payload));
    }
}
```

```php
// Controller
return new JsonResponse(status: Response::HTTP_ACCEPTED);
```

When a job receives an Eloquent model, use the `SerializesModels` trait alongside `Queueable`. This stores the model's class and key on the queue rather than the full serialized object, and re-fetches it fresh when the job runs. Because the trait restores model properties via `__wakeup()`, model constructor properties must not be `readonly`:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

final class DestroyPostJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private Post $post, // not readonly — SerializesModels rehydrates via __wakeup()
    ) {}
}
```

**Exceptions to non-blocking:**
- User registration — the user needs a token immediately.
- Login / token issuance — must be synchronous.
- Any operation where the response body is needed by the client to proceed.

Use judgement. If the client cannot function without the result, make it synchronous.

---

## 11. Dependency Injection

Always inject dependencies via the constructor. Never use:

- `Facades` (unless there is no alternative.)
- `app()` helper
- `resolve()` helper

If you know you need a class, declare it in the constructor. Laravel's container will resolve it automatically.

```php
// Correct
final class StoreController
{
    public function __construct(
        private readonly StorePostAction $action,
    ) {}
}

// Wrong — never do this inside a method
public function __invoke(): JsonResponse
{
    $action = app(StorePostAction::class); // ❌
}
```

---

## 12. Pagination

Always use `simplePaginate()`. It avoids the expensive `COUNT(*)` query of `paginate()` and keeps the response lean:

```php
Post::query()->simplePaginate(perPage: 15);
```

Never use `paginate()` on API endpoints.

---

## 13. Rate Limiting

Every API route group must have `throttle:api` middleware. No endpoint is ever unthrottled. Define rate limiters in `App\Providers\AppServiceProvider` (or a dedicated `RateLimitServiceProvider`):

```php
RateLimiter::for('api', function (Request $request): Limit {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## 14. Query Filtering — Spatie Laravel Query Builder

Use [Spatie Laravel Query Builder](https://github.com/spatie/laravel-query-builder) for all filterable list endpoints. Never build raw query strings manually:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class IndexController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $posts = QueryBuilder::for(Post::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::partial('title'),
            ])
            ->allowedSorts(['created_at', 'title'])
            ->simplePaginate(perPage: 15);

        return PostResource::collection($posts);
    }
}
```

---

## 15. Testing

Test from the **outside in**. Drive tests through HTTP — assert on the response, not on internals. Cover both the happy path and the unhappy paths.

```php
it('creates a post and returns 201', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/posts', [
        'title'   => 'Hello World',
        'content' => 'This is the content.',
    ])->assertStatus(
        Response::HTTP_CREATED,
    )->assertJsonPath('title', 'Hello World');
});

it('returns 422 when title is missing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/v1/posts', [
        'content' => 'Missing title.',
    ])->assertStatus(
        Response::HTTP_UNPROCESSABLE_ENTITY,
    )->assertJsonPath('status', 422)->assertJsonPath('title', 'Validation Error');
});
```

---

## 16. API Versioning — Sunset Middleware

When a versioned endpoint is scheduled for removal, signal this to consumers using the `Sunset` HTTP header ([RFC 8594](https://www.rfc-editor.org/rfc/rfc8594)). This gives clients advance warning to migrate before the endpoint disappears.

Create a `Sunset` middleware at `app/Http/Middleware/Sunset.php`:

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

Apply it per route group in the resource file when v1 has a known deprecation date:

```php
Route::prefix('v1/posts')
    ->middleware(['auth:sanctum', 'throttle:api', 'sunset:2026-12-31'])
    ->group(function (): void {
        // v1 routes — sunset date is communicated on every response
    });
```

The v2 routes live in the same resource file without the middleware:

```php
Route::prefix('v2/posts')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        // v2 routes
    });
```

Register the middleware alias in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'sunset' => \App\Http\Middleware\Sunset::class,
    ]);
})
```

---

## 17. Forcing JSON Responses

Laravel's exception handler only returns JSON when `$request->expectsJson()` is true — which checks for the `Accept: application/json` header. If a client omits it, some exceptions (particularly those thrown before the handler fires, such as middleware failures) can still return HTML.

Fix this with a `ForceJsonResponse` middleware at `app/Http/Middleware/ForceJsonResponse.php` that sets the header on every inbound request:

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

Register it as the first middleware on all API routes in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'sunset'            => \App\Http\Middleware\Sunset::class,
        'force.json'        => \App\Http\Middleware\ForceJsonResponse::class,
    ]);
})
```

Apply it at the top of every resource route group:

```php
Route::prefix('v1/posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        // ...
    });
```

---

## 18. CORS

A standalone API will be called from origins other than its own domain. Laravel includes `HandleCors` middleware out of the box, backed by `config/cors.php`.

For a standalone API, set `paths` to `['*']` — there is no web prefix to protect:

```php
// config/cors.php
return [
    'paths'                    => ['*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => ['*'], // lock this down per environment
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
```

In production, replace `allowed_origins: ['*']` with an explicit list of trusted origins. Drive it from an environment variable so it can vary across environments without a code change:

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
```

`HandleCors` is applied globally by Laravel's default middleware stack, so no per-route changes are needed.

---

## References

For detailed folder structure and naming conventions, see [references/CONVENTIONS.md](references/CONVENTIONS.md).
