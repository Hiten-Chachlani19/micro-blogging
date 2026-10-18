<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
