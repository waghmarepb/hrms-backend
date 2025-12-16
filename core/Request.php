<?php

class Request
{
    private static $input = null;
    private static $user = null;

    public static function all()
    {
        if (self::$input === null) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $json = file_get_contents('php://input');
                self::$input = json_decode($json, true) ?? [];
            } else {
                self::$input = array_merge($_GET, $_POST);
            }
        }
        
        return self::$input;
    }

    public static function input($key, $default = null)
    {
        $data = self::all();
        return $data[$key] ?? $default;
    }

    public static function has($key)
    {
        $data = self::all();
        return isset($data[$key]);
    }

    public static function only($keys)
    {
        $data = self::all();
        $result = [];
        
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
            }
        }
        
        return $result;
    }

    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function header($key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }

    public static function bearerToken()
    {
        $header = self::header('Authorization');
        
        if ($header && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        
        return null;
    }

    public static function setUser($user)
    {
        self::$user = $user;
    }

    public static function user()
    {
        return self::$user;
    }

    public static function file($key)
    {
        return $_FILES[$key] ?? null;
    }
}

