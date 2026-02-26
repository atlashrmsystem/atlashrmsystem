<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Mobile\AttendanceController as MobileAttendanceController;
use App\Http\Controllers\Api\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\LeaveRequestController as MobileLeaveRequestController;
use App\Http\Controllers\Api\Mobile\PaySlipController as MobilePaySlipController;
use App\Http\Controllers\Api\Mobile\PaySlipRequestController as MobilePaySlipRequestController;
use App\Http\Controllers\Api\Mobile\ProfileController as MobileProfileController;
use App\Http\Controllers\Api\Mobile\SalaryCertificateRequestController as MobileSalaryCertificateRequestController;
use App\Http\Controllers\Api\Mobile\SalesController as MobileSalesController;
use App\Http\Controllers\Api\Mobile\ScheduleController as MobileScheduleController;
use App\Http\Controllers\Api\Mobile\ShiftController as MobileShiftController;
use App\Http\Controllers\Api\Mobile\StaffController as MobileStaffController;
use App\Http\Controllers\Api\Mobile\StoreController as MobileStoreController;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppraisalController;
use App\Http\Controllers\AppraisalFormController;
use App\Http\Controllers\BenefitEnrollmentController;
use App\Http\Controllers\BenefitTypeController;
use App\Http\Controllers\BrandAreaController;
use App\Http\Controllers\BrandAreaStoreController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmployeeBankAccountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeExperienceController;
use App\Http\Controllers\EmployeeRelativeController;
use App\Http\Controllers\EmployeeSalaryStructureController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PerformanceCycleController;
use App\Http\Controllers\ReportController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['roles', 'permissions']);
    });

    Route::prefix('admin')->middleware('role:super-admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | Mobile API (Staff / Supervisor / Manager)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [MobileProfileController::class, 'show'])->middleware('permission:view own profile');
    Route::put('/profile', [MobileProfileController::class, 'update'])->middleware('permission:edit own profile');
    Route::get('/stores', [MobileStoreController::class, 'index']);
    Route::get('/leave-types', function () {
        return LeaveType::query()->select(['id', 'name'])->orderBy('name')->get();
    });
    Route::get('/staff', [MobileStaffController::class, 'index']);

    Route::get('/shifts', [MobileShiftController::class, 'index']);
    Route::post('/shifts', [MobileShiftController::class, 'store'])->middleware('permission:manage schedules');
    Route::put('/shifts/{shift}', [MobileShiftController::class, 'update'])->middleware('permission:manage schedules');
    Route::delete('/shifts/{shift}', [MobileShiftController::class, 'destroy'])->middleware('permission:manage schedules');

    Route::get('/schedules', [MobileScheduleController::class, 'index']);
    Route::get('/schedules/weeks', [MobileScheduleController::class, 'weeks']);
    Route::get('/schedules/week-status', [MobileScheduleController::class, 'weekStatus']);
    Route::post('/schedules', [MobileScheduleController::class, 'store'])->middleware('permission:manage schedules');
    Route::post('/schedules/publish', [MobileScheduleController::class, 'publish'])->middleware('permission:manage schedules');
    Route::put('/schedules/{schedule}', [MobileScheduleController::class, 'update'])->middleware('permission:manage schedules');
    Route::delete('/schedules/{schedule}', [MobileScheduleController::class, 'destroy'])->middleware('permission:manage schedules');

    Route::get('/leave-requests', [MobileLeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [MobileLeaveRequestController::class, 'store'])->middleware('permission:request leave');
    Route::get('/leave-requests/{leaveRequest}', [MobileLeaveRequestController::class, 'show']);
    Route::put('/leave-requests/{leaveRequest}/approve', [MobileLeaveRequestController::class, 'approve']);
    Route::put('/leave-requests/{leaveRequest}/reject', [MobileLeaveRequestController::class, 'reject']);

    Route::get('/pay-slips', [MobilePaySlipController::class, 'index'])->middleware('permission:view own pay slips');
    Route::get('/pay-slips/{id}/download', [MobilePaySlipController::class, 'download'])
        ->middleware('permission:view own pay slips')
        ->name('mobile.pay-slips.download');
    Route::get('/pay-slip-requests', [MobilePaySlipRequestController::class, 'index']);
    Route::post('/pay-slip-requests', [MobilePaySlipRequestController::class, 'store'])->middleware('permission:request payslip');
    Route::get('/pay-slip-requests/{paySlipRequest}', [MobilePaySlipRequestController::class, 'show']);
    Route::put('/pay-slip-requests/{paySlipRequest}/approve', [MobilePaySlipRequestController::class, 'approve']);
    Route::put('/pay-slip-requests/{paySlipRequest}/reject', [MobilePaySlipRequestController::class, 'reject']);

    Route::get('/salary-certificate-requests', [MobileSalaryCertificateRequestController::class, 'index']);
    Route::post('/salary-certificate-requests', [MobileSalaryCertificateRequestController::class, 'store'])
        ->middleware('permission:request salary certificate');
    Route::put('/salary-certificate-requests/{salaryCertificateRequest}/approve', [MobileSalaryCertificateRequestController::class, 'approve']);
    Route::put('/salary-certificate-requests/{salaryCertificateRequest}/reject', [MobileSalaryCertificateRequestController::class, 'reject']);

    Route::get('/sales', [MobileSalesController::class, 'index']);
    Route::post('/sales', [MobileSalesController::class, 'store'])->middleware('permission:enter sales');
    Route::get('/sales/report', [MobileSalesController::class, 'report']);

    Route::get('/dashboard', [MobileDashboardController::class, 'index']);

    Route::get('/employees/me', [EmployeeController::class, 'me']);
    Route::patch('/employees/me', [EmployeeController::class, 'updateMe']);
    Route::post('/employees/{id}/create-account', [EmployeeController::class, 'createUserAccount'])->middleware('permission:manage employees');
    Route::post('/employees/{id}/reset-credentials', [EmployeeController::class, 'resetUserCredentials'])->middleware('permission:manage employees');

    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:view employees');
    Route::get('/employees/assignment-rules', [EmployeeController::class, 'assignmentRules'])->middleware('permission:view employees');
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:manage employees');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->middleware('permission:view employees');
    Route::match(['put', 'patch'], '/employees/{id}', [EmployeeController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/educations', [EmployeeEducationController::class, 'index'])->middleware('permission:view employees');
    Route::post('/employees/{employee}/educations', [EmployeeEducationController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/employees/{employee}/educations/{educationId}', [EmployeeEducationController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/educations/{educationId}', [EmployeeEducationController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/experiences', [EmployeeExperienceController::class, 'index'])->middleware('permission:view employees');
    Route::post('/employees/{employee}/experiences', [EmployeeExperienceController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/employees/{employee}/experiences/{experienceId}', [EmployeeExperienceController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/experiences/{experienceId}', [EmployeeExperienceController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/relatives', [EmployeeRelativeController::class, 'index'])->middleware('permission:view employees');
    Route::post('/employees/{employee}/relatives', [EmployeeRelativeController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/employees/{employee}/relatives/{relativeId}', [EmployeeRelativeController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/relatives/{relativeId}', [EmployeeRelativeController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/bank-account', [EmployeeBankAccountController::class, 'show'])->middleware('permission:view employees');
    Route::put('/employees/{employee}/bank-account', [EmployeeBankAccountController::class, 'upsert'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/bank-account', [EmployeeBankAccountController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/documents', [EmployeeDocumentController::class, 'index'])->middleware('permission:view employees');
    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/employees/{employee}/documents/{documentId}', [EmployeeDocumentController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/documents/{documentId}', [EmployeeDocumentController::class, 'destroy'])->middleware('permission:manage employees');
    Route::get('/employees/{employee}/salary-structure', [EmployeeSalaryStructureController::class, 'show'])->middleware('permission:view employees');
    Route::put('/employees/{employee}/salary-structure', [EmployeeSalaryStructureController::class, 'upsert'])->middleware('permission:manage employees');
    Route::delete('/employees/{employee}/salary-structure', [EmployeeSalaryStructureController::class, 'destroy'])->middleware('permission:manage employees');

    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/management', [BrandController::class, 'management']);
    Route::get('/brands/{brand}/stores/{store}/staff', [BrandController::class, 'storeStaff']);
    Route::get('/brands/managers', [BrandController::class, 'managers'])->middleware('permission:manage employees');
    Route::post('/brands', [BrandController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:manage employees');
    Route::post('/brands/{brand}/areas', [BrandAreaController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/brands/{brand}/areas/{area}', [BrandAreaController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/brands/{brand}/areas/{area}', [BrandAreaController::class, 'destroy'])->middleware('permission:manage employees');
    Route::post('/brands/{brand}/stores', [BrandAreaStoreController::class, 'storeForBrand'])->middleware('permission:manage employees');
    Route::post('/brands/{brand}/areas/{area}/stores', [BrandAreaStoreController::class, 'store'])->middleware('permission:manage employees');
    Route::put('/brands/{brand}/areas/{area}/stores/{store}', [BrandAreaStoreController::class, 'update'])->middleware('permission:manage employees');
    Route::delete('/brands/{brand}/areas/{area}/stores/{store}', [BrandAreaStoreController::class, 'destroy'])->middleware('permission:manage employees');

    Route::apiResource('contracts', ContractController::class)->only(['index', 'store', 'show']);

    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/request', [LeaveRequestController::class, 'store']);
        Route::get('/balances', [LeaveRequestController::class, 'balances']);
        Route::patch('/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::patch('/{id}/reject', [LeaveRequestController::class, 'reject']);
    });

    Route::prefix('attendance')->group(function () {
        Route::get('/', [MobileAttendanceController::class, 'index']);
        Route::get('/today', [MobileAttendanceController::class, 'today']);
        Route::get('/summary/today', [\App\Http\Controllers\AttendanceController::class, 'dailySummary']);
        Route::post('/clock-in', [MobileAttendanceController::class, 'clockIn'])->middleware('permission:clock in/out');
        Route::post('/clock-out', [MobileAttendanceController::class, 'clockOut'])->middleware('permission:clock in/out');
    });

    Route::prefix('timesheets')->group(function () {
        Route::get('/', [\App\Http\Controllers\TimesheetController::class, 'index']);
        Route::post('/generate', [\App\Http\Controllers\TimesheetController::class, 'generate']);
    });

    // Recruitment & Onboarding
    Route::apiResource('job-postings', \App\Http\Controllers\JobPostingController::class);
    Route::apiResource('candidates', \App\Http\Controllers\CandidateController::class)->except(['destroy']);

    Route::prefix('applications')->group(function () {
        Route::post('/', [\App\Http\Controllers\ApplicationController::class, 'store']);
        Route::put('/{id}/status', [\App\Http\Controllers\ApplicationController::class, 'updateStatus']);
        Route::get('/{id}/interviews', [\App\Http\Controllers\ApplicationController::class, 'interviews']);
    });

    Route::prefix('interviews')->group(function () {
        Route::post('/', [\App\Http\Controllers\InterviewController::class, 'store']);
        Route::put('/{id}/feedback', [\App\Http\Controllers\InterviewController::class, 'feedback']);
    });

    Route::prefix('offer-letters')->group(function () {
        Route::post('/', [\App\Http\Controllers\OfferLetterController::class, 'store']);
        Route::put('/{id}/accept', [\App\Http\Controllers\OfferLetterController::class, 'accept']);
    });

    Route::prefix('onboarding')->group(function () {
        Route::get('/checklists', [\App\Http\Controllers\OnboardingController::class, 'checklists']);
    });
    Route::post('/employees/{id}/onboarding', [\App\Http\Controllers\OnboardingController::class, 'assign']);
    Route::put('/employee-onboarding/{id}/complete', [OnboardingController::class, 'completeTask']);

    // Module 2: Performance Management
    Route::get('/performance-cycles/active', [PerformanceCycleController::class, 'active']);
    Route::apiResource('performance-cycles', PerformanceCycleController::class)->only(['index', 'store']);
    Route::apiResource('goals', GoalController::class)->only(['index', 'store', 'update']);
    Route::apiResource('appraisal-forms', AppraisalFormController::class)->only(['index', 'show', 'store']);

    Route::apiResource('appraisals', AppraisalController::class)->only(['index', 'show', 'store']);
    Route::post('/appraisals/{id}/submit', [AppraisalController::class, 'submit']);
    Route::post('/appraisals/{id}/review', [AppraisalController::class, 'review']);

    Route::apiResource('feedback-requests', FeedbackController::class)->only(['index', 'store', 'update']);

    // Module 3: Benefits Administration
    Route::get('/benefit-types/active', [BenefitTypeController::class, 'active']);
    Route::apiResource('benefit-types', BenefitTypeController::class)->only(['index', 'store']);
    Route::apiResource('benefit-enrollments', BenefitEnrollmentController::class)->only(['index', 'store', 'update']);

    // Module 4: Enhanced HR Analytics
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/attrition', [AnalyticsController::class, 'attritionRisk']);

    // Phase 4: Payroll & WPS
    Route::get('/payrolls/wps-export', [PayrollController::class, 'exportWps']);
    Route::post('/payrolls/generate', [PayrollController::class, 'generate']);
    Route::apiResource('payrolls', PayrollController::class)->only(['index', 'update']);

    // Phase 5: Emiratisation tracking & Document management
    Route::get('/compliance/emiratisation-stats', [ComplianceController::class, 'emiratisationStats']);
    Route::get('/compliance/expiring-documents', [ComplianceController::class, 'expiringDocuments']);

    // Phase 6: Reports
    Route::get('/reports/summary', [ReportController::class, 'summary']);

    // Phase 6: Notifications & Reports
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});
