<?php

declare(strict_types=1);

namespace PayBridgeNP\Exceptions;

class SignatureVerificationException extends PayBridgeException
{
    public function __construct(string $message = 'Webhook signature verification failed')
    {
        parent::__construct($message, 0, 'signature_verification_error');
    }
}
