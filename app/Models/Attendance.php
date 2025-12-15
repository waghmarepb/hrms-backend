<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance_history';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uid',
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'work_hours',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'work_hours' => 'decimal:2',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}


