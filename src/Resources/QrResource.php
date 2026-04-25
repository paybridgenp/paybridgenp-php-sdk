<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class QrResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a Fonepay Direct-QR session. Returns a raw EMV QR string, a
     * base64-encoded PNG image, and a per-session SSE URL the customer's
     * browser can subscribe to for real-time payment events.
     *
     * Premium feature — the merchant must be on the Premium plan.
     *
     * @param array{
     *   amount: int,
     *   currency?: string,
     *   customer: array{
     *     name: string,
     *     email: string,
     *     phone?: string,
     *     address?: array{
     *       line1: string,
     *       city: string,
     *       line2?: string,
     *       state?: string,
     *       postalCode?: string,
     *       country?: string
     *     }
     *   },
     *   metadata?: array<string,mixed>
     * } $params
     *
     * @return array{
     *   id: string,
     *   amount: int,
     *   currency: string,
     *   provider: string,
     *   status: string,
     *   qr_message: string,
     *   qr_image: string,
     *   events_url: string,
     *   expires_at: string
     * }
     */
    public function fonepay(array $params): array
    {
        return $this->http->post('/v1/qr/fonepay', $params);
    }
}
