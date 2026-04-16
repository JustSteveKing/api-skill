# API Skill for Laravel

An opinionated agent skill that encodes production-ready patterns for building REST APIs in Laravel 13+.

Once installed, the agent follows a consistent, prescriptive ruleset across every layer of the stack — routing, controllers, validation, error handling, authentication, testing, and more — without needing to be briefed on preferences at the start of every session.

## Requirements

- Laravel 13+
- PHP 8.5+
- Claude Code or similar

## Installation

### Global (all projects)

```bash
git clone https://github.com/juststeveking/api-skill.git ~/.claude/skills/api-skill
```

### Project-level

```bash
git clone https://github.com/juststeveking/api-skill.git .claude/skills/api-skill
```

Claude Code discovers skills automatically on startup. No further configuration is required.

## What's covered

This skill is prescriptive across 18 topics. When Claude generates or reviews Laravel API code with this skill active, it follows these rules without deviation:

| # | Topic | Rule summary |
|---|---|---|
| 1 | **Route organisation** | No `api` prefix. One file per resource under `routes/api/`. Versioning per-file. Always `throttle:api`. |
| 2 | **Controllers** | `final` single-action invokables only. Constructor DI. No Facades, `app()`, or `resolve()`. |
| 3 | **Form Requests & Payloads** | Every mutating endpoint uses a Form Request with a `payload()` method returning a typed DTO. DTOs are plain PHP objects with a typed constructor and `toArray()`. |
| 4 | **API Resources** | Always use Eloquent Resources (`--json-api` flag). `JsonResource::withoutWrapping()` set globally. |
| 5 | **HTTP status codes** | Symfony `Response::HTTP_*` constants. Never bare integers. |
| 6 | **Error handling** | RFC 9457 Problem Details for all exceptions. Full exception handler covering validation, auth, authorization, not-found, and a catch-all. |
| 7 | **Authentication** | Laravel Sanctum with stateless tokens. `auth:sanctum` + `throttle:api` on all protected routes. |
| 8 | **Authorization** | Laravel Policies. Checks in Form Request `authorize()`. Never inside Actions. |
| 9 | **Action Pattern** | One Action class per operation. All DB writes in `DatabaseManager::transaction()`. |
| 10 | **Background jobs** | Non-blocking by default. `202 Accepted` for deferred work. `SerializesModels` on jobs that receive models. Synchronous only for auth flows. |
| 11 | **Dependency injection** | Constructor injection always. No Facades, `app()`, or `resolve()`. |
| 12 | **Pagination** | `simplePaginate()` only. Never `paginate()`. |
| 13 | **Rate limiting** | `throttle:api` on every route group, including auth. Defined in `AppServiceProvider`. |
| 14 | **Query filtering** | Spatie Laravel Query Builder for all filterable list endpoints. |
| 15 | **Testing** | Pest PHP. Outside-in HTTP tests. Happy path and unhappy paths. Problem Details shape asserted on errors. |
| 16 | **Versioning & Sunset** | `Sunset` middleware (RFC 8594) signals deprecation dates. v1 and v2 coexist in the same route file. |
| 17 | **Forced JSON responses** | `ForceJsonResponse` middleware sets `Accept: application/json` on all requests, ensuring the exception handler always returns JSON. |
| 18 | **CORS** | `HandleCors` is global. `config/cors.php` configured for standalone APIs. `allowed_origins` driven by env var. |

## Structure

```
api-skill/
├── SKILL.md                    ← skill definition and all rules
└── references/
    └── CONVENTIONS.md          ← folder structure, naming tables, and full worked examples
```

`SKILL.md` is the authoritative reference. `CONVENTIONS.md` provides the directory layout, naming convention tables, and complete worked examples for every pattern (store, destroy with background job, registration, exception handler, AppServiceProvider, Sunset middleware, CORS, and testing).

## License

MIT — see [LICENSE](LICENSE).
