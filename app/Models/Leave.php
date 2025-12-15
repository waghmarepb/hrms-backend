<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table = 'leave_apply';
    protected $primaryKey = 'leave_appl_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'apply_strt_date',
        'apply_end_date',
        'apply_day',
        'leave_aprv_strt_date',
        'leave_aprv_end_date',
        'num_aprv_day',
        'reason',
        'apply_hard_copy',
        'apply_date',
        'approve_date',
        'approved_by',
        'leave_type',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function getStatusAttribute()
    {
        if (!empty($this->approved_by) && !empty($this->approve_date)) {
            return 'approved';
        }
        return 'pending';
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee ? $this->employee->full_name : 'Unknown';
    }
}
