<?php

namespace App\Providers;

use App\Models\EmployeeAppraisal;
use App\Services\AppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


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
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        view()->share('appraisalPeriod', app(AppService::class)->appraisalPeriod());
    }
}
