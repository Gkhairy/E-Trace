<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        // Pagination bergaya E-Trace (tema terang, aktif biru) untuk semua daftar.
        Paginator::defaultView('vendor.pagination.etrace');

        // Paksa HTTPS bila diaktifkan (default: production). Semua URL yang dibuat
        // (asset/route/url) memakai https + cookie sesi ditandai secure. Redirect
        // http->https & header HSTS ditangani middleware ForceHttps.
        if (config('app.force_https')) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }
    }
}
