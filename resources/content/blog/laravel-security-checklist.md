---
title: Laravel Security Checklist: 15 Things to Check Before Launch
seo_title: Laravel Security Checklist: 15 Checks Before You Launch
description: A pre-launch Laravel security checklist for Laravel 13: debug mode, keys, cookies, CSRF, validation, uploads, authorization, dependencies and backups.
category: Laravel & PHP
keyword: laravel security checklist
date: 2026-11-10
---

Before a Laravel app goes live, check these 15 things: debug mode off, a private app key, code and `.env` outside the web root, HTTPS with secure cookies, security headers, CSRF protection intact, validated input with mass assignment locked down, safe file uploads, escaped output, parameterised queries, authorization on every action, strong passwords with login throttling, audited dependencies, private logs, and tested backups.

Laravel gives you good defaults for most of these. The failures I see in real projects come from turning a default off during development and forgetting to turn it back on, or from hosting setups that expose files the framework assumes are private.

The checklist below is the one I run on client builds before launch. Code samples are for Laravel 13 on PHP 8.3 or newer, and each item maps to a category in the [OWASP Top 10:2025](https://top10.owasp.org/2025).

## The checklist at a glance

| # | Check | How to verify quickly |
| --- | --- | --- |
| 1 | Debug mode off in production | `php artisan about` shows Debug Mode OFF |
| 2 | App key set, private and rotatable | `APP_KEY` present, not in Git |
| 3 | Only `public/` is web-accessible | Request `/.env` and `/storage/logs/laravel.log` and get 403 or 404 |
| 4 | HTTPS and secure cookies | `SESSION_SECURE_COOKIE=true`, HTTP redirects to HTTPS |
| 5 | Security headers sent | Check response headers in dev tools |
| 6 | CSRF protection on, with a short exclusion list | Review `bootstrap/app.php` |
| 7 | Validation and mass assignment locked down | No `$request->all()` passed to `create()` |
| 8 | File uploads restricted and stored privately | Upload a `.php` file; it should be rejected |
| 9 | Output escaped | Search views for `{!!` |
| 10 | No SQL built from user input | Search for `DB::raw`, `whereRaw`, `orderByRaw` |
| 11 | Authorization on every action | Change an ID in the URL while logged in as another user |
| 12 | Password rules and login throttling | Try 10 wrong logins in a row |
| 13 | Dependencies audited and supported | `composer audit` and `npm audit` are clean |
| 14 | Logs private, no secrets in them | Read a day of logs |
| 15 | Backups exist and restore | Restore to a scratch database |

## Configuration and secrets

### 1. Turn debug mode off

With `APP_DEBUG=true`, an exception renders a page that can include environment details, file paths and query data. It's the first thing an attacker checks. In production:

```ini
APP_ENV=production
APP_DEBUG=false
```

Run `php artisan about` on the server to confirm the environment and debug mode Laravel actually loaded, especially if you use `config:cache`, because a cached config ignores later `.env` edits until you re-cache.

### 2. Keep the app key private and plan for rotation

`APP_KEY` encrypts cookies, sessions and anything you pass through `Crypt` or the `encrypted` cast. Generate it on the server with `php artisan key:generate`, never commit it, and don't reuse it across projects. If it leaks, rotate it. Laravel's `APP_PREVIOUS_KEYS` setting lets the app still decrypt data made with older keys, so a rotation doesn't log everyone out. See the [encryption docs](https://laravel.com/docs/13.x/encryption).

### 3. Make sure only the public folder is served

The web server's document root must be Laravel's `public/` directory. If it points at the project root, `/.env`, `/storage/logs/laravel.log` and `/composer.lock` may all be downloadable.

On shared hosting, where you can't always change the document root, put the app in a folder outside `public_html` or in a subfolder that denies all web access, and keep only `index.php` and your built assets public. I walk through that layout in [How to Deploy Laravel on Shared Hosting](/blog/deploy-laravel-shared-hosting). One lesson from doing this on a real host: don't assume dotfiles are hidden. Apache normally refuses to serve files starting with `.ht`, but on one shared host I deploy to, those files were served like any other. Never leave a copy like `.env.backup` or `.htpasswd-old` anywhere under the web root.

## HTTPS, cookies and headers

### 4. Force HTTPS and lock down cookies

Redirect all HTTP traffic to HTTPS at the server or CDN, then tell Laravel to only send session cookies over HTTPS:

```ini
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

`http_only` and `lax` are already the defaults in `config/session.php`; `secure` is not, because it would break local development over plain HTTP. If your app sits behind a load balancer or CDN that terminates TLS, Laravel needs to trust that proxy to know the request was secure. Use `$middleware->trustProxies(at: [...])` with your proxy's addresses rather than `'*'`, because trusting every proxy lets anyone fake their IP with an `X-Forwarded-For` header. Check first: some hosts already put the real visitor IP in `REMOTE_ADDR`, in which case you don't need to trust anything.

### 5. Send security headers

A small middleware covers the headers every site should send:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

Register it with `$middleware->web(append: SecurityHeaders::class);` in `bootstrap/app.php`. On Apache or LiteSpeed hosting you can set the same headers in `.htaccess` instead. Add a Content-Security-Policy too, but roll it out in report-only mode first; a strict policy written blind will break analytics, payment iframes and embedded maps.

## Requests and input

### 6. Leave CSRF protection on

Laravel 13's `PreventRequestForgery` middleware checks the browser's `Sec-Fetch-Site` header first and falls back to the CSRF token for older browsers and non-HTTPS requests (details in the [CSRF docs](https://laravel.com/docs/13.x/csrf)). It's on by default for web routes. The risk is the exclusion list:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->preventRequestForgery(except: [
        'webhooks/payments',
    ]);
})
```

Only exclude routes that receive server-to-server calls, such as payment webhooks, and verify those requests some other way: a signature, a shared secret or a call back to the provider's API. The same applies to payment gateways like WiPay, which I cover in [How to Integrate WiPay Payments in Laravel](/blog/wipay-laravel-integration).

### 7. Validate everything and block mass assignment

Every write should go through validation, and only validated fields should reach the model:

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'email', 'phone', 'message'])]
class Enquiry extends Model {}
```

```php
$enquiry = Enquiry::create($request->validated());
```

Never pass `$request->all()` to `create()` or `update()`. Someone can add `is_admin=1` or `status=paid` to the form and, if those fields are fillable, the model will accept them. In development, `Model::shouldBeStrict(! app()->isProduction());` in a service provider makes Laravel throw when you try to fill an attribute that isn't fillable, instead of silently dropping it, which surfaces these mistakes early.

### 8. Restrict and isolate file uploads

```php
$request->validate([
    'cv' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
]);

