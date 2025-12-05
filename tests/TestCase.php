<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Shammaa\LaravelSlug\LaravelSlugServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('cache.default', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSlugServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
    }
}

