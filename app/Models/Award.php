<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use HasFactory;

    protected $table = 'award';
    protected $primaryKey = 'award_id';
    public $timestamps = false;

    protected $fillable = [
        'award_name',
        'aw_description',
        'awr_gift_item',
        'date',
        'employee_id',
        'awarded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the employee who received the award
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the person who gave the award
     */
    public function awardedBy()
    {
        return $this->belongsTo(Employee::class, 'awarded_by', 'employee_id');
    }

    /**
     * Scope by employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope by award name
     */
    public function scopeByAwardName($query, $name)
    {
        return $query->where('award_name', 'LIKE', "%{$name}%");
    }
}

