<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class CouponsResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a reusable discount coupon. Discount params (type + percent/amount)
     * are immutable post-creation — replace by deactivating and creating a new one.
     *
     * @param array{
     *   code: string,
     *   name: string,
     *   discountType: 'percent'|'amount',
     *   duration: 'once'|'repeating'|'forever',
     *   percentOff?: int,
     *   amountOff?: int,
     *   currency?: string,
     *   durationInCycles?: int,
     *   maxRedemptions?: int,
     *   redeemBy?: string,
     *   appliesToPlanIds?: array<int,string>,
     *   projectIds?: array<int,string>,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/billing/coupons', $params);
    }

    /**
     * @param array{active?: bool, limit?: int} $params
     *
     * @return array{data: array<int,array<string,mixed>>}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'active' => isset($params['active']) ? ($params['active'] ? 'true' : 'false') : null,
            'limit'  => $params['limit'] ?? null,
        ], fn($v) => $v !== null));
        $path = '/v1/billing/coupons' . ($query !== '' ? '?' . $query : '');
        return $this->http->get($path);
    }

    /** @return array<string,mixed> */
    public function get(string $couponId): array
    {
        return $this->http->get('/v1/billing/coupons/' . rawurlencode($couponId));
    }

    /**
     * Deactivate a coupon (soft-delete).
     *
     * @return array<string,mixed>
     */
    public function deactivate(string $couponId): array
    {
        return $this->http->delete('/v1/billing/coupons/' . rawurlencode($couponId));
    }
}
