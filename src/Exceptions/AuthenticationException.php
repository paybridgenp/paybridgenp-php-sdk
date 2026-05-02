<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class AuthenticationException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message = 'Invalid or missing API key',
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null
    ) {
        parent::__construct($message, 401, 'authentication_error', $errorCode, $requestId, $raw);
    }
}
