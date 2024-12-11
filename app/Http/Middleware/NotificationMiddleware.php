<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AppService;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Share notification counts in views
            view()->share('notificationAppraisal', $this->appService->getNotificationCountsAppraisal(Auth::user()->employee_id));
            view()->share('notificationGoal', $this->appService->getNotificationCountsGoal(Auth::user()->employee_id));
        }

        return $next($request);
    }
}
