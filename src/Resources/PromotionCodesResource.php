<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class PromotionCodesResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a customer-facing promotion code that redeems a coupon.
     *
     * @param array{
     *   couponId: string,
     *   code: string,
     *   maxRedemptions?: int,
     *   expiresAt?: string,
     *   firstTimeTransaction?: bool,
     *   minimumAmount?: int,
     *   customerIds?: array<int,string>,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/billing/promotion-codes', $params);
    }

    /**
     * @param array{couponId?: string, active?: bool, limit?: int} $params
     *
     * @return array{data: array<int,array<string,mixed>>}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'couponId' => $params['couponId'] ?? null,
            'active'   => isset($params['active']) ? ($params['active'] ? 'true' : 'false') : null,
            'limit'    => $params['limit'] ?? null,
        ], fn($v) => $v !== null));
        $path = '/v1/billing/promotion-codes' . ($query !== '' ? '?' . $query : '');
        return $this->http->get($path);
    }

    /** @return array<string,mixed> */
    public function get(string $promotionCodeId): array
    {
        return $this->http->get('/v1/billing/promotion-codes/' . rawurlencode($promotionCodeId));
    }

    /**
     * Deactivate. Existing redemptions remain valid.
     *
     * @return array<string,mixed>
     */
    public function deactivate(string $promotionCodeId): array
    {
        return $this->http->patch(
            '/v1/billing/promotion-codes/' . rawurlencode($promotionCodeId),
            ['active' => false]
        );
    }

    /**
     * Validate a code and preview the discount. Read-only — does NOT redeem.
     *
     * @param array{code: string, customerId?: string, planId?: string, amount?: int} $params
     *
     * @return array<string,mixed>
     */
    public function validate(array $params): array
    {
        return $this->http->post('/v1/billing/promotion-codes/validate', $params);
    }
}
