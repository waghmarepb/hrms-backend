<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $table = 'bank_information';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
    ];

    /**
     * Search banks by name or account number
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('bank_name', 'LIKE', "%{$term}%")
            ->orWhere('account_number', 'LIKE', "%{$term}%")
            ->orWhere('branch_name', 'LIKE', "%{$term}%");
    }
}

