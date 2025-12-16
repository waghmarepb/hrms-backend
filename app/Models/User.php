<?php

class User
{
    private $db;
    private $table = 'user';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email)
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    public function findById($id)
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function checkPassword($user, $password)
    {
        // Check if it's old MD5 password (32 chars)
        if (strlen($user['password']) === 32 && md5($password) === $user['password']) {
            return true;
        }
        
        // Check if it's bcrypt password (60 chars)
        if (strlen($user['password']) === 60) {
            return password_verify($password, $user['password']);
        }
        
        return false;
    }

    public function updateLoginInfo($userId, $ipAddress)
    {
        return $this->db->update(
            $this->table,
            [
                'last_login' => now(),
                'ip_address' => $ipAddress
            ],
            'WHERE id = ?',
            [$userId]
        );
    }

    public function updateLogoutInfo($userId)
    {
        return $this->db->update(
            $this->table,
            ['last_logout' => now()],
            'WHERE id = ?',
            [$userId]
        );
    }

    public function getFullName($user)
    {
        return trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
    }

    public function isAdmin($user)
    {
        return isset($user['is_admin']) && $user['is_admin'] == 1;
    }
}

