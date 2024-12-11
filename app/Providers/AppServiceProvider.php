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
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Share data with views globally
        View::share('appraisalPeriod', $appService->appraisalPeriod());
    }
}
