<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class CustomersResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a billing customer.
     *
     * @param array{
     *   name: string,
     *   email?: string|null,
     *   phone?: string|null,
     *   externalCustomerId?: string|null,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/billing/customers', $params);
    }

    /**
     * List billing customers.
     *
     * @param array{page?: int, limit?: int, search?: string} $params
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, page: int, limit: int}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'page'   => $params['page']   ?? null,
            'limit'  => $params['limit']  ?? null,
            'search' => $params['search'] ?? null,
        ], fn($v) => $v !== null));

        $path = '/v1/billing/customers' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single customer by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $customerId): array
    {
        return $this->http->get('/v1/billing/customers/' . rawurlencode($customerId));
    }

    /**
     * Update a customer.
     *
     * @param array{
     *   name?: string,
     *   email?: string|null,
     *   phone?: string|null,
     *   externalCustomerId?: string|null,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function update(string $customerId, array $params): array
    {
        return $this->http->patch('/v1/billing/customers/' . rawurlencode($customerId), $params);
    }

    /**
     * Delete a customer. Will return 409 Conflict if the customer has
     * active, paused, or past-due subscriptions.
     *
     * @return array{deleted: bool}
     */
    public function delete(string $customerId): array
    {
        return $this->http->delete('/v1/billing/customers/' . rawurlencode($customerId));
    }
}
