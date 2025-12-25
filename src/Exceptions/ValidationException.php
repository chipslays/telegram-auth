<?php

namespace Telegram\Auth\Exceptions;

/**
 * Exception thrown when hash validation fails
 */
class ValidationException extends TelegramAuthException
{
    /**
     * Create exception for invalid hash
     */
    public static function invalidHash(): self
    {
        return new self('Invalid hash: authentication failed');
    }

    /**
     * Create exception for expired authentication data
     */
    public static function expired(int $authDate, int $maxAge): self
    {
        return new self(
            sprintf(
                'Authentication data expired. Auth date: %d, max age: %d seconds',
                $authDate,
                $maxAge
            )
        );
    }

    /**
     * Create exception for invalid signature
     */
    public static function invalidSignature(): self
    {
        return new self('Invalid signature: data verification failed');
    }
}
