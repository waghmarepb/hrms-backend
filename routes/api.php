<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\RecruitmentController;
use App\Http\Controllers\Api\V1\NoticeController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ChartOfAccountController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\V1\LedgerController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\IncomeController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\BankController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\AwardController;
use App\Http\Controllers\Api\V1\TemplateController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Employees
        Route::apiResource('employees', EmployeeController::class);
        
        // Departments
        Route::apiResource('departments', DepartmentController::class);
        
        // Leave Management
        Route::apiResource('leaves', LeaveController::class);
        Route::put('/leaves/{id}/approve', [LeaveController::class, 'approve']);
        Route::put('/leaves/{id}/reject', [LeaveController::class, 'reject']);
        
        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/attendance/report', [AttendanceController::class, 'report']);
        
        // Payroll
        Route::get('/payroll', [PayrollController::class, 'index']);
        Route::get('/payroll/{id}', [PayrollController::class, 'show']);
        Route::post('/payroll/generate', [PayrollController::class, 'generate']);
        Route::put('/payroll/{id}/pay', [PayrollController::class, 'markAsPaid']);
        
        // Recruitment
        Route::get('/jobs', [RecruitmentController::class, 'jobs']);
        Route::post('/jobs', [RecruitmentController::class, 'createJob']);
        Route::get('/jobs/{id}/applications', [RecruitmentController::class, 'jobApplications']);
        Route::put('/applications/{id}/status', [RecruitmentController::class, 'updateApplicationStatus']);
        
        // Notices
        Route::get('/notices', [NoticeController::class, 'index']);
        Route::post('/notices', [NoticeController::class, 'store']);
        Route::delete('/notices/{id}', [NoticeController::class, 'destroy']);
        
        // Reports
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/employees', [ReportController::class, 'employeeReport']);
        Route::get('/reports/attendance', [ReportController::class, 'attendanceReport']);
        Route::get('/reports/leave', [ReportController::class, 'leaveReport']);
        Route::get('/reports/payroll', [ReportController::class, 'payrollReport']);
        
        // ========== ACCOUNTS MODULE ==========
        
        // Chart of Accounts (COA)
        Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index']);
        Route::get('/chart-of-accounts/tree', [ChartOfAccountController::class, 'tree']);
        Route::get('/chart-of-accounts/transaction-accounts', [ChartOfAccountController::class, 'transactionAccounts']);
        Route::get('/chart-of-accounts/by-type/{type}', [ChartOfAccountController::class, 'byType']);
        Route::get('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'show']);
        Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store']);
        Route::put('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'update']);
        Route::delete('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'destroy']);
        
        // Vouchers (Debit, Credit, Contra, Journal)
        Route::get('/vouchers', [VoucherController::class, 'index']);
        Route::get('/vouchers/{voucherNo}', [VoucherController::class, 'show']);
        Route::post('/vouchers/debit', [VoucherController::class, 'createDebitVoucher']);
        Route::post('/vouchers/credit', [VoucherController::class, 'createCreditVoucher']);
        Route::post('/vouchers/contra', [VoucherController::class, 'createContraVoucher']);
        Route::post('/vouchers/journal', [VoucherController::class, 'createJournalVoucher']);
        Route::put('/vouchers/{voucherNo}/approve', [VoucherController::class, 'approve']);
        Route::delete('/vouchers/{voucherNo}', [VoucherController::class, 'destroy']);
        
        // Ledgers (General Ledger, Cash Book, Bank Book)
        Route::get('/ledgers/general', [LedgerController::class, 'generalLedger']);
        Route::get('/ledgers/cash-book', [LedgerController::class, 'cashBook']);
        Route::get('/ledgers/bank-book', [LedgerController::class, 'bankBook']);
        Route::get('/ledgers/account-balance/{accountCode}', [LedgerController::class, 'accountBalance']);
        
        // Financial Reports (Trial Balance, P&L, Cash Flow, Balance Sheet)
        Route::get('/financial-reports/trial-balance', [FinancialReportController::class, 'trialBalance']);
        Route::get('/financial-reports/profit-loss', [FinancialReportController::class, 'profitLoss']);
        Route::get('/financial-reports/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
        Route::get('/financial-reports/cash-flow', [FinancialReportController::class, 'cashFlow']);
        
        // ========== EXPENSE MODULE ==========
        
        // Expense Categories
        Route::get('/expense-categories', [ExpenseController::class, 'categories']);
        Route::post('/expense-categories', [ExpenseController::class, 'createCategory']);
        Route::get('/expense-categories/{id}', [ExpenseController::class, 'showCategory']);
        Route::put('/expense-categories/{id}', [ExpenseController::class, 'updateCategory']);
        Route::delete('/expense-categories/{id}', [ExpenseController::class, 'deleteCategory']);
        
        // Expense Entries
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::get('/expenses/statement', [ExpenseController::class, 'statement']);
        Route::get('/expenses/summary', [ExpenseController::class, 'summary']);
        
        // ========== INCOME MODULE ==========
        
        // Income Categories
        Route::get('/income-categories', [IncomeController::class, 'categories']);
        Route::post('/income-categories', [IncomeController::class, 'createCategory']);
        Route::get('/income-categories/{id}', [IncomeController::class, 'showCategory']);
        Route::put('/income-categories/{id}', [IncomeController::class, 'updateCategory']);
        Route::delete('/income-categories/{id}', [IncomeController::class, 'deleteCategory']);
        
        // Income Entries
        Route::get('/incomes', [IncomeController::class, 'index']);
        Route::post('/incomes', [IncomeController::class, 'store']);
        Route::get('/incomes/statement', [IncomeController::class, 'statement']);
        Route::get('/incomes/summary', [IncomeController::class, 'summary']);
        
        // ========== LOAN MODULE ==========
        
        // Loan Management
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/reports/summary', [LoanController::class, 'summary']);
        Route::post('/loans', [LoanController::class, 'store']);
        Route::get('/loans/{id}', [LoanController::class, 'show']);
        Route::put('/loans/{id}', [LoanController::class, 'update']);
        Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
        Route::put('/loans/{id}/approve', [LoanController::class, 'approve']);
        Route::put('/loans/{id}/reject', [LoanController::class, 'reject']);
        
        // Loan Installments
        Route::get('/loans/{id}/installments', [LoanController::class, 'installments']);
        Route::post('/loans/{id}/installments', [LoanController::class, 'recordInstallment']);
        
        // ========== ASSET MANAGEMENT MODULE ==========
        
        // Asset Types
        Route::get('/asset-types', [AssetController::class, 'types']);
        Route::post('/asset-types', [AssetController::class, 'createType']);
        Route::get('/asset-types/{id}', [AssetController::class, 'showType']);
        Route::put('/asset-types/{id}', [AssetController::class, 'updateType']);
        Route::delete('/asset-types/{id}', [AssetController::class, 'deleteType']);
        
        // Assets/Equipment
        Route::get('/assets', [AssetController::class, 'index']);
        Route::get('/assets/available', [AssetController::class, 'available']);
        Route::post('/assets', [AssetController::class, 'store']);
        Route::get('/assets/{id}', [AssetController::class, 'show']);
        Route::put('/assets/{id}', [AssetController::class, 'update']);
        Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
        
        // Asset Assignments
        Route::get('/asset-assignments', [AssetController::class, 'assignments']);
        Route::post('/asset-assignments', [AssetController::class, 'assignAsset']);
        Route::put('/asset-assignments/return', [AssetController::class, 'returnAsset']);
        Route::get('/asset-assignments/employee/{employeeId}', [AssetController::class, 'employeeAssets']);
        Route::get('/asset-assignments/history/{employeeId}', [AssetController::class, 'employeeAssetHistory']);
        
        // ========== BANK MANAGEMENT MODULE ==========
        
        // Banks
        Route::get('/banks', [BankController::class, 'index']);
        Route::post('/banks', [BankController::class, 'store']);
        Route::get('/banks/{id}', [BankController::class, 'show']);
        Route::put('/banks/{id}', [BankController::class, 'update']);
        Route::delete('/banks/{id}', [BankController::class, 'destroy']);
        
        // ========== TAX MODULE ==========
        
        // Tax Setup (Brackets/Slabs)
        Route::get('/tax-setup', [TaxController::class, 'index']);
        Route::post('/tax-setup', [TaxController::class, 'store']);
        Route::get('/tax-setup/{id}', [TaxController::class, 'show']);
        Route::put('/tax-setup/{id}', [TaxController::class, 'update']);
        Route::delete('/tax-setup/{id}', [TaxController::class, 'destroy']);
        Route::post('/tax-setup/calculate', [TaxController::class, 'calculate']);
        
        // Tax Collections
        Route::get('/tax-collections', [TaxController::class, 'collections']);
        Route::delete('/tax-collections/{id}', [TaxController::class, 'deleteCollection']);
        Route::get('/tax-collections/summary', [TaxController::class, 'summary']);
        
        // ========== AWARD MODULE ==========
        
        // Awards
        Route::get('/awards', [AwardController::class, 'index']);
        Route::post('/awards', [AwardController::class, 'store']);
        Route::get('/awards/employee/{employeeId}', [AwardController::class, 'employeeAwards']);
        Route::get('/awards/{id}', [AwardController::class, 'show']);
        Route::put('/awards/{id}', [AwardController::class, 'update']);
        Route::delete('/awards/{id}', [AwardController::class, 'destroy']);
        
        // ========== TEMPLATE MODULE ==========
        
        // Templates
        Route::get('/templates', [TemplateController::class, 'index']);
        Route::get('/templates/active', [TemplateController::class, 'active']);
        Route::post('/templates', [TemplateController::class, 'store']);
        Route::get('/templates/{id}', [TemplateController::class, 'show']);
        Route::put('/templates/{id}', [TemplateController::class, 'update']);
        Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);
        Route::post('/templates/{id}/render', [TemplateController::class, 'render']);
    });
});

// Public recruitment endpoints (job seekers don't need auth)
Route::prefix('v1')->group(function () {
    Route::post('/jobs/{id}/apply', [RecruitmentController::class, 'apply']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'version' => '1.0.0',
    ]);
});
