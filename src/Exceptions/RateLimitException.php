<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class RateLimitException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(string $message = 'Too many requests', ?array $raw = null)
    {
        parent::__construct($message, 429, 'rate_limit_error', $raw);
    }
}
