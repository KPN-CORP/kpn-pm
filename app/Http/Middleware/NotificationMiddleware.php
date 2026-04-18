<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AppService;
use App\Services\KPIService;

class NotificationMiddleware
{
    protected $appService;
    protected $kpiService;

    public function __construct(AppService $appService, KPIService $kpiService)
    {
        $this->appService = $appService;
        $this->kpiService = $kpiService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $filterYear = $request->filterYear ?? null;
        
        if (Auth::check()) {
            // Share notification counts in views
            view()->share('notificationAppraisal', $this->appService->getNotificationCountsAppraisal(Auth::user()->employee_id, $filterYear));
            view()->share('notificationGoal', $this->appService->getNotificationCountsGoal(Auth::user()->employee_id, $filterYear));
            // view()->share('notificationProposed360', $this->appService->getNotificationCountsAppraisal(Auth::user()->employee_id, $filterYear));
        }

        return $next($request);
    }
}
