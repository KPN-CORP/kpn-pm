<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationEmployeeController extends Controller
{
    /**
     * Return list of employees for integration.
     *
     * Provides: employee_id, fullname, email, group_company, office_area, manager_l1_id, manager_l2_id
     */
    public function index(Request $request): JsonResponse
    {
        // Cek token dipindah dari closure di routes/api.php ke sini supaya
        // seluruh route bisa di-cache (php artisan route:cache tidak bisa
        // men-serialize closure).
        $token = str_replace('Bearer ', '', (string) $request->header('Authorization'));

        if (! hash_equals((string) config('services.integration.token_ga'), $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $employees = Employee::select(
            'employee_id',
            'fullname',
            'email',
            'group_company',
            'office_area',
            'unit',
            'manager_l1_id',
            'manager_l2_id'
        )->get();

        return response()->json(['data' => $employees], 200);
    }
}
