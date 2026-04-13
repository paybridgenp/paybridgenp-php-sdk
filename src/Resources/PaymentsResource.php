<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class PaymentsResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * List payments for the authenticated project.
     *
     * @param array{limit?: int, offset?: int} $params
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
        ], fn($v) => $v !== null));

        $path = '/v1/payments' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single payment by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $paymentId): array
    {
        return $this->http->get('/v1/payments/' . rawurlencode($paymentId));
    }
}
