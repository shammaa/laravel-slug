<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generate(string $string, string $separator = '-', ?string $fallback = null)
 * @method static string generateUnique(string $string, string $table, string $column = 'slug', string $separator = '-', ?int $excludeId = null)
 *
 * @see \Shammaa\LaravelSlug\Services\SlugService
 */
class Slug extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'slug';
    }
}

