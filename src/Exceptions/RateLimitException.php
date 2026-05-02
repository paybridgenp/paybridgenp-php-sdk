<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class RateLimitException extends PayBridgeException
{
    /** @var int|null From the `Retry-After` response header, in seconds. */
    private $retryAfter;

    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message = 'Too many requests',
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null,
        ?int $retryAfter = null
    ) {
        parent::__construct($message, 429, 'rate_limit_error', $errorCode, $requestId, $raw);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
