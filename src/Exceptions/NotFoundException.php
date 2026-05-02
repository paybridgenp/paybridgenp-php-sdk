<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

/**
 * Pre-3.0 alias for `InvalidRequestException` with status 404. Stripe-style
 * envelope categorises 404 as `invalid_request_error`. Kept as a subclass so
 * callers using `catch (NotFoundException $e)` keep working.
 *
 * @deprecated since 3.0 — `instanceof InvalidRequestException` + checking
 *   `$e->getStatusCode() === 404` is the new way.
 */
class NotFoundException extends InvalidRequestException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message = 'Resource not found',
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null
    ) {
        parent::__construct($message, 404, $errorCode, $requestId, $raw);
    }
}
