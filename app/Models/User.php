<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Use existing table name
    protected $table = 'user';
    
    // Primary key
    protected $primaryKey = 'id';
    
    // No timestamps in old table
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'image',
        'status',
        'is_admin',
        'last_login',
        'last_logout',
        'ip_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'last_login' => 'datetime',
        'last_logout' => 'datetime',
        'status' => 'integer',
        'is_admin' => 'integer',
    ];

    /**
     * Get full name attribute
     */
    public function getFullnameAttribute()
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->is_admin == 1;
    }

    /**
     * Check MD5 password (without upgrade due to column size limit)
     */
    public function checkAndUpgradePassword($password)
    {
        // Check if it's old MD5 password (32 chars)
        if (strlen($this->password) === 32 && md5($password) === $this->password) {
            // MD5 password matches - don't upgrade due to column size limit
            return true;
        }
        
        // Check if it's bcrypt password (60 chars)
        if (strlen($this->password) === 60) {
            return Hash::check($password, $this->password);
        }
        
        return false;
    }
}
