<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class SubscriptionsResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a subscription.
     *
     * @param array{
     *   customerId: string,
     *   planId: string,
     *   referenceId?: string,
     *   startDate?: string,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/billing/subscriptions', $params);
    }

    /**
     * List subscriptions.
     *
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   status?: 'active'|'past_due'|'paused'|'cancelled'|'completed',
     *   customerId?: string,
     *   planId?: string
     * } $params
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, page: int, limit: int}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'page'       => $params['page']       ?? null,
            'limit'      => $params['limit']      ?? null,
            'status'     => $params['status']      ?? null,
            'customerId' => $params['customerId'] ?? null,
            'planId'     => $params['planId']     ?? null,
        ], fn($v) => $v !== null));

        $path = '/v1/billing/subscriptions' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single subscription by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $subscriptionId): array
    {
        return $this->http->get('/v1/billing/subscriptions/' . rawurlencode($subscriptionId));
    }

    /**
     * Pause a subscription.
     *
     * @param array{pauseReason?: string, resumeAt?: string} $params
     *
     * @return array<string,mixed>
     */
    public function pause(string $subscriptionId, array $params = []): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/pause',
            $params
        );
    }

    /**
     * Resume a paused subscription.
     *
     * @return array<string,mixed>
     */
    public function resume(string $subscriptionId): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/resume',
            []
        );
    }

    /**
     * Cancel a subscription.
     *
     * @param array{cancelReason?: string, atPeriodEnd?: bool} $params
     *
     * @return array<string,mixed>
     */
    public function cancel(string $subscriptionId, array $params = []): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/cancel',
            $params
        );
    }

    /**
     * Schedule a plan change on a subscription.
     *
     * @param array{newPlanId: string, effectiveAt?: string} $params
     *
     * @return array<string,mixed>
     */
    public function changePlan(string $subscriptionId, array $params): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/change-plan',
            $params
        );
    }
}
