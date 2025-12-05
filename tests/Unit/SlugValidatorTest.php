<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shammaa\LaravelSlug\Exceptions\InvalidSlugException;
use Shammaa\LaravelSlug\Services\SlugValidator;

class SlugValidatorTest extends TestCase
{
    public function test_validate_config_success(): void
    {
        $config = [
            'separator' => '-',
            'max_length' => 255,
        ];

        $this->assertTrue(SlugValidator::validateConfig($config));
    }

    public function test_validate_config_throws_exception_when_separator_invalid(): void
    {
        $this->expectException(InvalidSlugException::class);
        SlugValidator::validateConfig(['separator' => '--']);
    }

    public function test_validate_slug_success(): void
    {
        $this->assertTrue(SlugValidator::validateSlug('test-slug'));
    }

    public function test_validate_slug_throws_exception_when_empty(): void
    {
        $this->expectException(InvalidSlugException::class);
        SlugValidator::validateSlug('');
    }
}

