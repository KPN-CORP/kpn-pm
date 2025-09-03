<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Flow;
use App\Models\Assignment;

class FlowAccessMiddleware
{
    public function handle(Request $request, Closure $next, ?string $moduleTransaction = null)
    {
        $user = $request->user();
        $employee = $user->employee; // pastikan relasi employee di model User

        if (!$moduleTransaction) {
            abort(403, 'Access denied. No module transaction provided.');
        }

        // Ambil flow berdasarkan module_transaction
        $flow = Flow::where('module_transaction', $moduleTransaction)->first();

        if (!$flow) {
            abort(403, 'Access denied. Flow not found.');
        }

        // Decode assignments JSON menjadi array id
        $assignmentIds = is_array($flow->assignments)
            ? $flow->assignments
            : json_decode($flow->assignments, true);

        if (!is_array($assignmentIds) || empty($assignmentIds)) {
            abort(403, 'Access denied. No assignments found.');
        }

        // Ambil data assignments dari tabel assignments
        $assignments = Assignment::whereIn('id', $assignmentIds)->get();

        // Cek setiap assignment
        foreach ($assignments as $assignment) {
            if (!empty($assignment->restriction)) {
                $restriction = is_array($assignment->restriction)
                    ? $assignment->restriction
                    : json_decode($assignment->restriction, true);

                if (!is_array($restriction)) {
                    continue;
                }

                $pass = true;
                foreach ($restriction as $field => $allowedValues) {
                    $empValue = $employee->{$field} ?? null;

                    if (!in_array($empValue, $allowedValues, true)) {
                        $pass = false;
                        break;
                    }
                }

                if ($pass) {
                    return $next($request); // Lolos restriction
                }
            }
        }

        abort(403, 'Access denied. Restriction not met.');
    }
}
