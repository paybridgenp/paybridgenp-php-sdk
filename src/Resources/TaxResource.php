<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

/**
 * Account-level tax configuration applied to invoices.
 */
class TaxResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Get the current tax settings.
     *
     * @return array{enabled: bool, rate_bps: int, registration_number: string|null, label: string|null}
     */
    public function getSettings(): array
    {
        return $this->http->get('/v1/billing/settings/tax');
    }

    /**
     * Update tax settings (enabled, rate, registration number, label).
     *
     * @param array{enabled?: bool, rateBps?: int, registrationNumber?: string|null, label?: string|null} $params
     *
     * @return array{enabled: bool, rate_bps: int, registration_number: string|null, label: string|null}
     */
    public function updateSettings(array $params): array
    {
        return $this->http->patch('/v1/billing/settings/tax', $params);
    }
}
