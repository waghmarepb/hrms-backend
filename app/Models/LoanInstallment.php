<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $table = 'loan_installment';
    protected $primaryKey = 'loan_inst_id';
    public $timestamps = false;

    protected $fillable = [
        'loan_id',
        'employee_id',
        'installment_amount',
        'payment',
        'date',
        'received_by',
        'installment_no',
    ];

    protected $casts = [
        'installment_amount' => 'decimal:2',
        'payment' => 'decimal:2',
        'date' => 'date',
        'installment_no' => 'integer',
    ];

    /**
     * Get the loan this installment belongs to
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    /**
     * Get the employee who took the loan
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the receiver/supervisor
     */
    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'received_by', 'employee_id');
    }

    /**
     * Scope by loan
     */
    public function scopeForLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
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
}

