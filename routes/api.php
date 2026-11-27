<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntegrationEmployeeController;
use App\Http\Controllers\KPIAchievementController;

Route::middleware('throttle:30,1')
    ->get('/integration/employees', [IntegrationEmployeeController::class, 'index']);

Route::post('/kpi-achievements', [KPIAchievementController::class, 'store']);
