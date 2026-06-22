<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

/**
 * Reusable hosted payment pages. Mirrors the public `/v1/payment-links` routes
 * (all require an API key with the `links:read` / `links:write` scope).
 *
 * Note: payment-link responses use camelCase keys (`customerName`, `expiresAt`,
 * …), matching the live API.
 */
class PaymentLinksResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a payment link. Provide either a fixed `amount` (paisa), or
     * `minAmount`/`maxAmount` bounds for a customer-entered amount.
     *
     * @param array<string,mixed> $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/payment-links', $params);
    }

    /**
     * List payment links for the project, newest first.
     *
     * @param array{limit?: int, offset?: int, active?: bool} $params
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
            // API expects the literal strings "true"/"false", not PHP's 1/0.
            'active' => isset($params['active']) ? ($params['active'] ? 'true' : 'false') : null,
        ], fn($v) => $v !== null));

        $path = '/v1/payment-links' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single link by ID, including aggregated view/conversion stats.
     *
     * @return array<string,mixed>
     */
    public function get(string $id): array
    {
        return $this->http->get('/v1/payment-links/' . rawurlencode($id));
    }

    /**
     * Update a link's editable fields. Only the keys you pass are changed.
     *
     * @param array<string,mixed> $params
     *
     * @return array<string,mixed>
     */
    public function update(string $id, array $params): array
    {
        return $this->http->patch('/v1/payment-links/' . rawurlencode($id), $params);
    }

    /**
     * Cancel (deactivate) a link so it can no longer accept payments, while
     * keeping it and its history for your records. The recommended way to
     * retire a link that has already been used.
     *
     * @return array<string,mixed>
     */
    public function cancel(string $id): array
    {
        return $this->http->post('/v1/payment-links/' . rawurlencode($id) . '/cancel', []);
    }

    /**
     * Permanently delete a link. Only allowed when the link has never been used
     * — otherwise the API returns 422 and you should `cancel()` it instead.
     *
     * @return array{deleted: bool, id: string}
     */
    public function delete(string $id): array
    {
        return $this->http->delete('/v1/payment-links/' . rawurlencode($id));
    }
}
