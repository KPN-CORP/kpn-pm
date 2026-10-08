<?php

namespace App\Providers;

use App\Services\AppService;
use App\Services\RehireMergeService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Share data with views globally
        View::share('appraisalPeriod', $appService->appraisalPeriod());
        View::share('flowAccess', fn($moduleTransaction) => $appService->checkFlowAccess($moduleTransaction));
        View::share('userRatingAccess', fn() => $appService->checkKpiUnit());

        // Karyawan rehire: pindahkan data id lama ke id baru saat login
        // (form login & SSO dbauth). SSO JWT memanggilnya langsung.
        Event::listen(Login::class, function (Login $event) {
            app(RehireMergeService::class)->mergeOnLogin($event->user->employee_id ?? null);
        });

    }
}
