<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class CheckoutResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a new checkout session.
     *
     * `flow` controls the customer experience:
     *  - "hosted" (default) — render the PayBridgeNP picker; `provider`, if set,
     *    is just pre-selected and the customer can still switch.
     *  - "redirect" — skip the picker and 302 the customer straight to the
     *    chosen provider. `provider` is required.
     *
     * `cancel_url` is optional. When omitted, cancellations fall back to
     * `return_url` with `?status=cancelled` appended, and the hosted picker
     * hides its Cancel link.
     *
     * @param array{
     *   amount: int,
     *   return_url: string,
     *   cancel_url?: string,
     *   provider?: string,
     *   flow?: string,
     *   currency?: string,
     *   metadata?: array<string,mixed>,
     *   customer?: array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     address?: array{
     *       line1: string,
     *       line2?: string,
     *       city: string,
     *       state?: string,
     *       postalCode?: string,
     *       country?: string
     *     }
     *   },
     *   collect_address?: bool
     * } $params
     *
     * @return array{
     *   id: string,
     *   checkout_url: string,
     *   flow: string,
     *   provider: ?string,
     *   expires_at: string
     * }
     */
    public function create(array $params): array
    {
        // Map PHP-idiomatic snake_case keys to the camelCase keys the API expects
        $body = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'return_url':       $body['returnUrl']       = $value; break;
                case 'cancel_url':       $body['cancelUrl']       = $value; break;
                case 'collect_address':  $body['collectAddress']  = $value; break;
                default:                 $body[$key]              = $value; break;
            }
        }
        return $this->http->post('/v1/checkout', $body);
    }

    /**
     * Expire a checkout session so it can no longer accept payment.
     *
     * Use this when you mint a fresh checkout session for a logical purchase
     * that already had one outstanding (e.g. a customer requesting a new
     * payment link, your reminder system regenerating expired URLs). Without
     * an explicit expire call, the old URL stays payable until its 30-minute
     * TTL elapses, which can let a customer who reloads the old tab pay
     * twice. Mirrors Stripe's `POST /checkout/sessions/{id}/expire`.
     *
     * Idempotent: calling on an already-terminal session is a no-op that
     * returns the current row state without error.
     *
     * @return array{
     *   id: string,
     *   status: string,
     *   flow: string,
     *   provider: ?string,
     *   expires_at: string
     * }
     */
    public function expire(string $id): array
    {
        return $this->http->post('/v1/checkout/' . rawurlencode($id) . '/expire', []);
    }

    /**
     * Retrieve a checkout session by ID, including its current status, amount,
     * customer, and any collected address. Read-only — sessions are created via
     * `create()`. Hits `GET /v1/sessions/{id}`.
     *
     * Note: this richer read shape uses camelCase keys (`customerName`,
     * `expiresAt`, …), unlike the snake_case `create()` response.
     *
     * @return array<string,mixed>
     */
    public function get(string $id): array
    {
        return $this->http->get('/v1/sessions/' . rawurlencode($id));
    }

    /**
     * List checkout sessions for the authenticated project, newest first.
     * Optionally filter by `status` and page with `limit`/`offset`.
     *
     * @param array{limit?: int, offset?: int, status?: string} $params
     *
     * @return array{
     *   data: array<int, array<string,mixed>>,
     *   meta: array{total: int, limit: int, offset: int}
     * }
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'limit'  => $params['limit']  ?? null,
            'offset' => $params['offset'] ?? null,
            'status' => $params['status'] ?? null,
        ], fn($v) => $v !== null));

        $path = '/v1/sessions' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }
}
