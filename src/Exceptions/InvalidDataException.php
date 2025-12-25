<?php

namespace Kilogram\Auth\Exceptions;

use LogicException;

/**
 * Exception for invalid arguments passed to methods
 */
class InvalidDataException extends LogicException
{
    /**
     * Create exception for missing hash parameter
     */
    public static function missingHash(): self
    {
        return new self('Hash parameter is required');
    }

    /**
     * Create exception for empty bot token
     */
    public static function emptyToken(): self
    {
        return new self('Bot token cannot be empty');
    }

    /**
     * Create exception for invalid initData format
     */
    public static function invalidInitDataFormat(): self
    {
        return new self('Invalid initData format');
    }

    /**
     * Create exception for missing signature parameter
     */
    public static function missingSignature(): self
    {
        return new self('Signature parameter is required for third-party validation');
    }
}
