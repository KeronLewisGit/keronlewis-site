---
title: API Integration Best Practices: Webhooks, Retries and Idempotency
seo_title: API Integration Best Practices: Retries and Idempotency
description: API integration best practices from real payment work: idempotency keys, safe retries with backoff, verified webhooks, timeouts and reconciliation.
category: Web Development
keyword: api integration best practices
date: 2026-11-10
---

The API integration best practices that matter most come down to one assumption: every call over the network can fail, arrive late or arrive twice. So you set timeouts on every request, retry only the failures that are safe to retry, make every write idempotent so a repeat does no harm, verify every webhook before trusting it, and reconcile against the provider's records on a schedule.

Get those five right and most integration bugs never reach production. Skip them and you get the classics: a customer charged twice, an order marked paid that was never paid, a queue stuck retrying a request that will never succeed.

I have built payment, booking and messaging integrations for Caribbean businesses for years, mostly in Laravel, including WiPay card payments. The examples below are in PHP and Laravel, but the ideas apply to any stack.

## Why API integrations break in production

Integrations that work perfectly in development fail in production because the network and the other system stop behaving like your test environment. These are the failure modes I see most, and the practice that handles each.

| What happens | Typical symptom | Practice that prevents it |
|---|---|---|
| Request times out but the provider processed it | Duplicate charge or record on retry | Idempotency keys |
| Provider has a short outage | Burst of 5xx errors, lost work | Retries with backoff in a queue |
| Your retry loop hammers a failing service | Rate limiting, longer outage | Backoff with jitter, retry limits |
| Webhook delivered twice | Duplicate emails, double fulfilment | Deduplicate on event ID |
| Webhook forged by someone else | Orders marked paid for free | Signature verification |
| Events arrive out of order | Status flips back to "pending" | Compare timestamps or fetch current state |
| A webhook never arrives | Stuck orders | Scheduled reconciliation |
| Hanging connection | Workers tied up, slow pages | Short, explicit timeouts |

## 1. Make every write idempotent

An operation is idempotent if doing it twice has the same effect as doing it once. HTTP already defines `GET`, `PUT` and `DELETE` as idempotent in RFC 9110, but the requests that cause real damage are `POST`s: create a charge, create an order, send a message.

