<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class PlansResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a billing plan.
     *
     * @param array{
     *   name: string,
     *   amount: int,
     *   intervalUnit: 'day'|'week'|'month'|'quarter'|'year',
     *   intervalCount?: int,
     *   currency?: string,
     *   description?: string|null,
     *   gracePeriodDays?: int,
     *   trialDays?: int,
     *   defaultProvider?: string|null,
     *   reminderDaysBeforeDue?: int,
     *   overdueReminderIntervalDays?: int,
     *   overdueAction?: 'keep_active'|'mark_past_due'|'pause'|'cancel',
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * @return array<string,mixed>
     */
    public function create(array $params): array
    {
        return $this->http->post('/v1/billing/plans', $params);
    }

    /**
     * List billing plans.
     *
     * @param array{page?: int, limit?: int, active?: bool} $params
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, page: int, limit: int}
     */
    public function list(array $params = []): array
    {
        $query = http_build_query(array_filter([
            'page'   => $params['page']   ?? null,
            'limit'  => $params['limit']  ?? null,
            'active' => isset($params['active']) ? ($params['active'] ? 'true' : 'false') : null,
        ], fn($v) => $v !== null));

        $path = '/v1/billing/plans' . ($query !== '' ? '?' . $query : '');

        return $this->http->get($path);
    }

    /**
     * Retrieve a single plan by ID.
     *
     * @return array<string,mixed>
     */
    public function get(string $planId): array
    {
        return $this->http->get('/v1/billing/plans/' . rawurlencode($planId));
    }

    /**
     * Update a plan.
     *
     * @param array{
     *   name?: string,
     *   description?: string|null,
     *   active?: bool,
     *   defaultProvider?: string|null,
     *   gracePeriodDays?: int,
     *   reminderDaysBeforeDue?: int,
     *   overdueReminderIntervalDays?: int,
     *   overdueAction?: 'keep_active'|'mark_past_due'|'pause'|'cancel'
     * } $params
     *
     * @return array<string,mixed>
     */
    public function update(string $planId, array $params): array
    {
        return $this->http->patch('/v1/billing/plans/' . rawurlencode($planId), $params);
    }
}
