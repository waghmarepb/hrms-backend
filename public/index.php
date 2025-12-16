<?php

/**
 * @OA\Info(
 *     title="HRMS API Documentation",
 *     version="1.0.0",
 *     description="Human Resource Management System - RESTful API with comprehensive HR features including employee management, attendance, payroll, leave management, recruitment, and financial accounting.",
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@hrms.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost/hrms/new-backend/public",
 *     description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee management operations"
 * )
 * @OA\Tag(
 *     name="Departments",
 *     description="Department management operations"
 * )
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance tracking and reporting"
 * )
 * @OA\Tag(
 *     name="Leave",
 *     description="Leave/vacation management"
 * )
 * @OA\Tag(
 *     name="Payroll",
 *     description="Payroll generation and management"
 * )
 * @OA\Tag(
 *     name="Recruitment",
 *     description="Job postings and applications"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="HR analytics and reports"
 * )
 * @OA\Tag(
 *     name="Expenses",
 *     description="Expense tracking and categories"
 * )
 * @OA\Tag(
 *     name="Income",
 *     description="Income tracking and categories"
 * )
 * @OA\Tag(
 *     name="Loans",
 *     description="Employee loan management"
 * )
 * @OA\Tag(
 *     name="Assets",
 *     description="Asset/equipment management"
 * )
 * @OA\Tag(
 *     name="Banks",
 *     description="Bank account management"
 * )
 * @OA\Tag(
 *     name="Taxes",
 *     description="Tax setup and collections"
 * )
 * @OA\Tag(
 *     name="Awards",
 *     description="Employee awards and recognition"
 * )
 * @OA\Tag(
 *     name="Accounting",
 *     description="Chart of accounts, vouchers, and ledgers"
 * )
 * @OA\Tag(
 *     name="Financial Reports",
 *     description="Trial balance, profit & loss, balance sheet"
 * )
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php-errors.log');

// Set timezone
date_default_timezone_set('UTC');

// Load environment variables if .env exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Autoload core classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../core/' . $class . '.php',
        __DIR__ . '/../app/Models/' . $class . '.php',
        __DIR__ . '/../app/Controllers/' . $class . '.php',
        __DIR__ . '/../app/Middleware/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load helpers
require_once __DIR__ . '/../app/Helpers/helpers.php';

// Handle errors and exceptions
set_exception_handler(function ($e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    
    if (env('APP_DEBUG') === 'true') {
        Response::error($e->getMessage(), 500, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        Response::error('Internal server error', 500);
    }
});

// Initialize router
$router = new Router();

// Apply CORS middleware globally
$corsMiddleware = new CorsMiddleware();
$corsMiddleware->handle();

// Load routes
require __DIR__ . '/../routes/api.php';

// Dispatch request
$router->dispatch();

