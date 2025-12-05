<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Services;

use Shammaa\LaravelSlug\Exceptions\InvalidSlugException;

class SlugValidator
{
    /**
     * Validate slug configuration.
     *
     * @param array $config The configuration array
     * @param bool $throwException Whether to throw exception on invalid config
     * @return bool
     * @throws InvalidSlugException
     */
    public static function validateConfig(array $config, bool $throwException = true): bool
    {
        // Validate separator
        if (isset($config['separator']) && strlen($config['separator']) > 1) {
            if ($throwException) {
                throw new InvalidSlugException("Separator must be a single character");
            }
            return false;
        }

        // Validate max_length
        if (isset($config['max_length']) && $config['max_length'] < 1) {
            if ($throwException) {
                throw new InvalidSlugException("max_length must be greater than 0");
            }
            return false;
        }

        return true;
    }

    /**
     * Validate generated slug.
     *
     * @param string $slug The slug to validate
     * @param bool $throwException Whether to throw exception on invalid slug
     * @return bool
     * @throws InvalidSlugException
     */
    public static function validateSlug(string $slug, bool $throwException = true): bool
    {
        if (empty($slug)) {
            if ($throwException) {
                throw new InvalidSlugException("Slug cannot be empty");
            }
            return false;
        }

        if (strlen($slug) > 255) {
            if ($throwException) {
                throw new InvalidSlugException("Slug must be 255 characters or less");
            }
            return false;
        }

        return true;
    }
}

