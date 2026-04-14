<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class RefundsResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a refund for a successful payment.
     *
     * @param array{
     *   paymentId: string,
     *   amount: int,
     *   reason: string,
     *   notes?: string,
     *   mobileNumber?: string
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/refunds', $params);
    }

    /**
     * List refunds for the authenticated project.
     *
     * @param array{paymentId?: string, limit?: int, offset?: int} $params
     *
     * @return array{
     *   data: array<int, array<string,mixed>>,
     *   meta: array{total: int, limit: int, offset: int}
     * }
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'paymentId' => $params['paymentId'] ?? null,
            'limit'     => $params['limit']     ?? null,
            'offset'    => $params['offset']    ?? null,
        ], fn($v) => $v !== null));

        $path = '/v1/refunds' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single refund by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $refundId): array
    {
        return $this->http->get('/v1/refunds/' . rawurlencode($refundId));
    }
}
