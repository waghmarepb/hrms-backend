<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee_history';
    protected $primaryKey = 'emp_his_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'pos_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'alter_phone',
        'present_address',
        'parmanent_address',
        'picture',
        'dept_id',
        'hire_date',
        'dob',
        'gender',
        'marital_status',
        'duty_type',
        'is_admin',
    ];

    protected $appends = ['full_name', 'designation', 'department_name', 'status'];

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getDesignationAttribute()
    {
        // You can add a relationship to position table if available
        return $this->position->name ?? 'Employee';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department->department_name ?? 'No Department';
    }

    public function getStatusAttribute()
    {
        // If termination_date is in future or same as hire_date, considered active
        return 'active';
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id', 'dept_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'pos_id', 'pos_id');
    }
}
