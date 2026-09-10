<?php

namespace App\Http\Middleware;

use App\Services\AppService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class NotificationMiddleware
{
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
    }

    /**
     * Handle an incoming request.
     *
     * Hitungan notifikasi HANYA dipakai di layouts_.shared.left-sidebar.
     * Dulu dihitung eager di sini untuk setiap request — termasuk ratusan
     * request AJAX/JSON (endpoint *-data, check-file, changes-company, dll)
     * yang tidak pernah merender sidebar. Sekarang didaftarkan sebagai view
     * composer, jadi query-nya baru jalan kalau sidebar-nya benar-benar
     * dirender.
     *
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $appService = $this->appService;
            $employeeId = Auth::user()->employee_id;
            $filterYear = $request->filterYear ?? null;

            // Cukup satu pendaftaran. Laravel menormalkan nama view ("/"
            // menjadi "."), jadi layout yang menulis
            // @include('layouts_.shared/left-sidebar') tetap cocok dengan
            // nama bertitik di bawah — mendaftarkan kedua ejaan justru
            // membuat callback-nya jalan dua kali.
            View::composer('layouts_.shared.left-sidebar', function ($view) use ($appService, $employeeId, $filterYear) {
                $view->with([
                    'notificationGoal' => $appService->getNotificationCountsGoal($employeeId, $filterYear),
                    'notificationAppraisal' => $appService->getNotificationCountsAppraisal($employeeId, $filterYear),
                ]);
            });
        }

        return $next($request);
    }
}
