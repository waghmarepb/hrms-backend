<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'grand_loan';
    protected $primaryKey = 'loan_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'permission_by',
        'loan_details',
        'amount',
        'interest_rate',
        'installment',
        'installment_period',
        'repayment_amount',
        'date_of_approve',
        'loan_status',
        'repayment_start_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'installment' => 'integer',
        'repayment_amount' => 'decimal:2',
        'date_of_approve' => 'date',
        'repayment_start_date' => 'date',
    ];

    // Loan Status Constants
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_COMPLETED = 3;

    /**
     * Get the employee who took the loan
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the supervisor who approved the loan
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'permission_by', 'employee_id');
    }

    /**
     * Get all installments for this loan
     */
    public function installments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id', 'loan_id');
    }

    /**
     * Get paid installments
     */
    public function paidInstallments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id', 'loan_id');
    }

    /**
     * Scope for approved loans
     */
    public function scopeApproved($query)
    {
        return $query->where('loan_status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending loans
     */
    public function scopePending($query)
    {
        return $query->where('loan_status', self::STATUS_PENDING);
    }

    /**
     * Scope for specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute()
    {
        return $this->installments()->sum('installment_amount');
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute()
    {
        return $this->repayment_amount - $this->total_paid;
    }
}


