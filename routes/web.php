<?php

use App\Http\Controllers\Admin\OnBehalfController as AdminOnBehalfController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SendbackController as AdminSendbackController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportExcelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SendbackController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyAppraisalController;
use App\Http\Controllers\MyGoalController;
use App\Http\Controllers\TeamGoalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('goals');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'role:superadmin'])->name('dashboard');

Route::get('dbauth', [SsoController::class, 'dbauth']);

Route::get('fetch-employees', [EmployeeController::class, 'fetchAndStoreEmployees']);
Route::get('updmenu-employees', [EmployeeController::class, 'updateEmployeeAccessMenu']);
Route::get('daily-schedules', [ScheduleController::class, 'reminderDailySchedules']);

Route::get('/test-email', function () {
    $messages = '<p>This is a test message with <strong>bold</strong> text.</p>';
    $name = 'John Doe';

    return view('email.reminderschedule', compact('messages', 'name'));
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
                
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                    ->name('password.request');
                    
    Route::get('reset-password-email', [PasswordResetLinkController::class, 'selfResetView']);
    
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                    ->name('password.email');    
});


Route::middleware('auth')->group(function () {

    Route::get('reset-self', [PasswordResetLinkController::class, 'selfReset'])
                ->name('password.reset.self');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'task'])->name('tasks');

    // My Goals
    Route::get('/goals', [MyGoalController::class, 'index'])->name('goals');
    Route::get('/goals/detail/{id}', [MyGoalController::class, 'show'])->name('goals.detail');
    Route::get('/goals/form/{id}', [MyGoalController::class, 'create'])->name('goals.form');
    Route::post('/goals/submit', [MyGoalController::class, 'store'])->name('goals.submit');
    Route::get('/goals/edit/{id}', [MyGoalController::class, 'edit'])->name('goals.edit');
    Route::post('/goals/update', [MyGoalController::class, 'update'])->name('goals.update');

    // Team Goals
    Route::get('/team-goals', [TeamGoalController::class, 'index'])->name('team-goals');
    Route::get('/team-goals/detail/{id}', [TeamGoalController::class, 'show'])->name('team-goals.detail');
    Route::get('/team-goals/form/{id}', [TeamGoalController::class, 'create'])->name('team-goals.form');
    Route::post('/team-goals/submit', [TeamGoalController::class, 'store'])->name('team-goals.submit');
    Route::get('/team-goals/edit/{id}', [TeamGoalController::class, 'edit'])->name('team-goals.edit');
    Route::get('/team-goals/approval/{id}', [TeamGoalController::class, 'approval'])->name('team-goals.approval');
    Route::get('/get-tooltip-content', [TeamGoalController::class, 'getTooltipContent']);
    Route::get('/units-of-measurement', [TeamGoalController::class, 'unitOfMeasurement']);

    // My Appraisals
    Route::get('/appraisals', [MyAppraisalController::class, 'index'])->name('appraisals');
    Route::get('/appraisals-360', [MyAppraisalController::class, 'index'])->name('appraisals-360');
    
    Route::get('/appraisals/create/{id}', [MyAppraisalController::class, 'create'])->name('form.appraisal');
    Route::get('/appraisals/edit/{id}', [MyAppraisalController::class, 'edit'])->name('edit.appraisal');
    Route::post('/appraisals', [MyAppraisalController::class, 'store'])->name('form.submit');
    
    // Approval
    Route::post('/approval/goal', [ApprovalController::class, 'store'])->name('approval.goal');

    // Sendback
    Route::post('/sendback/goal', [SendbackController::class, 'store'])->name('sendback.goal');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    Route::get('export/employees', [ExportExcelController::class, 'export'])->name('export.employee');
    Route::get('/get-report-content/{reportType}', [ReportController::class, 'getReportContent']);
    Route::get('/export/report-emp', [ExportExcelController::class, 'exportreportemp'])->name('export.reportemp');

    Route::post('/export', [ExportExcelController::class, 'export'])->name('export');
    Route::post('/admin-export', [ExportExcelController::class, 'exportAdmin'])->name('admin.export');
    Route::post('/notInitiatedReport', [ExportExcelController::class, 'notInitiated'])->name('team-goals.notInitiated');
    Route::post('/initiatedReport', [ExportExcelController::class, 'initiated'])->name('team-goals.initiated');
    // Route::get('/export/goals', [ReportController::class, 'exportGoal'])->name('export.goal');
    Route::post('/get-report-content', [ReportController::class, 'getReportContent'])->name('reports.content');
    
    Route::get('/changes-group-company', [ReportController::class, 'changesGroupCompany']);
    Route::get('/changes-company', [ReportController::class, 'changesCompany']);
    
    // Authentication
    
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('{first}/{second}', [HomeController::class, 'secondLevel'])->name('second');

    Route::get('/guides', [GuideController::class, 'index'])->name('guides');
    Route::post('/guides', [GuideController::class, 'store'])->name('upload.guide');
    Route::delete('/guides-delete/{id}', [GuideController::class, 'destroy'])->name('delete.guide');
    
    // ============================ Administrator ===================================

    Route::middleware(['permission:viewschedule'])->group(function () {
        // Schedule
        Route::get('/schedules', [ScheduleController::class, 'schedule'])->name('schedules');
        Route::get('/schedules-form', [ScheduleController::class, 'form'])->name('schedules.form');
        Route::post('/schedule-save', [ScheduleController::class, 'save'])->name('save-schedule');
        Route::get('/schedule/edit/{id}', [ScheduleController::class, 'edit'])->name('edit-schedule');
        Route::post('/schedule', [ScheduleController::class, 'update'])->name('update-schedule');
        Route::delete('/schedule/{id}', [ScheduleController::class, 'softDelete'])->name('soft-delete-schedule');
    });
    
    Route::middleware(['permission:viewlayer'])->group(function () {
        // layer
        Route::get('/layer', [LayerController::class, 'layer'])->name('layer');
        Route::post('/update-layer', [LayerController::class, 'updatelayer'])->name('update-layer');
        Route::post('/import-layer', [LayerController::class, 'importLayer'])->name('import-layer');
        Route::post('/history-show', [LayerController::class, 'show'])->name('history-show');
    });
    
    Route::middleware(['permission:viewrole'])->group(function () {
        // Roles
        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/admin/roles/submit', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/admin/roles/update', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/admin/roles/delete/{id}', [RoleController::class, 'destroy'])->name('roles.delete');
        Route::get('/admin/roles/assign', [RoleController::class, 'assign'])->name('roles.assign');
        Route::get('/admin/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('/admin/roles/manage', [RoleController::class, 'manage'])->name('roles.manage');
        Route::get('/admin/roles/get-assignment', [RoleController::class, 'getAssignment'])->name('getAssignment');
        Route::get('/admin/roles/get-permission', [RoleController::class, 'getPermission'])->name('getPermission');
        Route::post('/admin/assign-user', [RoleController::class, 'assignUser'])->name('assign.user');
    });
    
    Route::middleware(['permission:viewonbehalf'])->group(function () {
        // Approval-Admin
        Route::post('/admin/approval/goal', [AdminOnBehalfController::class, 'store'])->name('admin.approval.goal');
        Route::get('/admin/approval/goal/{id}', [AdminOnBehalfController::class, 'create'])->name('admin.create.approval.goal');
        // Goals - Admin
        Route::get('/onbehalf', [AdminOnBehalfController::class, 'index'])->name('onbehalf');
        Route::post('/admin/onbehalf/content', [AdminOnBehalfController::class, 'getOnBehalfContent'])->name('admin.onbehalf.content');
        Route::post('/admin/goal-content', [AdminOnBehalfController::class, 'getGoalContent']);
        // Sendback
        Route::post('/admin/sendback/goal', [AdminSendbackController::class, 'store'])->name('admin.sendback.goal');
    });
    
    Route::middleware(['permission:viewreport'])->group(function () {
        
        Route::get('/reports-admin', [AdminReportController::class, 'index'])->name('admin.reports');
        Route::get('/admin/get-report-content/{reportType}', [AdminReportController::class, 'getReportContent']);
        Route::post('/admin/get-report-content', [AdminReportController::class, 'getReportContent']);
        Route::get('/admin/changes-group-company', [AdminReportController::class, 'changesGroupCompany']);
        Route::get('/admin/changes-company', [AdminReportController::class, 'changesCompany']);
        //Employee
        Route::get('/employees', [EmployeeController::class, 'employee'])->name('employees');
        Route::get('/employee/filter', [EmployeeController::class, 'filterEmployees'])->name('employee.filter');
    });
});


Route::fallback(function () {
    return view('errors.404');
});

require __DIR__.'/auth.php';
