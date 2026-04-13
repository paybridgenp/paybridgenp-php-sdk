<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class InvoicesResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * List invoices.
     *
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   status?: 'draft'|'open'|'paid'|'overdue'|'void'|'uncollectible',
     *   customerId?: string,
     *   subscriptionId?: string,
     *   search?: string
     * } $params
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, page: int, limit: int}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'page'           => $params['page']           ?? null,
            'limit'          => $params['limit']          ?? null,
            'status'         => $params['status']         ?? null,
            'customerId'     => $params['customerId']     ?? null,
            'subscriptionId' => $params['subscriptionId'] ?? null,
            'search'         => $params['search']         ?? null,
        ], fn($v) => $v !== null));

        $path = '/v1/billing/invoices' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single invoice by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $invoiceId): array
    {
        return $this->http->get('/v1/billing/invoices/' . rawurlencode($invoiceId));
    }
}
