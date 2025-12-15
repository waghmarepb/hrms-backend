<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $table = 'position';
    protected $primaryKey = 'pos_id';
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'position_name',
        'position_details',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'pos_id', 'pos_id');
    }

    public function getNameAttribute()
    {
        return $this->position_name;
    }
}

