<?php

class Auth
{
    private static $user = null;

    public static function attempt($email, $password)
    {
        $db = Database::getInstance();
        
        $user = $db->selectOne(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        return $user;
    }

    public static function createToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $db = Database::getInstance();
        $db->insert('personal_access_tokens', [
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
            'name' => 'auth_token',
            'token' => hash('sha256', $token),
            'abilities' => json_encode(['*']),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }

    public static function validateToken($token)
    {
        if (!$token) {
            return null;
        }
        
        $hashedToken = hash('sha256', $token);
        $db = Database::getInstance();
        
        $tokenRecord = $db->selectOne(
            "SELECT * FROM personal_access_tokens 
             WHERE token = ? 
             AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1",
            [$hashedToken]
        );
        
        if (!$tokenRecord) {
            return null;
        }
        
        $user = $db->selectOne(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$tokenRecord['tokenable_id']]
        );
        
        return $user;
    }

    public static function revokeToken($token)
    {
        if (!$token) {
            return false;
        }
        
        $hashedToken = hash('sha256', $token);
        $db = Database::getInstance();
        
        return $db->delete(
            'personal_access_tokens',
            'WHERE token = ?',
            [$hashedToken]
        );
    }

    public static function setUser($user)
    {
        self::$user = $user;
        Request::setUser($user);
    }

    public static function user()
    {
        return self::$user;
    }

    public static function id()
    {
        return self::$user['id'] ?? null;
    }

    public static function check()
    {
        return self::$user !== null;
    }
}

