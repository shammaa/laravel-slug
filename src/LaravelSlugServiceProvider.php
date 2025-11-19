<?php

declare(strict_types=1);

namespace Shammaa\LaravelSlug;

use Illuminate\Support\ServiceProvider;
use Shammaa\LaravelSlug\Services\SlugService;

class LaravelSlugServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton('slug', function ($app) {
            return new SlugService();
        });

        $this->app->alias('slug', SlugService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/slug.php' => config_path('slug.php'),
            ], 'slug-config');
        }
    }
}

