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
        $employees = Employee::select(
            'employee_id',
            'fullname',
            'email',
            'group_company',
            'office_area',
            'manager_l1_id',
            'manager_l2_id'
        )->get();

        return response()->json(['data' => $employees], 200);
    }
}
