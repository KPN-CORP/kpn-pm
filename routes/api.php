<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntegrationEmployeeController;
use App\Http\Controllers\KPIAchievementController;
use Illuminate\Http\Request;

Route::middleware('throttle:30,1')->get('/integration/employees', function (Request $request) {

    $token = str_replace('Bearer ', '', $request->header('Authorization'));

    if ($token !== env('INTEGRATION_API_TOKEN_GA')) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }

    return app(IntegrationEmployeeController::class)->index($request);

});

Route::post('/kpi-achievements', [KPIAchievementController::class, 'store']);