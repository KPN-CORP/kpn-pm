<?php

namespace App\Providers;

use App\Services\AppService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // You could bind services here if needed.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AppService $appService): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Share data with views globally.
        // appraisalPeriod dulu di-share eager di sini, jadi setiap request —
        // termasuk response JSON yang tidak merender view sama sekali —
        // menjalankan query ke tabel schedules. Sebagai composer, query-nya
        // baru jalan kalau ada view yang benar-benar dirender (dan hasilnya
        // di-memoize di AppService untuk sisa request).
        View::composer('*', function ($view) use ($appService) {
            $view->with('appraisalPeriod', $appService->appraisalPeriod());
        });
        View::share('flowAccess', fn($moduleTransaction) => $appService->checkFlowAccess($moduleTransaction));
        View::share('userRatingAccess', fn() => $appService->checkKpiUnit());

    }
}
