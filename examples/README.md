# Examples

Complete, layered examples. Every file uses the namespace it would have in a real Laravel application — copy any file directly into the matching path in your project.

## Responses — shared infrastructure

```
responses/
└── ProblemResponse.php     app/Http/Responses/ProblemResponse.php
```

Implements `Responsable`. Returns `Content-Type: application/problem+json` as required by RFC 9457. Used by every exception handler closure — see `references/CONVENTIONS.md` for the full handler.

## Posts — full CRUD

Demonstrates all five operations, a Policy-gated update, a background-job delete, Spatie Query Builder filtering, and `Queue::fake()` testing.

```
posts/
├── Post.php                         app/Models/Post.php
├── PostPolicy.php                   app/Policies/PostPolicy.php
├── routes.php                       routes/api/posts.php
├── payloads/
│   ├── StorePayload.php             app/Http/Payloads/Posts/StorePayload.php
│   └── UpdatePayload.php            app/Http/Payloads/Posts/UpdatePayload.php
├── requests/
│   ├── StoreRequest.php             app/Http/Requests/Posts/V1/StoreRequest.php
│   ├── UpdateRequest.php            app/Http/Requests/Posts/V1/UpdateRequest.php
│   └── DestroyRequest.php           app/Http/Requests/Posts/V1/DestroyRequest.php
├── actions/
│   ├── StorePostAction.php          app/Actions/Posts/StorePostAction.php
│   ├── UpdatePostAction.php         app/Actions/Posts/UpdatePostAction.php
│   └── DestroyPostAction.php        app/Actions/Posts/DestroyPostAction.php
├── jobs/
│   └── DestroyPostJob.php           app/Jobs/Posts/DestroyPostJob.php
├── resources/
│   └── PostResource.php             app/Http/Resources/PostResource.php
├── controllers/
│   ├── IndexController.php          app/Http/Controllers/Posts/V1/IndexController.php
│   ├── ShowController.php           app/Http/Controllers/Posts/V1/ShowController.php
│   ├── StoreController.php          app/Http/Controllers/Posts/V1/StoreController.php
│   ├── UpdateController.php         app/Http/Controllers/Posts/V1/UpdateController.php
│   └── DestroyController.php        app/Http/Controllers/Posts/V1/DestroyController.php
└── tests/
    ├── IndexTest.php                tests/Feature/Posts/V1/IndexTest.php
    ├── ShowTest.php                 tests/Feature/Posts/V1/ShowTest.php
    ├── StoreTest.php                tests/Feature/Posts/V1/StoreTest.php
    ├── UpdateTest.php               tests/Feature/Posts/V1/UpdateTest.php
    └── DestroyTest.php              tests/Feature/Posts/V1/DestroyTest.php
```

## Auth — register, login, logout

Demonstrates synchronous token issuance, credential validation, and token revocation.

```
auth/
├── User.php                         app/Models/User.php
├── routes.php                       routes/api/auth.php
├── payloads/
│   ├── RegisterPayload.php          app/Http/Payloads/Auth/RegisterPayload.php
│   └── LoginPayload.php             app/Http/Payloads/Auth/LoginPayload.php
├── requests/
│   ├── RegisterRequest.php          app/Http/Requests/Auth/V1/RegisterRequest.php
│   └── LoginRequest.php             app/Http/Requests/Auth/V1/LoginRequest.php
├── actions/
│   ├── RegisterUserAction.php       app/Actions/Auth/RegisterUserAction.php
│   └── LoginUserAction.php          app/Actions/Auth/LoginUserAction.php
├── resources/
│   └── UserResource.php             app/Http/Resources/UserResource.php
├── controllers/
│   ├── RegisterController.php       app/Http/Controllers/Auth/V1/RegisterController.php
│   ├── LoginController.php          app/Http/Controllers/Auth/V1/LoginController.php
│   └── LogoutController.php         app/Http/Controllers/Auth/V1/LogoutController.php
└── tests/
    ├── RegisterTest.php             tests/Feature/Auth/V1/RegisterTest.php
    ├── LoginTest.php                tests/Feature/Auth/V1/LoginTest.php
    └── LogoutTest.php               tests/Feature/Auth/V1/LogoutTest.php
```
