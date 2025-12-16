<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasFactory;

    protected $table = 'income_area';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'income_field',
    ];

    /**
     * Get all income entries for this category
     */
    public function incomes()
    {
        return $this->hasMany(Income::class, 'income_category_id', 'id');
    }

    /**
     * Get the chart of account for this income category
     */
    public function chartOfAccount()
    {
        return $this->hasOne(ChartOfAccount::class, 'HeadName', 'income_field')
            ->where('HeadType', 'I');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->orderBy('income_field', 'asc');
    }
}


