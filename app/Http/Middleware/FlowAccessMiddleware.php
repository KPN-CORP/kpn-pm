<?php

namespace App\Http\Middleware;

use App\Services\AppService;
use Closure;
use Illuminate\Http\Request;

/**
 * flow_access:<Module Name>
 *
 * Aturan aksesnya sama dengan menu di sidebar ($flowAccess(...)), yaitu
 * AppService::checkFlowAccess. Untuk "Propose 360", assignment menentukan
 * karyawan yang perlu di-360 (target), bukan siapa yang boleh membuka menu;
 * lihat App\Support\Propose360Assignment::canAccess.
 */
class FlowAccessMiddleware
{
    public function handle(Request $request, Closure $next, ?string $moduleTransaction = null)
    {
        if (!$moduleTransaction) {
            abort(403, 'Access denied. No module transaction provided.');
        }

        if (!app(AppService::class)->checkFlowAccess($moduleTransaction)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
