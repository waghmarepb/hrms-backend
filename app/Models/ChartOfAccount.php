<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'acc_coa';
    protected $primaryKey = 'HeadCode';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'HeadCode',
        'HeadName',
        'PHeadName',
        'HeadLevel',
        'IsActive',
        'IsTransaction',
        'IsGL',
        'HeadType',
        'IsBudget',
        'IsDepreciation',
        'DepreciationRate',
        'CreateBy',
        'CreateDate',
        'UpdateBy',
        'UpdateDate',
    ];

    protected $casts = [
        'HeadLevel' => 'integer',
        'IsActive' => 'boolean',
        'IsTransaction' => 'boolean',
        'IsGL' => 'boolean',
        'IsBudget' => 'boolean',
        'IsDepreciation' => 'boolean',
        'DepreciationRate' => 'decimal:2',
        'CreateDate' => 'datetime',
        'UpdateDate' => 'datetime',
    ];

    /**
     * Get child accounts
     */
    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'PHeadName', 'HeadName');
    }

    /**
     * Get parent account
     */
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'PHeadName', 'HeadName');
    }

    /**
     * Get transactions for this account
     */
    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'COAID', 'HeadCode');
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('IsActive', 1);
    }

    /**
     * Scope for transaction accounts
     */
    public function scopeTransactionable($query)
    {
        return $query->where('IsTransaction', 1);
    }

    /**
     * Get accounts by head type
     */
    public function scopeByHeadType($query, $type)
    {
        return $query->where('HeadType', $type);
    }

    /**
     * Get accounts by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('HeadLevel', $level);
    }
}

