<?php

class CorsMiddleware
{
    public function handle()
    {
        $config = require __DIR__ . '/../../config/cors.php';
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Check if origin is allowed
        if ($config['allowed_origins'][0] === '*' || in_array($origin, $config['allowed_origins'])) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers']));
        header('Access-Control-Max-Age: ' . $config['max_age']);
        
        if ($config['supports_credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        return true;
    }
}

