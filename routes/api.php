<?php

// Health check
$router->get('/health', function() {
    Response::json([
        'success' => true,
        'message' => 'API is running',
        'version' => '1.0.0 (Core PHP)'
    ]);
});

// API v1 routes
$router->prefix('/api/v1')->group(function($router) {
    
    // Public routes - Authentication
    $router->post('/auth/login', [AuthController::class, 'login']);
    
    // Public recruitment endpoint (job seekers don't need auth)
    $router->post('/jobs/{id}/apply', [RecruitmentController::class, 'apply']);
    
    // Protected routes - require authentication
    $router->middleware(['AuthMiddleware'])->group(function($router) {
        
        // Auth endpoints
        $router->post('/auth/logout', [AuthController::class, 'logout']);
        $router->get('/auth/me', [AuthController::class, 'me']);
        
        // Employee Management ✅ MIGRATED
        $router->get('/employees', [EmployeeController::class, 'index']);
        $router->get('/employees/{id}', [EmployeeController::class, 'show']);
        $router->post('/employees', [EmployeeController::class, 'store']);
        $router->put('/employees/{id}', [EmployeeController::class, 'update']);
        $router->delete('/employees/{id}', [EmployeeController::class, 'destroy']);
        
        // Department Management ✅ MIGRATED
        $router->get('/departments', [DepartmentController::class, 'index']);
        $router->get('/departments/{id}', [DepartmentController::class, 'show']);
        $router->post('/departments', [DepartmentController::class, 'store']);
        $router->put('/departments/{id}', [DepartmentController::class, 'update']);
        $router->delete('/departments/{id}', [DepartmentController::class, 'destroy']);
        
        // Leave Management ✅ MIGRATED
        $router->get('/leaves', [LeaveController::class, 'index']);
        $router->get('/leaves/{id}', [LeaveController::class, 'show']);
        $router->post('/leaves', [LeaveController::class, 'store']);
        $router->put('/leaves/{id}', [LeaveController::class, 'update']);
        $router->delete('/leaves/{id}', [LeaveController::class, 'destroy']);
        $router->put('/leaves/{id}/approve', [LeaveController::class, 'approve']);
        $router->put('/leaves/{id}/reject', [LeaveController::class, 'reject']);
        
        // Attendance Management ✅ MIGRATED
        $router->get('/attendance', [AttendanceController::class, 'index']);
        $router->post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        $router->post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
        $router->get('/attendance/report', [AttendanceController::class, 'report']);
        
        // Payroll Management ✅ MIGRATED
        $router->get('/payroll', [PayrollController::class, 'index']);
        $router->get('/payroll/{id}', [PayrollController::class, 'show']);
        $router->post('/payroll/generate', [PayrollController::class, 'generate']);
        $router->put('/payroll/{id}/pay', [PayrollController::class, 'markAsPaid']);
        
        // Notice Management ✅ MIGRATED
        $router->get('/notices', [NoticeController::class, 'index']);
        $router->post('/notices', [NoticeController::class, 'store']);
        $router->delete('/notices/{id}', [NoticeController::class, 'destroy']);
        
        // Recruitment ✅ MIGRATED
        $router->get('/jobs', [RecruitmentController::class, 'jobs']);
        $router->post('/jobs', [RecruitmentController::class, 'createJob']);
        $router->get('/jobs/{id}/applications', [RecruitmentController::class, 'jobApplications']);
        $router->put('/applications/{id}/status', [RecruitmentController::class, 'updateApplicationStatus']);
        
        // Reports ✅ MIGRATED
        $router->get('/reports/dashboard', [ReportController::class, 'dashboard']);
        $router->get('/reports/employees', [ReportController::class, 'employeeReport']);
        $router->get('/reports/attendance', [ReportController::class, 'attendanceReport']);
        $router->get('/reports/leave', [ReportController::class, 'leaveReport']);
        $router->get('/reports/payroll', [ReportController::class, 'payrollReport']);
        
        // ========== EXPENSE MODULE ========== ✅ MIGRATED
        
        // Expense Categories
        $router->get('/expense-categories', [ExpenseController::class, 'categories']);
        $router->post('/expense-categories', [ExpenseController::class, 'createCategory']);
        $router->get('/expense-categories/{id}', [ExpenseController::class, 'showCategory']);
        $router->put('/expense-categories/{id}', [ExpenseController::class, 'updateCategory']);
        $router->delete('/expense-categories/{id}', [ExpenseController::class, 'deleteCategory']);
        
        // Expense Entries
        $router->get('/expenses', [ExpenseController::class, 'index']);
        $router->post('/expenses', [ExpenseController::class, 'store']);
        $router->get('/expenses/statement', [ExpenseController::class, 'statement']);
        $router->get('/expenses/summary', [ExpenseController::class, 'summary']);
        
        // ========== INCOME MODULE ========== ✅ MIGRATED
        
        // Income Categories
        $router->get('/income-categories', [IncomeController::class, 'categories']);
        $router->post('/income-categories', [IncomeController::class, 'createCategory']);
        $router->get('/income-categories/{id}', [IncomeController::class, 'showCategory']);
        $router->put('/income-categories/{id}', [IncomeController::class, 'updateCategory']);
        $router->delete('/income-categories/{id}', [IncomeController::class, 'deleteCategory']);
        
        // Income Entries
        $router->get('/incomes', [IncomeController::class, 'index']);
        $router->post('/incomes', [IncomeController::class, 'store']);
        $router->get('/incomes/statement', [IncomeController::class, 'statement']);
        $router->get('/incomes/summary', [IncomeController::class, 'summary']);
        
        // ========== LOAN MODULE ========== ✅ MIGRATED
        
        // Loan Management
        $router->get('/loans', [LoanController::class, 'index']);
        $router->get('/loans/reports/summary', [LoanController::class, 'summary']);
        $router->post('/loans', [LoanController::class, 'store']);
        $router->get('/loans/{id}', [LoanController::class, 'show']);
        $router->put('/loans/{id}', [LoanController::class, 'update']);
        $router->delete('/loans/{id}', [LoanController::class, 'destroy']);
        $router->put('/loans/{id}/approve', [LoanController::class, 'approve']);
        $router->put('/loans/{id}/reject', [LoanController::class, 'reject']);
        
        // Loan Installments
        $router->get('/loans/{id}/installments', [LoanController::class, 'installments']);
        $router->post('/loans/{id}/installments', [LoanController::class, 'recordInstallment']);
        
        // ========== ASSET MANAGEMENT MODULE ========== ✅ MIGRATED
        
        // Asset Types
        $router->get('/asset-types', [AssetController::class, 'types']);
        $router->post('/asset-types', [AssetController::class, 'createType']);
        $router->get('/asset-types/{id}', [AssetController::class, 'showType']);
        $router->put('/asset-types/{id}', [AssetController::class, 'updateType']);
        $router->delete('/asset-types/{id}', [AssetController::class, 'deleteType']);
        
        // Assets/Equipment
        $router->get('/assets', [AssetController::class, 'index']);
        $router->get('/assets/available', [AssetController::class, 'available']);
        $router->post('/assets', [AssetController::class, 'store']);
        $router->get('/assets/{id}', [AssetController::class, 'show']);
        $router->put('/assets/{id}', [AssetController::class, 'update']);
        $router->delete('/assets/{id}', [AssetController::class, 'destroy']);
        
        // Asset Assignments
        $router->get('/asset-assignments', [AssetController::class, 'assignments']);
        $router->post('/asset-assignments', [AssetController::class, 'assignAsset']);
        $router->put('/asset-assignments/return', [AssetController::class, 'returnAsset']);
        $router->get('/asset-assignments/employee/{employeeId}', [AssetController::class, 'employeeAssets']);
        $router->get('/asset-assignments/history/{employeeId}', [AssetController::class, 'employeeAssetHistory']);
        
        // ========== BANK MANAGEMENT MODULE ========== ✅ MIGRATED
        
        // Banks
        $router->get('/banks', [BankController::class, 'index']);
        $router->post('/banks', [BankController::class, 'store']);
        $router->get('/banks/{id}', [BankController::class, 'show']);
        $router->put('/banks/{id}', [BankController::class, 'update']);
        $router->delete('/banks/{id}', [BankController::class, 'destroy']);
        
        // ========== TAX MODULE ========== ✅ MIGRATED
        
        // Tax Setup (Brackets/Slabs)
        $router->get('/tax-setup', [TaxController::class, 'index']);
        $router->post('/tax-setup', [TaxController::class, 'store']);
        $router->get('/tax-setup/{id}', [TaxController::class, 'show']);
        $router->put('/tax-setup/{id}', [TaxController::class, 'update']);
        $router->delete('/tax-setup/{id}', [TaxController::class, 'destroy']);
        $router->post('/tax-setup/calculate', [TaxController::class, 'calculate']);
        
        // Tax Collections
        $router->get('/tax-collections', [TaxController::class, 'collections']);
        $router->delete('/tax-collections/{id}', [TaxController::class, 'deleteCollection']);
        $router->get('/tax-collections/summary', [TaxController::class, 'summary']);
        
        // ========== AWARD MODULE ========== ✅ MIGRATED
        
        // Awards
        $router->get('/awards', [AwardController::class, 'index']);
        $router->post('/awards', [AwardController::class, 'store']);
        $router->get('/awards/employee/{employeeId}', [AwardController::class, 'employeeAwards']);
        $router->get('/awards/{id}', [AwardController::class, 'show']);
        $router->put('/awards/{id}', [AwardController::class, 'update']);
        $router->delete('/awards/{id}', [AwardController::class, 'destroy']);
        
        // ========== TEMPLATE MODULE ========== ✅ MIGRATED
        
        // Templates
        $router->get('/templates', [TemplateController::class, 'index']);
        $router->get('/templates/active', [TemplateController::class, 'active']);
        $router->post('/templates', [TemplateController::class, 'store']);
        $router->get('/templates/{id}', [TemplateController::class, 'show']);
        $router->put('/templates/{id}', [TemplateController::class, 'update']);
        $router->delete('/templates/{id}', [TemplateController::class, 'destroy']);
        $router->post('/templates/{id}/render', [TemplateController::class, 'render']);
        
        // ========== CHART OF ACCOUNTS MODULE ========== ✅ MIGRATED
        
        // Chart of Accounts
        $router->get('/chart-of-accounts', [ChartOfAccountController::class, 'index']);
        $router->get('/chart-of-accounts/tree', [ChartOfAccountController::class, 'tree']);
        $router->get('/chart-of-accounts/transaction-accounts', [ChartOfAccountController::class, 'transactionAccounts']);
        $router->get('/chart-of-accounts/by-type/{type}', [ChartOfAccountController::class, 'byType']);
        $router->post('/chart-of-accounts', [ChartOfAccountController::class, 'store']);
        $router->get('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'show']);
        $router->put('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'update']);
        $router->delete('/chart-of-accounts/{headCode}', [ChartOfAccountController::class, 'destroy']);
        
        // ========== VOUCHER MODULE ========== ✅ MIGRATED
        
        // Vouchers
        $router->get('/vouchers', [VoucherController::class, 'index']);
        $router->get('/vouchers/{voucherNo}', [VoucherController::class, 'show']);
        $router->post('/vouchers/debit', [VoucherController::class, 'createDebitVoucher']);
        $router->post('/vouchers/credit', [VoucherController::class, 'createCreditVoucher']);
        $router->post('/vouchers/contra', [VoucherController::class, 'createContraVoucher']);
        $router->post('/vouchers/journal', [VoucherController::class, 'createJournalVoucher']);
        $router->put('/vouchers/{voucherNo}/approve', [VoucherController::class, 'approve']);
        $router->delete('/vouchers/{voucherNo}', [VoucherController::class, 'destroy']);
        
        // ========== LEDGER MODULE ========== ✅ MIGRATED
        
        // Ledgers
        $router->get('/ledgers/general', [LedgerController::class, 'generalLedger']);
        $router->get('/ledgers/cash-book', [LedgerController::class, 'cashBook']);
        $router->get('/ledgers/bank-book', [LedgerController::class, 'bankBook']);
        $router->get('/ledgers/account-balance/{accountCode}', [LedgerController::class, 'accountBalance']);
        
        // ========== FINANCIAL REPORTS MODULE ========== ✅ MIGRATED
        
        // Financial Reports
        $router->get('/financial-reports/trial-balance', [FinancialReportController::class, 'trialBalance']);
        $router->get('/financial-reports/profit-loss', [FinancialReportController::class, 'profitLoss']);
        $router->get('/financial-reports/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
        $router->get('/financial-reports/cash-flow', [FinancialReportController::class, 'cashFlow']);
    });
});

