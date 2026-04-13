<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;
use PayBridgeNP\Exceptions\SignatureVerificationException;

class WebhooksResource
{
    /** @var HttpClient|null */
    private $http;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http;
    }

    /**
     * Create a new webhook endpoint.
     *
     * @param array{url: string, events?: string[]} $params
     *
     * @return array<string,mixed>  Includes a one-time `signing_secret` — save it immediately.
     */
    public function create(array $params): array
    {
        $this->requireHttp();
        return $this->http->post('/v1/webhooks', $params);
    }

    /**
     * List all webhook endpoints for the project.
     *
     * @return array{data: array<int, array<string,mixed>>}
     */
    public function list(): array
    {
        $this->requireHttp();
        return $this->http->get('/v1/webhooks');
    }

    /**
     * Delete a webhook endpoint.
     *
     * @return array{deleted: bool, id: string}
     */
    public function delete(string $endpointId): array
    {
        $this->requireHttp();
        return $this->http->delete('/v1/webhooks/' . rawurlencode($endpointId));
    }

    /**
     * Verify a webhook signature and return the parsed event payload.
     *
     * Pass the RAW request body string — do NOT json_decode it first.
     *
     * Example (plain PHP):
     *   $event = PayBridge::webhooks()->constructEvent(
     *       file_get_contents('php://input'),
     *       $_SERVER['HTTP_X_PAYBRIDGE_SIGNATURE'],
     *       'whsec_...'
     *   );
     *
     * Example (Laravel):
     *   $event = PayBridge::webhooks()->constructEvent(
     *       $request->getContent(),
     *       $request->header('X-PayBridge-Signature'),
     *       config('services.paybridge.webhook_secret')
     *   );
     *
     * @param string      $payload   Raw request body
     * @param string|null $signature Value of the X-PayBridge-Signature header
     * @param string      $secret    Your webhook signing secret (whsec_...)
     *
     * @return array<string,mixed>  The parsed event: ['id', 'type', 'created', 'data']
     *
     * @throws SignatureVerificationException
     */
    public function constructEvent(string $payload, ?string $signature, string $secret): array
    {
        if ($signature === null || $signature === '') {
            throw new SignatureVerificationException('Missing X-PayBridge-Signature header');
        }

        // Parse "t=<timestamp>,v1=<hex>" format
        $parts = [];
        foreach (explode(',', $signature) as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            throw new SignatureVerificationException('Malformed X-PayBridge-Signature header');
        }

        $timestamp = (int) $parts['t'];
        $v1        = $parts['v1'];

        // Replay attack protection: reject if timestamp is more than 5 minutes old
        $now = time();
        if (abs($now - $timestamp) > 300) {
            throw new SignatureVerificationException('Timestamp too old — possible replay attack');
        }

        // Compute expected HMAC-SHA256
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        // Timing-safe comparison
        if (!hash_equals($expected, $v1)) {
            throw new SignatureVerificationException('Webhook signature verification failed');
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new SignatureVerificationException('Invalid webhook payload — could not decode JSON');
        }

        return $event;
    }

    private function requireHttp(): void
    {
        if ($this->http === null) {
            throw new \RuntimeException(
                'This WebhooksResource has no HTTP client. ' .
                'Use $pb->webhooks->create() on a PayBridge instance, or ' .
                'PayBridge::webhooks()->constructEvent() for signature verification only.'
            );
        }
    }
}
