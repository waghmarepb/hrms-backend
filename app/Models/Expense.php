<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    // This model represents expense transactions which are stored in acc_transaction table
    // with Vtype = 'Expense'
    
    protected $table = 'acc_transaction';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'VNo',
        'Vtype',
        'VDate',
        'COAID',
        'Narration',
        'Debit',
        'Credit',
        'IsPosted',
        'CreateBy',
        'CreateDate',
        'UpdateBy',
        'UpdateDate',
        'IsAppove',
    ];

    protected $casts = [
        'VDate' => 'date',
        'Debit' => 'decimal:2',
        'Credit' => 'decimal:2',
        'IsPosted' => 'boolean',
        'IsAppove' => 'boolean',
        'CreateDate' => 'datetime',
        'UpdateDate' => 'datetime',
    ];

    const VTYPE_EXPENSE = 'Expense';

    /**
     * Boot method to set default Vtype
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->Vtype = self::VTYPE_EXPENSE;
            $model->IsPosted = 1;
            $model->IsAppove = 1;
        });
    }

    /**
     * Get the chart of account for this expense
     */
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'COAID', 'HeadCode');
    }

    /**
     * Get the creator of this expense
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'CreateBy', 'user_id');
    }

    /**
     * Scope for expense transactions only
     */
    public function scopeExpenses($query)
    {
        return $query->where('Vtype', self::VTYPE_EXPENSE);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('VDate', [$from, $to]);
    }

    /**
     * Scope by expense category (via COAID)
     */
    public function scopeByCategory($query, $categoryHeadCode)
    {
        return $query->where('COAID', $categoryHeadCode);
    }

    /**
     * Get debit entries (actual expenses)
     */
    public function scopeDebitEntries($query)
    {
        return $query->where('Debit', '>', 0);
    }
}

