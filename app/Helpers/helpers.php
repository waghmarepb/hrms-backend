<?php

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        $configPath = __DIR__ . '/../../config/' . $file . '.php';
        
        if (!file_exists($configPath)) {
            return $default;
        }
        
        $config = require $configPath;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }
        
        return $config;
    }
}

if (!function_exists('now')) {
    function now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    function today()
    {
        return date('Y-m-d');
    }
}

