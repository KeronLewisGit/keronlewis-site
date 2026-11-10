---
title: 15 Laravel Best Practices I Follow on Every Project
seo_title: Laravel Best Practices: 15 Rules I Use on Every Project
description: The 15 Laravel best practices I apply on every build: thin controllers, strict Eloquent, transactions, queues, tests and safe production config.
category: Laravel & PHP
keyword: laravel best practices
date: 2026-11-10
---

The Laravel best practices that matter most are simple: keep controllers thin, validate with Form Requests, turn on strict Eloquent mode in development, eager load relationships, wrap money and stock changes in transactions, push slow work to queues, test the paths that cost money when they break, and lock down production config.

None of that is new. What's hard is doing it on every project, including the small ones with tight deadlines, because those are the ones that come back to bite you a year later.

I've been building with Laravel since 2016, for everything from payment portals and recruitment back offices to business sites deployed on cheap shared hosting. This is the checklist I actually follow. The examples use Laravel 13, which needs PHP 8.3 or newer, but almost all of it applies to Laravel 11 and 12 too.

## Structure: where code belongs in a Laravel app

### 1. Keep controllers thin

A controller should take a request, call something that does the work, and return a response. If a controller method is longer than about 20 lines, the logic probably belongs somewhere else.

```php
public function store(StoreBookingRequest $request, BookingService $bookings): RedirectResponse
{
    $booking = $bookings->create($request->validated());

    return redirect()->route('bookings.show', $booking);
}
```

### 2. Put business logic in service or action classes

Anything with real rules (taking a payment, approving a job application, calculating a quote) goes in a plain class under `app/Services` or `app/Actions`. Controllers, queued jobs and Artisan commands can then all call the same code.

On payment projects I keep the gateway (the HTTP calls to the provider) separate from the payment service (our rules about invoices, deposits and receipts). When the provider changes its API, only one class changes. I show that split in practice in [how to integrate WiPay payments in Laravel](/blog/wipay-laravel-integration).

### 3. Validate with Form Requests, and only use validated data

