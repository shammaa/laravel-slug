<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Exceptions;

use InvalidArgumentException;

class InvalidSlugException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message, ?string $value = null)
    {
        if ($value) {
            $message = "Invalid slug for '{$value}': {$message}";
        }
        
        parent::__construct($message);
    }
}

