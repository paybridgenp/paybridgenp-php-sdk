<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

use RuntimeException;

class PayBridgeException extends RuntimeException
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $errorCode;

    /** @var array<string,mixed>|null */
    private $raw;

    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message,
        int $statusCode,
        string $errorCode,
        ?array $raw = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode  = $errorCode;
        $this->raw        = $raw;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getRaw(): ?array
    {
        return $this->raw;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'message'    => $this->getMessage(),
            'error_code' => $this->errorCode,
            'status_code' => $this->statusCode,
            'raw'        => $this->raw,
        ];
    }

    /**
     * Factory — picks the correct subclass based on HTTP status code.
     *
     * @param array<string,mixed>|null $raw
     */
    public static function fromResponse(string $message, int $statusCode, ?array $raw): self
    {
        switch ($statusCode) {
            case 401:
                return new AuthenticationException($message, $raw);
            case 403:
                return new PermissionException($message, $raw);
            case 404:
                return new NotFoundException($message, $raw);
            case 400:
            case 422:
                return new InvalidRequestException($message, $raw);
            case 429:
                return new RateLimitException($message, $raw);
            default:
                return new self($message, $statusCode, 'api_error', $raw);
        }
    }
}
