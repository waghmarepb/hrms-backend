<?php

return [
    'title' => 'HRMS API Documentation',
    'description' => 'Human Resource Management System - RESTful API',
    'version' => '1.0.0',
    'contact' => [
        'name' => 'API Support',
        'email' => 'support@hrms.com'
    ],
    'servers' => [
        [
            'url' => 'http://localhost/hrms/new-backend/public',
            'description' => 'Local Development Server'
        ]
    ],
    'scan_paths' => [
        __DIR__ . '/../app/Controllers',
        __DIR__ . '/../routes',
        __DIR__ . '/../public'
    ],
    'output_path' => __DIR__ . '/../public/swagger.json'
];

