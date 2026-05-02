<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

/**
 * Account-level error: the credentials are valid but the account or token is
 * not in good standing. Two common cases (check `getErrorCode()`):
 *
 *   - `account_suspended` (HTTP 403) — merchant account suspended; see
 *     `getSuspension()` for `suspended_at` + `reason`.
 *   - `token_paused` (HTTP 423) — MCP token paused for anomalous activity;
 *     see `getPause()` for `paused_at` + `reason`.
 */
class AccountException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        string $message,
        int $statusCode = 403,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?array $raw = null
    ) {
        parent::__construct($message, $statusCode, 'account_error', $errorCode, $requestId, $raw);
    }

    /**
     * Suspension detail for `account_suspended`.
     *
     * @return array{suspended_at?:string, reason?:?string}|null
     */
    public function getSuspension(): ?array
    {
        $raw = $this->getRaw();
        if (!is_array($raw) || !isset($raw['error']) || !is_array($raw['error'])) return null;
        $error = $raw['error'];
        return isset($error['suspension']) && is_array($error['suspension']) ? $error['suspension'] : null;
    }

    /**
     * Pause detail for `token_paused`.
     *
     * @return array{paused_at?:string, reason?:?string}|null
     */
    public function getPause(): ?array
    {
        $raw = $this->getRaw();
        if (!is_array($raw) || !isset($raw['error']) || !is_array($raw['error'])) return null;
        $error = $raw['error'];
        return isset($error['pause']) && is_array($error['pause']) ? $error['pause'] : null;
    }
}
