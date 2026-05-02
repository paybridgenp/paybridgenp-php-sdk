<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

/**
 * A concurrent request with the same Idempotency-Key is still in flight.
 * Wait briefly and retry, or use a different key.
 */
class IdempotencyException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message = 'Concurrent request with same Idempotency-Key',
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null
    ) {
        parent::__construct($message, 409, 'idempotency_error', $errorCode, $requestId, $raw);
    }
}
