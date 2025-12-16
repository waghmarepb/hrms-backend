<?php
/**
 * Database Connection Test Script
 * Run this to verify your database connection is working
 */

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Load core classes
require_once __DIR__ . '/core/Database.php';

echo "=================================\n";
echo "DATABASE CONNECTION TEST\n";
echo "=================================\n\n";

try {
    echo "Testing database connection...\n\n";
    
    $db = Database::getInstance();
    
    echo "✓ Database connection successful!\n\n";
    
    // Test query
    echo "Testing query execution...\n";
    $result = $db->selectOne("SELECT DATABASE() as db_name, NOW() as current_time");
    
    echo "✓ Query executed successfully!\n\n";
    
    echo "Connection Details:\n";
    echo "-------------------\n";
    echo "Database: " . $result['db_name'] . "\n";
    echo "Server Time: " . $result['current_time'] . "\n\n";
    
    // Test table access
    echo "Testing table access...\n";
    $userCount = $db->selectOne("SELECT COUNT(*) as count FROM user");
    echo "✓ Users table accessible! Found " . $userCount['count'] . " users\n\n";
    
    $employeeCount = $db->selectOne("SELECT COUNT(*) as count FROM employee_history");
    echo "✓ Employee table accessible! Found " . $employeeCount['count'] . " employees\n\n";
    
    echo "=================================\n";
    echo "ALL TESTS PASSED! ✓\n";
    echo "=================================\n";
    echo "\nYour Core PHP backend is ready to use!\n";
    echo "Access API at: http://localhost/new-backend/backend-php/public/api/v1/\n\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. Database credentials in .env file\n";
    echo "2. MySQL server is running\n";
    echo "3. Database 'hrms' exists\n";
    echo "4. User has proper permissions\n\n";
    exit(1);
}

