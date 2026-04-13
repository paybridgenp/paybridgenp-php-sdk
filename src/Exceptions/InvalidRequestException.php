<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class InvalidRequestException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(string $message = 'Invalid request parameters', ?array $raw = null)
    {
        parent::__construct($message, 400, 'invalid_request_error', $raw);
    }
}
