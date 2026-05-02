<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

use RuntimeException;

/**
 * Base exception for all PayBridgeNP API errors.
 *
 * Mirrors the API's nested error envelope (v3+):
 *
 *   { "error": { "message": "...", "type": "...", "code": "...", "request_id": "...", ... } }
 *
 * The `type` field drives the subclass hierarchy. Use `instanceof` to branch.
 */
class PayBridgeException extends RuntimeException
{
    /** @var int */
    private $statusCode;

    /** @var string Broad category — matches `error.type` from the API. */
    private $errorType;

    /** @var string|null Specific identifier — matches `error.code` (may be null). */
    private $errorCode;

    /** @var string|null Matches `error.request_id` and the `X-Request-Id` header. */
    private $requestId;

    /** @var array<string,mixed>|null Full parsed JSON body of the error response. */
    private $raw;

    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message,
        int $statusCode,
        string $errorType,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errorType  = $errorType;
        $this->errorCode  = $errorCode;
        $this->requestId  = $requestId;
        $this->raw        = $raw;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Broad error category — `authentication_error`, `account_error`,
     * `permission_error`, `invalid_request_error`, `idempotency_error`,
     * `rate_limit_error`, `api_error`.
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /** Specific identifier (e.g. `api_key_invalid`). May be null. */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /** Same as the `X-Request-Id` response header. Quote in support requests. */
    public function getRequestId(): ?string
    {
        return $this->requestId;
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
            'message'     => $this->getMessage(),
            'error_type'  => $this->errorType,
            'error_code'  => $this->errorCode,
            'status_code' => $this->statusCode,
            'request_id'  => $this->requestId,
            'raw'         => $this->raw,
        ];
    }

    /**
     * Factory — parses the nested error envelope and instantiates the right
     * subclass. Tolerates the legacy flat shape so v2 API responses still
     * work during the transition.
     *
     * @param array<string,mixed>|null $raw
     */
    public static function fromResponse(int $statusCode, ?array $raw, ?string $retryAfterHeader = null): self
    {
        $errorObj = is_array($raw) && isset($raw['error']) && is_array($raw['error']) ? $raw['error'] : null;

        if ($errorObj !== null) {
            $message   = isset($errorObj['message']) && is_string($errorObj['message']) ? $errorObj['message'] : 'HTTP ' . $statusCode;
            $type      = isset($errorObj['type']) && is_string($errorObj['type']) ? $errorObj['type'] : null;
            $code      = isset($errorObj['code']) && is_string($errorObj['code']) ? $errorObj['code'] : null;
            $requestId = isset($errorObj['request_id']) && is_string($errorObj['request_id']) ? $errorObj['request_id'] : null;
        } else {
            // Legacy flat shape: { error: "...", code: "..." }.
            $message   = is_array($raw) && isset($raw['error']) && is_string($raw['error']) ? $raw['error'] : 'HTTP ' . $statusCode;
            $type      = null;
            $code      = is_array($raw) && isset($raw['code']) && is_string($raw['code']) ? $raw['code'] : null;
            $requestId = null;
        }

        switch ($type) {
            case 'authentication_error':
                return new AuthenticationException($message, $code, $requestId, $raw);
            case 'account_error':
                return new AccountException($message, $statusCode, $code, $requestId, $raw);
            case 'permission_error':
                return new PermissionException($message, $code, $requestId, $raw);
            case 'invalid_request_error':
                return new InvalidRequestException($message, $statusCode, $code, $requestId, $raw);
            case 'idempotency_error':
                return new IdempotencyException($message, $code, $requestId, $raw);
            case 'rate_limit_error':
                $retryAfter = $retryAfterHeader !== null ? (int) $retryAfterHeader : null;
                return new RateLimitException($message, $code, $requestId, $raw, $retryAfter);
            case 'api_error':
                return new self($message, $statusCode, 'api_error', $code, $requestId, $raw);
        }

        // No type field — derive from status (legacy flat shape).
        if ($statusCode === 401) return new AuthenticationException($message, $code, $requestId, $raw);
        if ($statusCode === 403) return new PermissionException($message, $code, $requestId, $raw);
        if ($statusCode >= 400 && $statusCode < 500) {
            if ($statusCode === 429) {
                $retryAfter = $retryAfterHeader !== null ? (int) $retryAfterHeader : null;
                return new RateLimitException($message, $code, $requestId, $raw, $retryAfter);
            }
            return new InvalidRequestException($message, $statusCode, $code, $requestId, $raw);
        }
        return new self($message, $statusCode, 'api_error', $code, $requestId, $raw);
    }
}
