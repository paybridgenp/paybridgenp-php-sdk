<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class ConnectionException extends PayBridgeException
{
    public function __construct(string $message = 'Network or connection error')
    {
        parent::__construct($message, 0, 'connection_error');
    }
}
