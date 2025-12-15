<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory;

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

    // Voucher Types Constants
    const VTYPE_DEBIT = 'DV';
    const VTYPE_CREDIT = 'CV';
    const VTYPE_CONTRA = 'ContV';
    const VTYPE_JOURNAL = 'JV';
    const VTYPE_CASH_ADJUSTMENT = 'AJ-C';
    const VTYPE_BANK_ADJUSTMENT = 'AJ-B';
    const VTYPE_BALANCE_ADJUSTMENT = 'AJ';

    /**
     * Get the chart of account for this transaction
     */
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'COAID', 'HeadCode');
    }

    /**
     * Get the creator of this transaction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'CreateBy', 'user_id');
    }

    /**
     * Scope for approved transactions
     */
    public function scopeApproved($query)
    {
        return $query->where('IsAppove', 1);
    }

    /**
     * Scope for posted transactions
     */
    public function scopePosted($query)
    {
        return $query->where('IsPosted', 1);
    }

    /**
     * Scope by voucher type
     */
    public function scopeByVoucherType($query, $type)
    {
        return $query->where('Vtype', $type);
    }

    /**
     * Scope by voucher number
     */
    public function scopeByVoucherNo($query, $voucherNo)
    {
        return $query->where('VNo', $voucherNo);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('VDate', [$from, $to]);
    }

    /**
     * Get debit transactions
     */
    public function scopeDebit($query)
    {
        return $query->where('Debit', '>', 0);
    }

    /**
     * Get credit transactions
     */
    public function scopeCredit($query)
    {
        return $query->where('Credit', '>', 0);
    }
}

