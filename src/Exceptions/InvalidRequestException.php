<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class InvalidRequestException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message = 'Invalid request parameters',
        int $statusCode = 400,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null
    ) {
        parent::__construct($message, $statusCode, 'invalid_request_error', $errorCode, $requestId, $raw);
    }
}
