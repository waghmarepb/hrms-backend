<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'expense_information';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'expense_name',
    ];

    /**
     * Get all expenses for this category
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id', 'id');
    }

    /**
     * Get the chart of account for this expense category
     */
    public function chartOfAccount()
    {
        return $this->hasOne(ChartOfAccount::class, 'HeadName', 'expense_name')
            ->where('HeadType', 'E');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->orderBy('expense_name', 'asc');
    }
}

