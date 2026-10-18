<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!class_exists('Redis')) {
            class_alias(\stdClass::class, 'Redis');
        }

        this->app->bind('redis', function () {
            return new class {
                public function connection($name = null) { return $this; }
                public function connect(...$args) { return true; }
                public function get(...$args) { return null; }
                public function set(...$args) { return true; }
                public function del(...$args) { return true; }
                public function __call($method, $parameters) { return null; }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Config::set('cache.default', 'file');
        Config::set('session.driver', 'file');
        Config::set('queue.default', 'sync');
        Cache::macro('ttl', function (int $base): int {
            $jitter = (int) floor($base * 0.2);
            return $base + random_int(-$jitter, $jitter);
        });
    }
}
