<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    // This model represents income transactions which are stored in acc_transaction table
    // with Vtype = 'income'
    
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

    const VTYPE_INCOME = 'income';

    /**
     * Boot method to set default Vtype
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->Vtype = self::VTYPE_INCOME;
            $model->IsPosted = 1;
            $model->IsAppove = 1;
        });
    }

    /**
     * Get the chart of account for this income
     */
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'COAID', 'HeadCode');
    }

    /**
     * Get the creator of this income
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'CreateBy', 'user_id');
    }

    /**
     * Scope for income transactions only
     */
    public function scopeIncomes($query)
    {
        return $query->where('Vtype', self::VTYPE_INCOME);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('VDate', [$from, $to]);
    }

    /**
     * Scope by income category (via COAID)
     */
    public function scopeByCategory($query, $categoryHeadCode)
    {
        return $query->where('COAID', $categoryHeadCode);
    }

    /**
     * Get credit entries (actual income)
     */
    public function scopeCreditEntries($query)
    {
        return $query->where('Credit', '>', 0);
    }
}

