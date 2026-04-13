<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class PermissionException extends PayBridgeException
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(string $message = 'You do not have permission to perform this action', ?array $raw = null)
    {
        parent::__construct($message, 403, 'permission_error', $raw);
    }
}
