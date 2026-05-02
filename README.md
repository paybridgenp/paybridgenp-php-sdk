# PayBridgeNP — PHP SDK

Official PHP SDK for [PayBridgeNP](https://paybridgenp.com) — accept eSewa, Khalti, and ConnectIPS through a single API.

**Requirements:** PHP 7.4+, `ext-curl`, `ext-json`

---

## Installation

```bash
composer require paybridge-np/sdk
```

---

## Quick Start

```php
use PayBridgeNP\PayBridge;

$pb = new PayBridge(['api_key' => 'sk_test_...']);

$session = $pb->checkout->create([
    'amount'     => 5000,          // NPR 50.00 — always in paisa (NPR × 100)
    'return_url' => 'https://myshop.com/thank-you',
    'cancel_url' => 'https://myshop.com/cart',
    'metadata'   => ['order_id' => 'ORD-001'],
]);

header('Location: ' . $session['checkout_url']);
exit;
```

The customer lands on a hosted checkout page, picks their payment method (eSewa / Khalti), pays, and is redirected back to your `return_url` with:

```
https://myshop.com/thank-you?session_id=cs_xxx&status=success&payment_id=pay_xxx
```

---

## Configuration

```php
$pb = new PayBridge([
    'api_key'     => 'sk_live_...',              // required
    'base_url'    => 'https://api.paybridgenp.com', // optional, default shown
    'timeout'     => 30,                         // optional, seconds (default: 30)
    'max_retries' => 2,                          // optional, retries on 5xx (default: 2)
]);
```

Use `sk_test_` keys for sandbox mode — no real money moves. Switch to `sk_live_` for production.

---

## Checkout

### Create a session

```php
$session = $pb->checkout->create([
    'amount'     => 10000,       // NPR 100.00 in paisa — required
    'return_url' => 'https://myshop.com/thank-you', // required
    'cancel_url' => 'https://myshop.com/cart',       // optional
    'provider'   => 'esewa',     // optional — omit to let the customer pick
    'currency'   => 'NPR',       // optional, default: NPR
    'metadata'   => [            // optional — any key/value pairs
        'order_id'       => 'ORD-001',
        'customer_email' => 'ram@example.com',
    ],
]);

// $session['id']           — cs_xxxxxxxxxxxxxxxx
// $session['checkout_url'] — redirect the customer here
// $session['expires_at']   — ISO 8601 timestamp (1 hour from creation)
```

If you pass `provider` upfront, the response also includes `payment_method` with the direct redirect URL or form fields — useful if you want to skip the hosted page entirely.

### Expire a session

Use this when you mint a fresh session for a logical purchase that already had one outstanding (e.g. a customer requesting a new payment link), so the old URL stops being payable immediately rather than waiting for its 30-minute TTL. Idempotent on already-terminal sessions.

```php
$pb->checkout->expire('cs_xxxxxxxxxxxxxxxx');
```

### Laravel example

```php
// routes/web.php
Route::post('/checkout', function (Request $request) {
    $pb = new PayBridge(['api_key' => config('services.paybridge.key')]);

    $session = $pb->checkout->create([
        'amount'     => $request->input('amount'),
        'return_url' => route('checkout.return'),
        'cancel_url' => route('checkout.cancel'),
        'metadata'   => ['order_id' => $request->input('order_id')],
    ]);

    return redirect($session['checkout_url']);
});

Route::get('/checkout/return', function (Request $request) {
    $status    = $request->query('status');      // "success" or "failed"
    $paymentId = $request->query('payment_id');

    if ($status === 'success') {
        // fulfill the order
    }

    return view('checkout.result', compact('status', 'paymentId'));
});
```

---

## Payments

### List payments

```php
$result = $pb->payments->list(['limit' => 20, 'offset' => 0]);

foreach ($result['data'] as $payment) {
    echo $payment['id'];        // pay_xxxxxxxxxxxxxxxx
    echo $payment['amount'];    // paisa
    echo $payment['status'];    // success | failed | pending | ...
    echo $payment['provider'];  // esewa | khalti | connectips
}

// Pagination
$total  = $result['meta']['total'];
$limit  = $result['meta']['limit'];
$offset = $result['meta']['offset'];
```

### Retrieve a payment

```php
$payment = $pb->payments->get('pay_xxxxxxxxxxxxxxxx');

echo $payment['status'];       // success
echo $payment['provider_ref']; // provider's own transaction ID
echo $payment['metadata']['order_id']; // data you passed at checkout
```

---

## Webhooks

Webhooks let PayBridge notify your server when a payment is completed or fails. You register an endpoint URL, and we POST a signed JSON payload to it for every event.

### 1. Register an endpoint

```php
$endpoint = $pb->webhooks->create([
    'url'    => 'https://myshop.com/webhooks/paybridge',
    'events' => ['payment.succeeded', 'payment.failed'],
]);

// Save $endpoint['signing_secret'] somewhere safe (e.g. .env)
// It is shown ONCE and cannot be retrieved again.
echo $endpoint['signing_secret']; // whsec_...
```

### 2. Handle incoming webhooks

Always verify the signature before trusting the payload.

**Plain PHP:**

```php
<?php

require 'vendor/autoload.php';

use PayBridgeNP\PayBridge;
use PayBridgeNP\Exceptions\SignatureVerificationException;

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYBRIDGE_SIGNATURE'] ?? null;
$secret    = getenv('PAYBRIDGE_WEBHOOK_SECRET'); // whsec_...

try {
    $event = PayBridge::webhooks()->constructEvent($payload, $signature, $secret);
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

switch ($event['type']) {
    case 'payment.succeeded':
        $payment = $event['data'];
        $orderId = $payment['metadata']['order_id'] ?? null;
        // fulfill order, send receipt email, etc.
        break;

    case 'payment.failed':
        // notify customer, release reserved stock, etc.
        break;
}

http_response_code(200);
```

**Laravel:**

```php
// routes/api.php
Route::post('/webhooks/paybridge', [WebhookController::class, 'handle']);
```

```php
// app/Http/Controllers/WebhookController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayBridgeNP\PayBridge;
use PayBridgeNP\Exceptions\SignatureVerificationException;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $event = PayBridge::webhooks()->constructEvent(
                $request->getContent(),
                $request->header('X-PayBridge-Signature'),
                config('services.paybridge.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        match ($event['type']) {
            'payment.succeeded' => $this->handleSuccess($event['data']),
            'payment.failed'    => $this->handleFailed($event['data']),
            default             => null,
        };

        return response('OK', 200);
    }

    private function handleSuccess(array $payment): void
    {
        $orderId = $payment['metadata']['order_id'] ?? null;
        // fulfill order...
    }

    private function handleFailed(array $payment): void
    {
        // handle failure...
    }
}
```

> **Important:** Always pass the **raw** request body to `constructEvent()` — do not `json_decode` it first. The HMAC is computed over the raw string.

> **Important:** Disable CSRF verification for your webhook route in Laravel (`VerifyCsrfToken` middleware).

### Manage endpoints

```php
// List all endpoints
$endpoints = $pb->webhooks->list();

// Delete an endpoint
$pb->webhooks->delete('we_xxxxxxxxxxxxxxxx');
```

---

## Webhook Events

| Event | When it fires |
|---|---|
| `payment.succeeded` | Payment verified successfully |
| `payment.failed` | Payment attempted but failed or cancelled |

Every event payload has this shape:

```php
[
    'id'      => 'evt_xxxxxxxxxxxxxxxx',
    'type'    => 'payment.succeeded',
    'created' => 1711234567,   // Unix timestamp
    'data'    => [
        'id'           => 'pay_xxxxxxxxxxxxxxxx',
        'amount'       => 5000,
        'currency'     => 'NPR',
        'status'       => 'success',
        'provider'     => 'esewa',
        'provider_ref' => 'TXN-ABC123',
        'metadata'     => ['order_id' => 'ORD-001'],
        'created_at'   => '2026-03-25T10:00:00.000Z',
    ],
]
```

---

## Error Handling

All API errors throw a subclass of `PayBridgeNP\Exceptions\PayBridgeException`.

```php
use PayBridgeNP\Exceptions\PayBridgeException;
use PayBridgeNP\Exceptions\AuthenticationException;
use PayBridgeNP\Exceptions\InvalidRequestException;
use PayBridgeNP\Exceptions\RateLimitException;
use PayBridgeNP\Exceptions\SignatureVerificationException;

try {
    $session = $pb->checkout->create(['amount' => 5000, 'return_url' => 'https://...']);
} catch (AuthenticationException $e) {
    // Invalid or missing API key
    echo $e->getMessage();      // "Invalid or missing API key"
    echo $e->getStatusCode();   // 401
    echo $e->getErrorCode();    // "authentication_error"
} catch (InvalidRequestException $e) {
    // Bad parameters — check $e->getRaw() for field-level details
    print_r($e->getRaw());
} catch (RateLimitException $e) {
    // Too many requests — back off and retry
    sleep(5);
} catch (PayBridgeException $e) {
    // Catch-all for any other API error
    echo $e->getStatusCode();
    echo $e->getErrorCode();    // "api_error", "not_found_error", etc.
}
```

### Exception reference

| Class | Status | `getErrorCode()` |
|---|---|---|
| `AuthenticationException` | 401 | `authentication_error` |
| `PermissionException` | 403 | `permission_error` |
| `NotFoundException` | 404 | `not_found_error` |
| `InvalidRequestException` | 400 / 422 | `invalid_request_error` |
| `RateLimitException` | 429 | `rate_limit_error` |
| `SignatureVerificationException` | — | `signature_verification_error` |
| `ConnectionException` | — | `connection_error` |
| `PayBridgeException` | any | `api_error` |

---

## Sandbox Testing

Use `sk_test_` API keys to test without real money. The sandbox uses provider test environments:

| Provider | Test credentials |
|---|---|
| eSewa | Merchant code: `EPAYTEST`, secret: `8gBm/:&EnhH.1/q` — pre-configured, no setup needed |
| Khalti | Secret key: `test_secret_key_f59e8b7d18b4499ca40f68195a846e9b` — pre-configured |

In sandbox mode, no provider credentials need to be configured in the dashboard — built-in test credentials are used automatically.

---

## Running the Tests

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT
