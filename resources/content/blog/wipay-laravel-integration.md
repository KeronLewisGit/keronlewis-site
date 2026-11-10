---
title: How to Integrate WiPay Payments in Laravel (Step by Step)
seo_title: WiPay Laravel Integration: Step-by-Step Guide with Code
description: A step-by-step WiPay Laravel integration: config, payments table, hosted checkout request, verifying the response hash, testing and going live safely.
category: Laravel & PHP
keyword: wipay laravel
date: 2026-11-10
---

To integrate WiPay with Laravel, you send a server-side POST to WiPay's Payments API with your account number, order ID, total and a response URL. WiPay returns a hosted checkout URL, you redirect the customer there, and WiPay sends them back to your response URL with the result. You then verify the returned hash before marking anything paid.

That last step is where most integrations go wrong. The redirect back to your site is just a browser request with a query string. Anyone can type one. If you trust `status=success` without checking the hash, the transaction ID and the amount, you will eventually hand over goods for a payment that never happened.

I've built WiPay into several Laravel projects in Trinidad and Tobago, including a customer portal where clients pay deposits and balances on invoices. This guide is the pattern I use, simplified for a blog post. It follows [WiPay's Payments API documentation](https://docs.wipayfinancial.com/payments-api/payment-request) as of November 2026. WiPay does update its API, so check the docs for anything that looks different.

## What you need before you start

- **A verified WiPay Business account** for live payments. WiPay currently runs platforms for Trinidad and Tobago, Barbados, Grenada, Guyana and Jamaica.
- **Your account number and Payments API key**, from your WiPay dashboard. The API key never leaves your server.
- **Sandbox credentials for testing.** WiPay publishes test values: account number `1234567890` and API key `123`.
- **A Laravel app** on PHP 8.3 or newer. The examples use Laravel 13 but work on 11 and 12 with minor changes.

The endpoint depends on the country platform and environment. For Trinidad and Tobago:

| Environment | Request URL |
|---|---|
| Sandbox | `https://ttsb.wipayfinancial.com/plugins/payments/request` |
| Live | `https://tt.wipayfinancial.com/plugins/payments/request` |

Other countries swap `tt` for `bb`, `gd`, `gy` or `jm`, with `sb` added for sandbox.

## Step 1: Add WiPay settings to your config

Put credentials in `.env` and read them through `config/services.php`. Never call `env()` outside config files, because it returns `null` once config is cached.

```dotenv
WIPAY_ENVIRONMENT=sandbox
WIPAY_ACCOUNT_NUMBER=1234567890
WIPAY_API_KEY=123
WIPAY_COUNTRY_CODE=TT
WIPAY_FEE_STRUCTURE=customer_pay
```

```php
// config/services.php
'wipay' => [
    'environment' => env('WIPAY_ENVIRONMENT', 'sandbox'),
    'account_number' => env('WIPAY_ACCOUNT_NUMBER'),
    'api_key' => env('WIPAY_API_KEY'),
    'country_code' => env('WIPAY_COUNTRY_CODE', 'TT'),
    'fee_structure' => env('WIPAY_FEE_STRUCTURE', 'customer_pay'),
],
```

`fee_structure` decides who pays WiPay's fee: `customer_pay`, `merchant_absorb` or `split`. Agree this with the business owner before launch, because it changes what the customer sees at checkout.

## Step 2: Create a payments table that records every attempt

Store each checkout attempt as its own row, before you send anyone to WiPay. That gives you an order ID to send, somewhere to save WiPay's transaction ID, and an audit trail when a customer says "I paid".

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained();
    $table->string('order_id', 16)->unique();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('TTD');
    $table->string('status')->default('pending');
    $table->string('transaction_id')->nullable()->index();
    $table->boolean('hash_verified')->default(false);
    $table->string('card')->nullable();
    $table->string('message')->nullable();
    $table->json('raw_response')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

WiPay's `order_id` accepts letters, digits, hyphens and underscores, can't start or end with a hyphen or underscore, and should be 16 characters or fewer. Generate it in the model:

```php
protected static function booted(): void
{
    static::creating(function (Payment $payment) {
        do {
            $id = 'P'.strtoupper(Str::random(11));
        } while (static::where('order_id', $id)->exists());

        $payment->order_id ??= $id;
    });
}
```

## Step 3: Build a WiPay gateway class

Keep everything WiPay-specific in one class. Controllers shouldn't know what WiPay's parameters are called.

```php
namespace App\Services\WiPay;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WiPayGateway
{
    public function __construct(
        protected string $accountNumber,
        protected string $apiKey,
        protected string $environment,
        protected string $countryCode,
        protected string $feeStructure,
    ) {}

    public function requestUrl(): string
    {
        $subdomain = strtolower($this->countryCode).($this->environment === 'sandbox' ? 'sb' : '');

        return "https://{$subdomain}.wipayfinancial.com/plugins/payments/request";
    }

    public function createCheckout(Payment $payment, string $responseUrl): string
    {
        $response = Http::acceptJson()
            ->asForm()
            ->timeout(20)
            ->post($this->requestUrl(), [
                'account_number' => $this->accountNumber,
                'country_code' => $this->countryCode,
                'currency' => $payment->currency,
                'environment' => $this->environment,
                'fee_structure' => $this->feeStructure,
                'method' => 'credit_card_co',
                'order_id' => $payment->order_id,
                'origin' => 'MyShopTT',
                'response_url' => $responseUrl,
                'total' => number_format((float) $payment->amount, 2, '.', ''),
                'email' => $payment->invoice->customer->email,
                'fname' => mb_substr($payment->invoice->customer->first_name, 0, 30),
            ]);

        $json = $response->json() ?? [];

        if (! $response->successful() || empty($json['url'])) {
            throw new RuntimeException($json['message'] ?? 'WiPay did not accept the payment request.');
        }

        $payment->forceFill([
            'transaction_id' => $json['transaction_id'] ?? null,
            'raw_response' => ['request' => $json],
        ])->save();

        return $json['url'];
    }

    public function verifyHash(string $transactionId, string $originalTotal, ?string $hash): bool
    {
        if (! $hash || ! $transactionId) {
            return false;
        }

        $expected = md5($transactionId.$originalTotal.$this->apiKey);

        return hash_equals($expected, strtolower($hash));
    }
}
```

A few details that matter:

- **`Accept: application/json`** makes WiPay return JSON containing the checkout `url`, plus a `transaction_id` that I store, instead of redirecting. That lets you record the attempt before the customer leaves your site.
- **`total` must have exactly two decimal places.** Format it once and use the same value when you verify the hash.
- **`email` has a 50-character limit** and must not be the merchant's own registered email, which catches people out when they test with their own address.
- **`method`** is `credit_card_co` for the full checkout page. The older `credit_card` value is deprecated.
- **Optional fields** such as `lname`, `phone` (in E.164 format, for example `+18685551234`) and `data` (a pass-through string echoed back to you) are listed in the docs.

Bind it in `AppServiceProvider` so it's built from config:

```php
$this->app->bind(WiPayGateway::class, fn () => new WiPayGateway(
    accountNumber: (string) config('services.wipay.account_number'),
    apiKey: (string) config('services.wipay.api_key'),
    environment: config('services.wipay.environment') === 'live' ? 'live' : 'sandbox',
    countryCode: strtoupper((string) config('services.wipay.country_code')),
    feeStructure: (string) config('services.wipay.fee_structure'),
));
```

## Step 4: Start the payment and redirect to WiPay

The controller creates a pending payment, asks the gateway for a checkout URL and redirects. If WiPay is down, mark the attempt failed and show a friendly message.

```php
public function pay(Invoice $invoice, WiPayGateway $wipay): RedirectResponse
{
    $this->authorize('pay', $invoice);

    $payment = $invoice->payments()->create([
        'amount' => $invoice->balance(),
        'currency' => $invoice->currency,
        'status' => 'pending',
    ]);

    try {
        $url = $wipay->createCheckout($payment, route('payments.wipay.return'));
    } catch (\Throwable $e) {
        report($e);
        $payment->update(['status' => 'failed', 'message' => 'Checkout could not be started']);

        return back()->with('error', 'We could not reach the payment gateway. Please try again in a moment.');
    }

    return redirect()->away($url);
}
```

On one project I also cancel any older pending attempts for the same invoice before creating a new one, so a customer with two tabs open can't start two live checkouts for the same balance.

## Step 5: Handle the response and verify the WiPay hash

WiPay redirects the customer's browser to your `response_url` with a GET request. The query string includes `status`, `order_id`, `transaction_id`, `total`, `message`, `card` and, on success, a `hash`. The full list is in WiPay's [transaction response docs](https://docs.wipayfinancial.com/payments-api/transaction-response).

The rule from WiPay's docs: `hash = md5(transaction_id + original_total + api_key)`, where `original_total` is the amount you sent in the request, formatted to two decimal places. Don't use the `total` in the response, which can include fees.

Register the route outside your `auth` middleware. The customer's session doesn't always survive the round trip through the payment page, especially on mobile.

```php
Route::get('/payments/wipay/return', WiPayReturnController::class)
    ->name('payments.wipay.return');
```

Then check everything, inside a transaction with the row locked:

```php
public function __invoke(Request $request, WiPayGateway $wipay): RedirectResponse
{
    $payment = Payment::where('order_id', (string) $request->query('order_id'))->first();

    if (! $payment) {
        Log::warning('WiPay return for unknown order', $request->query());

        return redirect()->route('home')->with('error', 'We could not match that payment.');
    }

    $verified = DB::transaction(function () use ($payment, $request, $wipay) {
        $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

        if ($locked->status !== 'pending') {
            return $locked->status === 'success'; // already handled
        }

        $transactionId = (string) $request->query('transaction_id', '');
        $ok = $request->query('status') === 'success'
            && $locked->transaction_id !== null
            && hash_equals($locked->transaction_id, $transactionId)
            && ! Payment::where('transaction_id', $transactionId)
                ->where('status', 'success')->whereKeyNot($locked->id)->exists()
            && $wipay->verifyHash($transactionId, number_format((float) $locked->amount, 2, '.', ''), $request->query('hash'));

        $locked->forceFill([
            'status' => $ok ? 'success' : 'failed',
            'hash_verified' => $ok,
            'card' => mb_substr((string) $request->query('card'), 0, 60),
            'message' => mb_substr((string) $request->query('message'), 0, 255),
            'raw_response' => array_merge($locked->raw_response ?? [], ['return' => $request->query()]),
            'paid_at' => $ok ? now() : null,
        ])->save();

        if ($ok) {
            $locked->invoice->applyPayment($locked);
        }

        return $ok;
    });

    return redirect()->route('invoices.show', $payment->invoice)
        ->with($verified ? 'success' : 'error', $verified ? 'Payment received. Thank you.' : 'Payment was not completed.');
}
```

What each check stops:

| Check | What it prevents |
|---|---|
| Order exists | Random or guessed URLs |
| Row locked, status still pending | Double processing when the customer refreshes |
| Transaction ID matches the one from Step 3 | A real success response replayed against a different order |
| Transaction ID not used on another payment | Reusing one payment to settle two invoices |
| Hash matches, using your stored amount | Tampered amounts and fake success URLs |

Send receipts and staff notifications after the transaction commits, ideally from a queued job, so a mail server problem can't roll back a real payment. WiPay also says you can confirm status with its Transaction Retrieve endpoint as an extra check, and that refunds and chargebacks arrive as webhook events, which you'll want to handle on busier sites. I cover the general pattern in [API integration best practices](/blog/api-integration-best-practices).

## Step 6: Test the integration before you take real money

Use WiPay's sandbox and its published test cards for the full browser flow. For automated tests, fake the HTTP call and build return URLs yourself:

```php
public function test_a_tampered_hash_is_rejected(): void
{
    $payment = Payment::factory()->create([
        'amount' => 3750, 'status' => 'pending', 'transaction_id' => 'T1',
    ]);

    $this->get(route('payments.wipay.return', [
        'status' => 'success',
        'order_id' => $payment->order_id,
        'transaction_id' => 'T1',
        'hash' => md5('T1'.'1.00'.'123'),
    ]));

    $this->assertSame('failed', $payment->fresh()->status);
}

public function test_gateway_outage_is_handled(): void
{
    Http::fake(fn () => throw new ConnectionException('timed out'));
    // ...assert the customer sees an error and nothing is marked paid
}
```

Tests I write on every WiPay project: valid hash succeeds, wrong hash fails, wrong amount fails, a success URL for order A can't mark order B paid, refreshing the return URL doesn't double-apply the payment, and a gateway timeout shows a clear error. Laravel's [HTTP client docs](https://laravel.com/docs/13.x/http-client) cover `Http::fake()` in detail.

## Step 7: Go-live checklist for WiPay in Laravel

- [ ] `WIPAY_ENVIRONMENT=live` with the real account number and API key
- [ ] The app refuses to run live with blank or sandbox credentials (`1234567890` or `123`)
- [ ] Return URL uses HTTPS and the production domain
- [ ] Fee structure agreed with the business
- [ ] One small real payment made and refunded from the WiPay dashboard
- [ ] Failed and unverified responses are logged and visible to an admin
- [ ] Customers see "do not pay again, we're confirming" if a payment can't be verified straight away
- [ ] Config cached after deploy (`php artisan optimize`)

The second item is there because it's easy to deploy with production set to `live` while `.env` still has sandbox credentials, and a guard that throws instead of sending customers to a broken checkout is cheap. For the rest of the pre-launch list, see the [Laravel security checklist](/blog/laravel-security-checklist).

## What to do next

Get the sandbox flow working end to end first, then write the tests in Step 6 before you touch live credentials. If the site runs on shared hosting, [deploying Laravel on shared hosting](/blog/deploy-laravel-shared-hosting) covers getting a queue worker and scheduled tasks going without SSH.

If you'd rather have WiPay set up for you, I build [payment and API integrations](/services/integrations-automation) for Caribbean businesses. You can see a WiPay customer portal I built for [Mixers Anonymous](/work/mixers-anonymous).

## Frequently asked questions

### Does WiPay have an official Laravel package?

WiPay publishes a REST-style Payments API with PHP examples rather than an official Laravel package, as far as I'm aware. Integrating directly with Laravel's HTTP client takes one gateway class and one return controller, so a package isn't really needed. Writing it yourself also means you control the security checks instead of trusting a third-party package.

### How do I verify a WiPay payment?

Recalculate the hash on your server as md5 of the transaction ID, the original total you sent (formatted to two decimal places) and your API key, then compare it with the returned hash using `hash_equals`. Also confirm the order exists, is still pending, and that the transaction ID matches the one WiPay issued when you created the checkout.

### What are the WiPay sandbox credentials?

WiPay's documentation lists account number 1234567890 and API key 123 for sandbox testing, using the sandbox subdomain for your country, such as ttsb.wipayfinancial.com for Trinidad and Tobago. Test cards are listed in WiPay's sandbox documentation. Make sure your app refuses to use these values when the environment is set to live.

### Which countries does WiPay support?

At the time of writing, WiPay's Payments API lists platforms for Trinidad and Tobago, Barbados, Grenada, Guyana and Jamaica. The country code in your request selects the processing platform, not the customer's location, so customers from other countries can still pay. Check WiPay's documentation for current countries and supported currencies.

### Can I accept USD with WiPay in Laravel?

The `currency` field takes an ISO currency code, and WiPay provides a helper endpoint that lists supported currencies for each platform. I've built Trinidad and Tobago projects that invoice in both TTD and USD, but what's available depends on your merchant account. Confirm with WiPay before you show USD prices at checkout.