The standard fix is an **idempotency key**: a unique value the client generates once per logical operation and sends with every attempt. The server stores the key with the result. If the same key arrives again, it returns the stored result instead of repeating the work. [Stripe's idempotent requests](https://docs.stripe.com/api/idempotent_requests) are the best-known example, and there is an [IETF draft](https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/) standardising an `Idempotency-Key` header.

When you **call** an API that supports keys, generate the key when the user's action is created, store it, and reuse it on every retry:

```php
$payment = Payment::create([
    'invoice_id' => $invoice->id,
    'amount' => $invoice->balance(),
    'idempotency_key' => (string) Str::uuid(),
]);

$response = Http::withHeaders(['Idempotency-Key' => $payment->idempotency_key])
    ->timeout(10)
    ->post($providerUrl, $payload);
```

Generating a new key inside the retry loop defeats the point. The key belongs to the operation, not the attempt.

When you **build** an API, or handle callbacks from one, enforce idempotency in the database rather than with an "if exists" check in PHP. Two requests can pass the check at the same moment. A unique index cannot be fooled:

```php
Schema::create('processed_events', function (Blueprint $table) {
    $table->id();
    $table->string('provider');
    $table->string('event_id');
    $table->timestamps();
    $table->unique(['provider', 'event_id']);
});
```

For state changes such as marking an invoice paid, lock the row inside a transaction and check its current state before changing it. In my WiPay payment code, the return handler locks the payment row, and if it is no longer pending it returns the existing result instead of applying the payment again. A customer refreshing the confirmation page, or the provider calling back twice, then does nothing harmful.

```php
DB::transaction(function () use ($paymentId, $result) {
    $payment = Payment::whereKey($paymentId)->lockForUpdate()->first();

    if ($payment->status !== 'pending') {
        return; // Already handled. Do not apply it twice.
    }

    $payment->update(['status' => $result->status, 'paid_at' => now()]);
    $payment->invoice->applyPayment($payment);
});
```

> Key takeaway: idempotency is enforced by the database (unique constraints and row locks), not by hoping a request only arrives once.

## 2. Retry only what is safe, with backoff and jitter

Retries fix temporary failures. They make permanent failures worse. The rule I follow:

- **Retry:** connection errors, timeouts, `429 Too Many Requests`, `502`, `503`, `504`. Usually `500` too, if the request is idempotent.
- **Do not retry:** `400`, `401`, `403`, `404`, `422` and other client errors. Sending the same bad request again gets the same answer.
- **Respect `Retry-After`** when the provider sends it.
- **Never retry a non-idempotent write without an idempotency key.** This is how double charges happen.

Space retries out with **exponential backoff** (wait longer after each failure) and add **jitter** (a small random amount) so that many clients do not all retry at the same instant.

Laravel's [HTTP client](https://laravel.com/docs/13.x/http-client) handles this for short, in-request retries:

```php
$response = Http::timeout(10)
    ->connectTimeout(5)
    ->retry([200, 500, 1000], when: function (Throwable $e) {
        return $e instanceof ConnectionException
            || ($e instanceof RequestException && in_array($e->response->status(), [429, 502, 503, 504]));
    })
    ->post($url, $payload);
```

Keep in-request retries short. A customer waiting on a page should not sit through a minute of backoff. Anything that can wait belongs in a queued job with its own attempts and backoff:

```php
class SyncOrderToAccounting implements ShouldQueue
{
    public int $tries = 6;

    public function backoff(): array
    {
        return [30, 120, 600, 1800, 3600];
    }

    public function handle(AccountingClient $client): void
    {
        $client->pushOrder($this->order); // uses the order's stored idempotency key
    }

    public function failed(Throwable $e): void
    {
        // Alert a person. Six failures is not a blip.
    }
}
```

The `failed` method matters. A job that silently gives up is worse than one that never ran, because everyone assumes it worked.

## 3. Verify every webhook before you trust it

A webhook endpoint is a public URL that changes data in your system. Anyone who finds it can send a request to it. Treat the payload as untrusted until proven otherwise.

**Verify the signature.** Most providers sign the payload with a shared secret, usually HMAC-SHA256, and send the signature in a header. Recompute it over the raw request body and compare with a constant-time function:

```php
public function __invoke(Request $request): Response
{
    $payload = $request->getContent();
    $expected = hash_hmac('sha256', $payload, config('services.provider.webhook_secret'));

    if (! hash_equals($expected, (string) $request->header('X-Signature'))) {
        abort(401);
    }

    $event = json_decode($payload, true);

    $isNew = ProcessedEvent::query()->insertOrIgnore([
        'provider' => 'provider',
        'event_id' => $event['id'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if ($isNew) {
        HandleProviderEvent::dispatch($event);
    }

    return response()->noContent();
}
```

The header name and exact signing format vary by provider, so follow their documentation. The [Standard Webhooks](https://www.standardwebhooks.com/) specification is a good reference for what a well-designed scheme looks like, including a timestamp in the signed content so old requests cannot be replayed.

The other webhook rules:

- **Respond fast.** Verify, record, queue, return `2xx`. Do the real work in a job. Providers time out slow endpoints and then retry, which creates duplicates.
- **Deduplicate on the event ID.** Providers deliver at least once, not exactly once. The unique index above handles it.
- **Expect events out of order.** A "payment updated" event can arrive before "payment created". Either compare timestamps or, safest of all, treat the webhook as a nudge and fetch the current state from the provider's API.
- **Exclude the route from CSRF protection** and nothing else. Keep rate limiting on it.
- **Log the raw payload** (minus card or personal data) so you can replay or investigate later.

GitHub's published webhook best practices cover the same ground from the provider's side and are worth reading once.

## 4. Treat redirect-based payment returns as untrusted

Many Caribbean gateways, including WiPay, send the customer back to your site with the result in the query string. That URL passes through the customer's browser, so the customer can edit it. Showing "payment successful" because `status=success` is in the URL is a real vulnerability.

What I check before marking anything paid:

- The order or transaction ID matches one **my system created** and is still pending.
- The provider's hash or signature verifies against the **amount I stored**, not an amount from the URL.
- The transaction ID **has not already been used** on another successful payment.
- The whole update runs in a transaction with the row locked, as in the idempotency example above.

If any check fails, I record the attempt as unverified and log it, rather than throwing it away. Sometimes the money did move and a person needs to look. I go through this in detail in [How to Integrate WiPay Payments in Laravel](/blog/wipay-laravel-integration).

## 5. Set timeouts, handle rate limits and log properly

**Timeouts.** PHP's defaults can leave a request hanging far longer than any user will wait. Set an explicit connect timeout and request timeout on every outbound call. A few seconds to connect and ten or so to respond is a reasonable starting point for most APIs; payment and file APIs may need more.

**Rate limits.** Read the provider's limits before you build. Batch where the API allows it, cache reference data that rarely changes, and back off on `429` instead of retrying immediately.

**Errors.** When you build APIs, return consistent, machine-readable errors. [RFC 9457 problem details](https://www.rfc-editor.org/rfc/rfc9457.html) is a sensible format. When you consume them, store the provider's error code and message on your record instead of a bare "failed".

**Logging.** For each outbound call, log the endpoint, your record ID, the status code, duration and the provider's request ID if they send one. Never log secrets, full card numbers or API keys. Redact before writing.

**Secrets.** Keep API keys in environment variables, rotate them when staff or contractors leave, and use separate keys for test and live. The [Laravel Security Checklist](/blog/laravel-security-checklist) covers the rest.

## 6. Reconcile on a schedule

Webhooks get lost. Jobs fail. Providers have incidents. The only way to be sure your records match the provider's is to compare them.

A scheduled reconciliation job runs every hour or night and:

1. Finds records stuck in a pending state longer than they should be.
2. Asks the provider's API for the current status of each.
3. Updates them through the same idempotent code path as the webhook.
4. Flags anything that still does not match for a person to review.

On shared hosting without a queue worker, this can run from the scheduler via cron. It is the single most useful safety net an integration can have.

## API integration checklist

- [ ] Explicit connect and request timeouts on every outbound call
- [ ] Idempotency key stored per operation and reused on retries
- [ ] Unique database constraints on provider event and transaction IDs
- [ ] Row locks around state changes such as "mark paid"
- [ ] Retries only on network errors, `429` and selected `5xx`, with backoff and jitter
- [ ] Slow or retryable work moved into queued jobs with a `failed` handler
- [ ] Webhook signatures verified over the raw body with `hash_equals`
- [ ] Webhooks deduplicated, queued and answered quickly
- [ ] Redirect returns verified server-side against stored amounts
- [ ] Structured logs with record IDs, no secrets
- [ ] Scheduled reconciliation for stuck records
- [ ] Separate test and live credentials

## What to fix first in an existing integration

If you have an integration in production already, work through it in this order, because it follows where the money and data risk is:

1. **Webhook and return verification.** If anything can mark a record paid without a verified signature, fix that today.
2. **Unique constraints** on transaction and event IDs. A migration and an `insertOrIgnore` close the duplicate problem.
3. **Timeouts** on every outbound call.
4. **Reconciliation job** for anything stuck in pending.
5. **Retry policy**, reviewed against the rules above.

For more on structuring the surrounding code, see [15 Laravel Best Practices I Follow on Every Project](/blog/laravel-best-practices). If you would rather have someone review or build the integration, payment gateways, booking systems and third-party APIs are the core of my [integrations and automation work](/services/integrations-automation), and the [Mixers Anonymous project](/work/mixers-anonymous) shows a WiPay customer portal built along these lines.

## Frequently asked questions

### What is idempotency in API integration?

Idempotency means making the same request more than once has the same effect as making it once. In practice, the client sends a unique idempotency key with each logical operation, such as a payment, and reuses it on retries. The server stores the key and the result, so a repeat returns the original result instead of creating a second charge or record.

### How many times should an API request be retried?

There is no single number. For calls a user is waiting on, two or three quick retries over a second or two is usually enough. For background jobs, five or six attempts spread over an hour or more with exponential backoff is common. Only retry network errors, rate limits and server errors, and always use an idempotency key for writes.

### How do I secure a webhook endpoint?

Verify the provider's signature by recomputing the HMAC over the raw request body with your shared secret and comparing it using a constant-time function such as hash_equals. Reject old timestamps if the scheme includes them, deduplicate by event ID, keep the endpoint rate limited, and treat the payload as a prompt to fetch current state from the provider's API.

### What is the difference between a webhook and an API?

An API is something your code calls when it needs data or wants an action performed. A webhook is the reverse: the other system calls a URL on your server when something happens, such as a payment succeeding. Most integrations use both, with webhooks for timely notifications and API calls for fetching the authoritative current state and for reconciliation.

### What is exponential backoff with jitter?

Exponential backoff means waiting progressively longer between retries, for example one second, then two, then four. Jitter adds a small random amount to each wait. Together they stop your system from hammering a struggling service and prevent many clients from retrying at the same moment, which would otherwise cause repeated traffic spikes that prolong an outage.