Form Requests keep validation and authorization out of the controller, and `$request->validated()` gives you only the fields you declared. Never pass `$request->all()` into `create()` or `update()`. See the [validation docs](https://laravel.com/docs/13.x/validation) for the full rule list.

```php
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'event_date' => ['required', 'date', 'after:today'],
            'guests' => ['required', 'integer', 'between:10,500'],
            'email' => ['required', 'email:rfc', 'max:120'],
        ];
    }
}
```

## Eloquent best practices that prevent slow pages and silent bugs

### 4. Turn on strict mode outside production

One line in `AppServiceProvider::boot()` catches three classes of bug: lazy loading (N+1 queries), silently discarded attributes on mass assignment, and reading attributes that weren't selected.

```php
use Illuminate\Database\Eloquent\Model;

public function boot(): void
{
    Model::shouldBeStrict(! $this->app->isProduction());
}
```

In development these throw exceptions, so you find the problem the first time you load the page instead of when the table has 50,000 rows.

### 5. Eager load the relationships you use

If a view loops over orders and prints each customer's name, load customers up front with `with()`. The [Eloquent relationships docs](https://laravel.com/docs/13.x/eloquent-relationships) cover eager loading and constraining eager loads.

```php
$orders = Order::with(['customer', 'items.product'])
    ->latest()
    ->paginate(25);
```

Strict mode (item 4) tells you when you've forgotten one.

### 6. Be explicit about mass assignment

Declare `$fillable` (or use the `#[Fillable]` attribute in Laravel 13) and keep fields like `role`, `status`, `paid_at` and `is_admin` out of it. Set those deliberately in code with `forceFill()` or direct assignment, after you've checked the user is allowed to change them.

### 7. Use casts and backed enums for status fields

Status columns stored as free strings drift: `paid`, `Paid`, `success`, `complete`. A backed enum plus a cast keeps them honest and gives you autocomplete.

```php
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}

protected function casts(): array
{
    return [
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
}
```

## Data integrity: transactions, locks and money

### 8. Wrap multi-step writes in a transaction

If you create an order, decrement stock and record a payment, either all three happen or none do. `DB::transaction()` rolls everything back if an exception is thrown.

### 9. Lock rows when two requests can race

Payment callbacks, stock counts and seat bookings can arrive twice or at the same moment. Inside the transaction, load the row with `lockForUpdate()` and check its current state before changing it.

```php
DB::transaction(function () use ($paymentId, $transactionId) {
    $payment = Payment::whereKey($paymentId)->lockForUpdate()->firstOrFail();

    if ($payment->status !== PaymentStatus::Pending) {
        return; // already handled by another request
    }

    $payment->forceFill([
        'status' => PaymentStatus::Success,
        'transaction_id' => $transactionId,
        'paid_at' => now(),
    ])->save();
});
```

This is the pattern that stops a customer refreshing a "thank you" page and getting a second receipt, or an invoice being marked paid twice. If you integrate with outside services a lot, [API integration best practices](/blog/api-integration-best-practices) covers idempotency in more depth.

## Performance and background work

### 10. Push slow work onto queues

Emails, PDF generation, image resizing and calls to third-party APIs don't belong in the request. Dispatch a job and return the response. Laravel 13 lets you declare retry behavior with attributes on the job class:

```php
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

#[Tries(3)]
#[Backoff(10, 60, 300)]
class SendInvoiceEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function handle(): void
    {
        Mail::to($this->invoice->customer)->send(new InvoiceMail($this->invoice));
    }
}
```

You don't need Redis or a supervisor to start. On shared hosting I use the `database` queue driver and a cron entry that runs `php artisan queue:work --stop-when-empty` every minute. The [queues documentation](https://laravel.com/docs/13.x/queues) covers the other drivers and options, and [deploying Laravel on shared hosting](/blog/deploy-laravel-shared-hosting) covers the cron setup.

### 11. Give every outside HTTP call a timeout

Laravel's HTTP client waits up to 30 seconds by default, which is a long time for a customer staring at a checkout button while a slow payment gateway or courier API thinks about it. Set `timeout()`, retry only what's safe to retry, and log the response when it fails.

```php
$response = Http::acceptJson()
    ->timeout(15)
    ->retry(2, 500, throw: false)
    ->post($url, $payload);
```

Be careful with retries on anything that creates a charge. Retrying a request that actually succeeded the first time can bill a customer twice.

## Testing and code quality

### 12. Write feature tests for the paths that cost money

You don't need 100% coverage. You do need tests for checkout, payment callbacks, sign-up, password reset and anything an admin uses to change money or permissions. Test the failure cases too: a tampered callback, a gateway timeout, a duplicate request.

`Http::fake()` makes third-party APIs easy to test without hitting them:

```php
Http::fake(fn () => throw new ConnectionException('timed out'));

$this->actingAs($customer)
    ->post(route('invoices.pay', $invoice))
    ->assertSessionHas('error');

$this->assertSame('sent', $invoice->fresh()->status);
```

### 13. Format with Pint and check with static analysis

Pint ships with new Laravel apps. Run it before every commit so diffs only show real changes. Add Larastan (PHPStan with Laravel support) at a level the codebase can pass today, then raise it over time. Both catch mistakes AI coding assistants make too, which matters more now that so much code is generated.

## Production configuration and security

### 14. Never call env() outside config files

Once you run `php artisan config:cache` in production, `.env` isn't loaded on each request and `env()` calls in your app code return `null`. Read values through `config()` instead, and put every setting in a config file with a sensible default. The [configuration docs](https://laravel.com/docs/13.x/configuration) explain how caching works.

```php
// config/services.php
'wipay' => [
    'environment' => env('WIPAY_ENVIRONMENT', 'sandbox'),
],

// anywhere else
$environment = config('services.wipay.environment');
```

### 15. Lock down production before launch

My short list for every go-live:

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `php artisan optimize` run as part of deploy (caches config, routes, views and events)
- [ ] Only the `public` folder is web-accessible
- [ ] Policies or gates on every admin action (hiding a button is not authorization)
- [ ] Rate limiting on login, contact and quote forms
- [ ] `DB::prohibitDestructiveCommands($this->app->isProduction())` so nobody runs `migrate:fresh` on live data
- [ ] Error logging that someone actually reads
- [ ] Backups tested by restoring one

There's a lot more to cover on security alone, so I put it in a separate post: the [Laravel security checklist](/blog/laravel-security-checklist).

## What to do next: apply these in order of risk

Don't refactor everything at once. On an existing project, work through this list in this order:

1. Fix production config (items 14 and 15). It's the cheapest change with the biggest risk reduction.
2. Turn on strict mode in development (item 4) and fix what it flags.
3. Add transactions and locks to anything that touches money (items 8 and 9), with tests (item 12).
4. Move emails and API calls to queues (item 10).
5. Clean up controllers and validation (items 1 to 3) as you touch each feature.

If you'd rather have someone do this on your app, I build and maintain [Laravel web applications](/services/web-app-development) and take on audits of existing ones.

## Frequently asked questions

### What is the best folder structure for a Laravel project?

Start with Laravel's default structure and add only what you need. Most of my projects add `app/Services` or `app/Actions` for business logic and `app/Enums` for status values. Domain-based folders can help on very large apps, but on small and medium projects they add ceremony without much benefit. Consistency matters more than the exact layout you choose.

### Should I use the repository pattern in Laravel?

Usually not. Eloquent already gives you a clean query API, and wrapping it in repositories mostly adds files without adding value. Reach for query scopes, custom query builders or service classes first. A repository can make sense if you genuinely need to swap data sources, which is rare in typical business applications.

### How do I prevent N+1 queries in Laravel?

Eager load relationships with `with()` when you fetch the parent records, and turn on `Model::preventLazyLoading()` (or `Model::shouldBeStrict()`) outside production. Lazy loading then throws an exception in development, so you catch the problem when you first load the page. Laravel Debugbar or Telescope can also show the query count per request.

### Is Laravel good for large applications?

Yes. Laravel scales well when you follow the basics: queues for slow work, caching, indexed database columns, eager loading and stateless servers. Most performance problems in Laravel apps come from database queries and missing indexes, not the framework itself. Plenty of high-traffic applications run on Laravel in production.

### What PHP version does Laravel 13 need?

Laravel 13 requires PHP 8.3 or newer and supports PHP 8.3 to 8.5. It was released in March 2026 and gets bug fixes until Q3 2027 and security fixes until March 2028, according to the official support policy. Laravel 12 receives security fixes until February 2027, so plan your upgrade before then.
