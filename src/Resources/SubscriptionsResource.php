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
     *   trialDays?: int,
     *   trialEndsAt?: string,
     *   quantity?: int,
     *   billingAnchorDay?: int|null,
     *   metadata?: array<string,mixed>|null
     * } $params
     *
     * `trialEndsAt` (ISO 8601) wins over `trialDays` (0-365) wins over the
     * plan default. Both omitted = use plan.trialDays.
     * `quantity` is the per-seat multiplier (default 1, only for per_unit plans).
     * `billingAnchorDay` (1-28) pins the period-end day for month/quarter/year intervals.
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
     * Change a subscription's plan. Pass prorationBehavior = 'create_prorations'
     * to apply immediately and generate a proration invoice for the difference.
     * Default ('none') schedules the change for the next billing cycle.
     *
     * @param array{
     *   newPlanId: string,
     *   effectiveAt?: string,
     *   prorationBehavior?: 'none'|'create_prorations'
     * } $params
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

    /**
     * Preview the proration credit/debit for a mid-period plan change.
     * Returns the calculated amounts without committing any changes.
     *
     * @return array{creditAmount: int, debitAmount: int, netAmount: int, currency: string, periodStart: string, periodEnd: string, currentPlan: array, newPlan: array}
     */
    public function previewProration(string $subscriptionId, string $newPlanId): array
    {
        $query = http_build_query(['newPlanId' => $newPlanId]);
        return $this->http->get(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/preview-proration?' . $query
        );
    }

    /**
     * End a subscription's trial immediately. Generates the first paid
     * invoice and emails it to the customer. Fires
     * `subscription.trial_ended` webhook. Idempotent — subsequent calls
     * return 409 `trial_not_active`.
     *
     * @return array{subscription: array<string,mixed>, invoice: array<string,mixed>}
     */
    public function endTrial(string $subscriptionId): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/end-trial',
            []
        );
    }

    /**
     * Push a subscription's trial end into the future. Only valid while the
     * trial is still active. Re-arms the 3-day-before reminder. Fires
     * `subscription.trial_extended` webhook.
     *
     * @param array{trialEndsAt: string} $params ISO 8601, must be strictly after current trial end.
     *
     * @return array<string,mixed>
     */
    public function extendTrial(string $subscriptionId, array $params): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/extend-trial',
            $params
        );
    }

    /**
     * Attach a coupon or promotion code to a subscription (Phase 2). Takes
     * effect on the next invoice. Deactivates any prior active discount on
     * the same sub. Pass either couponId or promotionCode.
     *
     * @param array{couponId?: string, promotionCode?: string} $params
     *
     * @return array<string,mixed>
     */
    public function applyCoupon(string $subscriptionId, array $params): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/apply-coupon',
            $params
        );
    }

    /**
     * Remove the currently active discount from a subscription. Future
     * invoices are billed without discount.
     *
     * @return array<string,mixed>
     */
    public function removeDiscount(string $subscriptionId): array
    {
        return $this->http->delete(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/discount'
        );
    }

    // ── Usage (metered billing) ───────────────────────────────────────────────

    public function reportUsage(string $subscriptionId, array $params): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/usage',
            $params
        );
    }

    public function getUsageSummary(string $subscriptionId): array
    {
        return $this->http->get(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/usage'
        );
    }

    public function listUsageRecords(string $subscriptionId, int $limit = 50): array
    {
        return $this->http->get(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/usage/records',
            ['limit' => $limit]
        );
    }

    // ── Pending Invoice Items ─────────────────────────────────────────────────

    public function listInvoiceItems(string $subscriptionId): array
    {
        return $this->http->get(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/invoice-items'
        );
    }

    public function createInvoiceItem(string $subscriptionId, array $params): array
    {
        return $this->http->post(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/invoice-items',
            $params
        );
    }

    public function deleteInvoiceItem(string $subscriptionId, string $itemId): array
    {
        return $this->http->delete(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/invoice-items/' . rawurlencode($itemId)
        );
    }

    /**
     * Update the per-seat quantity on an active per_unit subscription.
     *
     * @param array{quantity: int} $params
     *
     * @return array<string,mixed>
     */
    public function updateQuantity(string $subscriptionId, array $params): array
    {
        return $this->http->patch(
            '/v1/billing/subscriptions/' . rawurlencode($subscriptionId) . '/quantity',
            $params
        );
    }
}
