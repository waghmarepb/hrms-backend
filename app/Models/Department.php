<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'department';
    protected $primaryKey = 'dept_id';
    public $timestamps = false;

    protected $fillable = [
        'department_name',
        'parent_id',
    ];

    protected $appends = ['dept_name'];

    public function getDeptNameAttribute()
    {
        return $this->department_name;
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'dept_id', 'dept_id');
    }
}
