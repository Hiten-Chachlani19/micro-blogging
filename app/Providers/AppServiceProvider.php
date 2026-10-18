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
    $this->app->singleton('redis', function () {
        return new class {
            public function connection($name = null)
            {
                return $this;
            }

            public function client()
            {
                return $this;
            }

            public function connect(...$args)
            {
                return true;
            }

            public function get(...$args)
            {
                return null;
            }

            public function set(...$args)
            {
                return true;
            }

            public function setex(...$args)
            {
                return true;
            }

            public function del(...$args)
            {
                return 0;
            }

            public function exists(...$args)
            {
                return false;
            }

            public function expire(...$args)
            {
                return true;
            }

            public function ping(...$args)
            {
                return 'PONG';
            }

            public function __call($method, $parameters)
            {
                return null;
            }
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