$path = $request->file('cv')->store('applications');
```

The default `local` disk stores files in `storage/app/private`, outside the web root, with a random filename. Serve them back through a controller that checks permissions, or a temporary signed URL. Don't store uploads in `public/` with their original names, and don't trust the file extension a user sends.

### 9. Escape output

Blade's `{{ $value }}` escapes HTML. `{!! $value !!}` doesn't. Search your views for `{!!` and make sure each one prints content you control or have sanitised. If users can write Markdown, convert it with HTML stripped:

```php
Str::markdown($comment->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
```

### 10. Never build SQL from user input

Eloquent and the query builder use bound parameters, so normal queries are safe. Raw methods are not, if you concatenate input into them:

```php
// Unsafe
Product::whereRaw("price > {$request->min}")->get();

// Safe
Product::whereRaw('price > ?', [$request->integer('min')])->get();

// Column names can't be bound, so allow-list them
$sort = in_array($request->sort, ['name', 'price', 'created_at'], true) ? $request->sort : 'created_at';
Product::orderBy($sort)->get();
```

## Authentication and access control

### 11. Authorise every action as well as every page

Broken access control is number one on the OWASP list for a reason. Logging in proves who someone is, not what they may touch. A logged-in customer who changes `/invoices/41` to `/invoices/42` must get a 403.

```php
use Illuminate\Support\Facades\Gate;

public function show(Invoice $invoice)
{
    Gate::authorize('view', $invoice);

    return view('invoices.show', compact('invoice'));
}
```

Put the rule in a policy (`php artisan make:policy InvoicePolicy --model=Invoice`), and for nested routes use `->scopeBindings()` so `/customers/1/invoices/42` fails when invoice 42 isn't customer 1's. For links in emails, such as invites or downloads, use `URL::temporarySignedRoute()` with the `signed` middleware so they can't be guessed or altered.

### 12. Require strong passwords and throttle logins

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

public function boot(): void
{
    Password::defaults(fn () => $this->app->isProduction()
        ? Password::min(12)->uncompromised()
        : Password::min(8));

    RateLimiter::for('login', fn (Request $request) => [
        Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        Limit::perMinute(20)->by($request->ip()),
    ]);
}
```

Use `Password::defaults()` in registration and reset rules, and apply `throttle:login` to the login route. `uncompromised()` checks the password against known breach data using a k-anonymity API, so the password itself never leaves your server. Throttle contact and quote forms too; they're a favourite target for spam bots. Don't reveal whether an email exists on the "forgot password" form.

## Dependencies, logging and recovery

### 13. Audit dependencies and stay on supported versions

Software supply chain failures are now their own OWASP category. Before launch, and on a schedule after it:

```bash
composer audit
npm audit --omit=dev
composer install --no-dev --optimize-autoloader
```

Laravel 13 gets security fixes until the first quarter of 2028, per the [Laravel release notes](https://laravel.com/docs/13.x/releases), and each PHP version has its own security window. Check the hosting's PHP version as well as your own.

### 14. Keep logs private and free of secrets

Logs should live in `storage/logs` (never web-accessible, see item 3) or a log service. Don't log passwords, card data, API keys or full request bodies from payment callbacks. Mark sensitive function parameters with PHP's `#[\SensitiveParameter]` attribute so they're redacted from stack traces. Set up error alerts so you hear about failures before customers tell you.

### 15. Back up, and test the restore

Back up the database and `storage/app` off the server, on a schedule, and restore one to a scratch database before launch. An untested backup is a guess. Decide how long you keep personal data and enforce it in code: Laravel's `Prunable` and `MassPrunable` traits with a scheduled `model:prune` make retention policies automatic, which also helps with data protection laws.

## Run this before every launch

Put the checklist in your project's README or pull request template and tick it on every release, including small ones. Most security regressions come from small changes: a new upload field, a new webhook route, a debugging flag left on after a late-night fix.

If you want a second pair of eyes on a Laravel app before it goes live, I do security reviews and hardening as part of [web app development](/services/web-app-development), and my [Laravel best practices](/blog/laravel-best-practices) post covers the habits that prevent most of these issues in the first place.

## Frequently asked questions

### Is Laravel secure by default?

Laravel ships with strong defaults: CSRF protection, escaped Blade output, parameterised queries, hashed passwords, encrypted cookies and signed URLs. Most real vulnerabilities come from developers bypassing those defaults, such as raw SQL with user input, unescaped output, `$request->all()` passed to models, missing authorization checks or debug mode left on in production.

### How do I check if APP_DEBUG is on in production?

Run `php artisan about` on the server, which shows the environment and whether debug mode is enabled, or `php artisan config:show app.debug`. Without shell access, run the same command once through a cron job and write its output to a file outside the web root. If you cache config, re-run `php artisan config:cache` after editing `.env`.

### Should I exclude webhook routes from CSRF protection?

Yes, but only routes that receive server-to-server calls, because the sender can't include your CSRF token. Keep the exclusion list as short as possible and verify every excluded request another way: check the provider's signature header, compare a hash, or confirm the transaction through the provider's API before changing any order or payment status.

### How often should I run composer audit?

Run it before every deploy and on a schedule, at least weekly, because new advisories are published for packages you already have installed. Many teams add it to CI so a pull request fails when a known vulnerable version is in `composer.lock`. Pair it with `npm audit` for front-end dependencies and update PHP itself when your version leaves security support.
