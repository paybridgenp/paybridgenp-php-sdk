<?php

declare(strict_types=1);

namespace PayBridgeNP;

use PayBridgeNP\Exceptions\ConnectionException;
use PayBridgeNP\Exceptions\PayBridgeException;

/**
 * cURL-based HTTP client with automatic retry and exponential backoff.
 */
class HttpClient
{
    private const DEFAULT_BASE_URL   = 'https://api.paybridgenp.com';
    private const DEFAULT_TIMEOUT    = 30;
    private const DEFAULT_MAX_RETRIES = 2;
    private const INITIAL_BACKOFF_MS  = 500;
    private const RETRY_STATUSES      = [500, 502, 503, 504];
    private const SDK_VERSION         = '0.1.0';

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $apiKey;

    /** @var int */
    private $timeout;

    /** @var int */
    private $maxRetries;

    /**
     * @param array{api_key:string, base_url?:string, timeout?:int, max_retries?:int} $config
     */
    public function __construct(array $config)
    {
        $this->apiKey     = $config['api_key'];
        $this->baseUrl    = rtrim($config['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout    = $config['timeout'] ?? self::DEFAULT_TIMEOUT;
        $this->maxRetries = $config['max_retries'] ?? self::DEFAULT_MAX_RETRIES;
    }

    /**
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     * @throws PayBridgeException
     */
    public function request(string $method, string $path, ?array $body = null): array
    {
        $url     = $this->baseUrl . $path;
        $attempt = 0;

        while (true) {
            $attempt++;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: PayBridgeNP-PHP-SDK/' . self::SDK_VERSION,
                ],
                CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            // Capture response headers to read Retry-After
            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($header);
            });

            $responseBody = curl_exec($ch);
            $curlError    = curl_error($ch);
            $statusCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // cURL-level failure (network, DNS, timeout)
            if ($responseBody === false) {
                if ($attempt > $this->maxRetries) {
                    throw new ConnectionException('Connection error: ' . $curlError);
                }
                $this->sleep($this->backoffMs($attempt));
                continue;
            }

            $decoded = json_decode((string) $responseBody, true);
            $data    = is_array($decoded) ? $decoded : [];

            // Success
            if ($statusCode >= 200 && $statusCode < 300) {
                return $data;
            }

            // Retryable server errors
            if (in_array($statusCode, self::RETRY_STATUSES, true) && $attempt <= $this->maxRetries) {
                $retryAfter = isset($responseHeaders['retry-after'])
                    ? (int) $responseHeaders['retry-after'] * 1000
                    : $this->backoffMs($attempt);
                $this->sleep($retryAfter);
                continue;
            }

            // Non-retryable error — throw typed exception
            $message = isset($data['error']) && is_string($data['error'])
                ? $data['error']
                : 'HTTP ' . $statusCode;

            throw PayBridgeException::fromResponse($message, $statusCode, $data ?: null);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public function patch(string $path, array $body): array
    {
        return $this->request('PATCH', $path, $body);
    }

    /**
     * @return array<string,mixed>
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function backoffMs(int $attempt): int
    {
        return (int) (self::INITIAL_BACKOFF_MS * (2 ** ($attempt - 1)) + mt_rand(0, 100));
    }

    private function sleep(int $ms): void
    {
        usleep($ms * 1000);
    }
}
