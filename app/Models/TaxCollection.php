<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCollection extends Model
{
    use HasFactory;

    protected $table = 'payroll_tax_collection';
    protected $primaryKey = 'tax_coll_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'sal_month',
        'tax_rate',
        'tax',
        'net_amount',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'tax' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Scope by month
     */
    public function scopeForMonth($query, $month)
    {
        return $query->where('sal_month', $month);
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
        return $query->whereBetween('sal_month', [$from, $to]);
    }
}


