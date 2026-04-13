<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class NotFoundException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(string $message = 'Resource not found', ?array $raw = null)
    {
        parent::__construct($message, 404, 'not_found_error', $raw);
    }
}
