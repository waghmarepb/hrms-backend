<?php

/**
 * Swagger Documentation Generator
 * Run this script to generate/update swagger.json
 * Usage: php swagger-generate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/swagger.php';

try {
    $openapi = \OpenApi\Generator::scan($config['scan_paths']);
    
    // Write to file
    file_put_contents($config['output_path'], $openapi->toJson());
    
    echo "✓ Swagger documentation generated successfully!\n";
    echo "  Location: {$config['output_path']}\n";
    echo "  View at: http://localhost/hrms/new-backend/public/swagger\n";
} catch (Exception $e) {
    echo "✗ Error generating swagger documentation: {$e->getMessage()}\n";
    exit(1);
}

