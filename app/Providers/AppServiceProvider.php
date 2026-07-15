<?php

namespace App\Providers;

use App\Support\Hashing\DjangoPbkdf2Hasher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Use the Django-compatible hasher as the application default so that
        // imported pbkdf2_sha256 password hashes verify on login, while new
        // passwords are stored with bcrypt.
        config(['hashing.driver' => 'django']);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Hash::extend('django', fn () => new DjangoPbkdf2Hasher());

        Paginator::useBootstrapFive();

        // Rate limiter for audio downloads / transcript scraping. Generous for
        // real listeners, but caps abusive automated fetching per IP.
        RateLimiter::for('downloads', function (Request $request) {
            return Limit::perMinute(40)->by($request->ip());
        });

        // Global soft limit applied to the public site to blunt crawl floods.
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });
    }
}
