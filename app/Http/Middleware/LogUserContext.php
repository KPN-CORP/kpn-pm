<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogUserContext
{
    /**
     * Menempelkan identitas user ke semua log dalam request ini,
     * supaya error di log bisa ditelusuri per user.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            Log::shareContext([
                'user' => [
                    'id'          => $user->id,
                    'employee_id' => $user->employee_id,
                    'fullname'    => $user->name,
                    'email'       => $user->email,
                ],
                'url'    => $request->fullUrl(),
                'method' => $request->method(),
                'ip'     => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
