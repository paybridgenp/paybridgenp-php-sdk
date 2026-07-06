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

    /**
     * Mint a Fonepay Direct-QR to pay this invoice. The customer scans it (in
     * your own UI / at a counter) and on success the invoice is marked paid and
     * the subscription activates (incomplete->active) -- the same outcome as the
     * hosted bill page. Returns a normal Direct-QR session (use its events_url
     * SSE stream + qr->refresh()).
     *
     * Premium feature; requires the `billing:write` scope and Fonepay configured.
     *
     * @return array{
     *   id: string, invoice_id: string, amount: int, currency: string,
     *   provider: string, status: string, qr_message: string, qr_image: string,
     *   events_url: string, expires_at: string
     * }
     */
    public function qr(string $invoiceId): array
    {
        return $this->http->post('/v1/billing/invoices/' . rawurlencode($invoiceId) . '/qr', []);
    }
}
